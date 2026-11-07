import { PRICE_USD } from '@/lib/trezorpay'

export default function Checkout() {
  return (
    <>
      <h1>Checkout</h1>
      <form method="post" action="/orders">
        <button>Pay ${PRICE_USD} in crypto</button>
      </form>
    </>
  )
}
