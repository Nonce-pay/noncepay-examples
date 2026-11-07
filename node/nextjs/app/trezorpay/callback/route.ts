import { add, compare, orders, PUBLIC_URL, verify } from '@/lib/trezorpay'

export async function GET(request: Request) {
  // GET callbacks are signed over the full URL, exactly as we receive it.
  const url = new URL(request.url)
  const signed = PUBLIC_URL + request.url.slice(url.origin.length)
  if (!(await verify(signed, request.headers.get('x-ca-signature')))) {
    return new Response('Bad signature', { status: 401 })
  }

  const query = url.searchParams
  const order = orders.get(query.get('order') ?? '')
  if (!order || query.get('address_in') !== order.address) return new Response('*ok*')

  const uuid = query.get('uuid') ?? ''
  if (query.get('pending') === '1') {
    if (order.status === 'waiting') order.status = 'received'
  } else if (!order.payments.has(uuid)) {
    // Each payment sends its own callback, once per uuid; a customer may pay in several parts.
    order.payments.add(uuid)
    order.paid = add(order.paid, query.get('value_coin') ?? '0')
    order.status = compare(order.paid, order.amount) >= 0 ? 'paid' : 'underpaid'
  }
  return new Response('*ok*')
}
