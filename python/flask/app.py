import base64
import os
import uuid
from decimal import Decimal
from html import escape

import requests
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding
from flask import Flask, abort, redirect, request

API = "https://api.noncepay.app"
PUBLIC_URL = os.environ["PUBLIC_URL"].rstrip("/")  # where NoncePay can reach this server
WALLET = os.environ["WALLET"]  # your wallet: payments are forwarded there
TICKER = os.environ.get("TICKER", "trc20/usdt")
PRICE_USD = "49.90"


def noncepay(path, **params):
    body = requests.get(f"{API}{path}", params=params, timeout=15).json()
    if body.get("status") != "success":
        raise RuntimeError(f"{path}: {body.get('error')}")
    return body


# The public key never changes: fetch it once, or pin it with NONCEPAY_PUBLIC_KEY.
PUBLIC_KEY = serialization.load_pem_public_key(
    (os.environ.get("NONCEPAY_PUBLIC_KEY") or noncepay("/pubkey/")["pubkey"]).encode()
)


def verify(data: bytes, signature: str | None) -> bool:
    if not signature:
        return False
    try:
        PUBLIC_KEY.verify(base64.b64decode(signature), data, padding.PKCS1v15(), hashes.SHA256())
        return True
    except Exception:
        return False


# Use your database in a real shop.
orders = {}
app = Flask(__name__)


def page(title, body):
    return (
        f'<!doctype html><meta charset="utf-8"><title>{title}</title>'
        f'<body style="font-family:system-ui;max-width:560px;margin:48px auto;padding:0 16px">'
        f"<h1>{title}</h1>{body}</body>"
    )


@app.get("/")
def checkout():
    return page("Checkout", f'<form method="post" action="/orders"><button>Pay ${PRICE_USD} in crypto</button></form>')


@app.post("/orders")
def create_order():
    # The callback URL identifies the payment address: one per order, never reused.
    order_id = str(uuid.uuid4())
    callback = f"{PUBLIC_URL}/noncepay/callback?order={order_id}"
    created = noncepay(f"/{TICKER}/create/", callback=callback, address=WALLET, pending=1)
    price = noncepay(f"/{TICKER}/convert/", value=PRICE_USD, **{"from": "usd"})
    qr = noncepay(f"/{TICKER}/qrcode/", address=created["address_in"], value=price["value_coin"], size=240)
    orders[order_id] = {
        "address": created["address_in"],
        "amount": price["value_coin"],
        "qr": qr["qr_code"],
        "status": "waiting",
        "paid": Decimal(0),
        "payments": set(),
    }
    return redirect(f"/orders/{order_id}", code=303)


@app.get("/orders/<order_id>")
def show_order(order_id):
    order = orders.get(order_id) or abort(404)
    return page(
        f"Order {order['status']}",
        f'<meta http-equiv="refresh" content="10">'
        f"<p>Send <b>{escape(order['amount'])}</b> {escape(TICKER)} to</p>"
        f"<p><code>{escape(order['address'])}</code></p>"
        f'<img src="data:image/png;base64,{order["qr"]}" width="240" height="240" alt="Payment QR code">'
        f"<p>Status: <b>{order['status']}</b></p>",
    )


@app.get("/noncepay/callback")
def callback():
    # GET callbacks are signed over the full URL, exactly as we receive it.
    signed = f"{PUBLIC_URL}{request.path}?{request.query_string.decode()}"
    if not verify(signed.encode(), request.headers.get("x-ca-signature")):
        return "Bad signature", 401

    query = request.args
    order = orders.get(query.get("order", ""))
    if not order or query.get("address_in") != order["address"]:
        return "*ok*"

    if query.get("pending") == "1":
        if order["status"] == "waiting":
            order["status"] = "received"
    elif query.get("uuid") not in order["payments"]:
        # Each payment sends its own callback, once per uuid; a customer may pay in several parts.
        order["payments"].add(query.get("uuid"))
        # Amounts are decimal strings: Decimal compares them exactly, floats would not.
        order["paid"] += Decimal(query.get("value_coin", "0"))
        order["status"] = "paid" if order["paid"] >= Decimal(order["amount"]) else "underpaid"
    return "*ok*"


if __name__ == "__main__":
    app.run(port=int(os.environ.get("PORT", 5000)))
