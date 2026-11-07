import { notFound } from 'next/navigation'
import { orders, TICKER } from '@/lib/trezorpay'

export const dynamic = 'force-dynamic'

export default async function OrderPage({ params }: { params: Promise<{ id: string }> }) {
  const order = orders.get((await params).id)
  if (!order) notFound()
  return (
    <>
      <meta httpEquiv="refresh" content="10" />
      <h1>Order {order.status}</h1>
      <p>
        Send <b>{order.amount}</b> {TICKER} to
      </p>
      <p>
        <code>{order.address}</code>
      </p>
      <img
        src={`data:image/png;base64,${order.qr}`}
        width={240}
        height={240}
        alt="Payment QR code"
      />
      <p>
        Status: <b>{order.status}</b>
      </p>
    </>
  )
}
