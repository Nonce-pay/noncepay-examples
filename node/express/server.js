import { createVerify, randomUUID } from 'node:crypto'
import express from 'express'

const API = 'https://api.trezorpay.com'
const PORT = Number(process.env.PORT ?? 3000)
const PUBLIC_URL = required('PUBLIC_URL').replace(/\/$/, '') // where TrezorPay can reach this server
const WALLET = required('WALLET') // your wallet: payments are forwarded there
const TICKER = process.env.TICKER ?? 'trc20/usdt'
const PRICE_USD = '49.90'

// The public key never changes: fetch it once, or pin it with TREZORPAY_PUBLIC_KEY.
const PUBLIC_KEY = process.env.TREZORPAY_PUBLIC_KEY ?? (await trezorpay('/pubkey/')).pubkey

// Use your database in a real shop.
const orders = new Map()

const app = express()

app.get('/', (req, res) => {
  res.send(
    page(
      'Checkout',
      `<form method="post" action="/orders"><button>Pay $${PRICE_USD} in crypto</button></form>`
    )
  )
})

app.post('/orders', async (req, res, next) => {
  try {
    // The callback URL identifies the payment address: one per order, never reused.
    const id = randomUUID()
    const callback = `${PUBLIC_URL}/trezorpay/callback?order=${id}`
    const created = await trezorpay(`/${TICKER}/create/`, { callback, address: WALLET, pending: 1 })
    const price = await trezorpay(`/${TICKER}/convert/`, { value: PRICE_USD, from: 'usd' })
    const qr = await trezorpay(`/${TICKER}/qrcode/`, {
      address: created.address_in,
      value: price.value_coin,
      size: 240,
    })
    orders.set(id, {
      address: created.address_in,
      amount: price.value_coin,
      minimum: created.minimum_transaction_coin,
      qr: qr.qr_code,
      status: 'waiting',
      payments: new Set(),
    })
    res.redirect(303, `/orders/${id}`)
  } catch (error) {
    next(error)
  }
})

app.get('/orders/:id', (req, res) => {
  const order = orders.get(req.params.id)
  if (!order) return res.status(404).send('Unknown order')
  res.send(
    page(
      `Order ${order.status}`,
      `<meta http-equiv="refresh" content="10">
      <p>Send <b>${escape(order.amount)}</b> ${escape(TICKER)} to</p>
      <p><code>${escape(order.address)}</code></p>
      <img src="data:image/png;base64,${order.qr}" width="240" height="240" alt="Payment QR code">
      <p>Status: <b>${order.status}</b></p>`
    )
  )
})

app.get('/trezorpay/callback', (req, res) => {
  // GET callbacks are signed over the full URL, exactly as we receive it.
  if (!verify(PUBLIC_URL + req.originalUrl, req.get('x-ca-signature'))) {
    return res.status(401).send('Bad signature')
  }
  const order = orders.get(req.query.order)
  if (!order || req.query.address_in !== order.address) return res.send('*ok*')

  if (req.query.pending === '1') {
    if (order.status === 'waiting') order.status = 'received'
  } else if (!order.payments.has(req.query.uuid)) {
    // Each payment sends its own callback, once per uuid; a customer may pay in several parts.
    order.payments.add(req.query.uuid)
    order.paid = add(order.paid ?? '0', req.query.value_coin)
    order.status = compare(order.paid, order.amount) >= 0 ? 'paid' : 'underpaid'
  }
  res.send('*ok*')
})

app.listen(PORT, () => console.log(`Shop on http://localhost:${PORT}, callbacks to ${PUBLIC_URL}`))

function verify(data, signature) {
  if (!signature) return false
  return createVerify('sha256').update(data).verify(PUBLIC_KEY, signature, 'base64')
}

async function trezorpay(path, params = {}) {
  const url = new URL(path, API)
  for (const [key, value] of Object.entries(params)) url.searchParams.set(key, String(value))
  const body = await (await fetch(url)).json()
  if (body.status !== 'success') throw new Error(`${path}: ${body.error}`)
  return body
}

// Amounts are decimal strings: compare them exactly, never as floats.
const SCALE = 18
function units(amount) {
  const [whole, fraction = ''] = amount.split('.')
  return BigInt(whole + fraction.padEnd(SCALE, '0').slice(0, SCALE))
}
function add(a, b) {
  const sum = (units(a) + units(b)).toString().padStart(SCALE + 1, '0')
  return `${sum.slice(0, -SCALE)}.${sum.slice(-SCALE)}`
}
function compare(a, b) {
  const diff = units(a) - units(b)
  return diff > 0n ? 1 : diff < 0n ? -1 : 0
}

function required(name) {
  if (!process.env[name]) throw new Error(`Set ${name} (see README.md)`)
  return process.env[name]
}

function escape(text) {
  return String(text).replace(/[&<>"]/g, (c) => `&#${c.charCodeAt(0)};`)
}

function page(title, body) {
  return `<!doctype html><meta charset="utf-8"><title>${title}</title>
  <body style="font-family:system-ui;max-width:560px;margin:48px auto;padding:0 16px">
  <h1>${title}</h1>${body}</body>`
}
