import { randomUUID } from 'node:crypto'
import { orders, PRICE_USD, PUBLIC_URL, TICKER, trezorpay, WALLET } from '@/lib/trezorpay'

export async function POST() {
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
    qr: qr.qr_code,
    status: 'waiting',
    paid: '0',
    payments: new Set(),
  })
  return new Response(null, { status: 303, headers: { location: `/orders/${id}` } })
}
