import { createVerify } from 'node:crypto'

const API = 'https://api.noncepay.app'
export const PUBLIC_URL = required('PUBLIC_URL').replace(/\/$/, '') // where NoncePay can reach this app
export const WALLET = required('WALLET') // your wallet: payments are forwarded there
export const TICKER = process.env.TICKER ?? 'trc20/usdt'
export const PRICE_USD = '49.90'

export type Order = {
  address: string
  amount: string
  qr: string
  status: 'waiting' | 'received' | 'underpaid' | 'paid'
  paid: string
  payments: Set<string>
}

// Use your database in a real shop. globalThis keeps the map across route modules in dev.
const store = globalThis as unknown as { orders?: Map<string, Order> }
export const orders = (store.orders ??= new Map())

export async function noncepay<T = Record<string, string>>(
  path: string,
  params: Record<string, string | number> = {}
): Promise<T> {
  const url = new URL(path, API)
  for (const [key, value] of Object.entries(params)) url.searchParams.set(key, String(value))
  const body = await (await fetch(url, { cache: 'no-store' })).json()
  if (body.status !== 'success') throw new Error(`${path}: ${body.error}`)
  return body
}

// The public key never changes: fetch it once, or pin it with NONCEPAY_PUBLIC_KEY.
let publicKey: Promise<string> | undefined
export async function verify(data: string, signature: string | null): Promise<boolean> {
  if (!signature) return false
  publicKey ??= process.env.NONCEPAY_PUBLIC_KEY
    ? Promise.resolve(process.env.NONCEPAY_PUBLIC_KEY)
    : noncepay<{ pubkey: string }>('/pubkey/').then((body) => body.pubkey)
  return createVerify('sha256')
    .update(data)
    .verify(await publicKey, signature, 'base64')
}

// Amounts are decimal strings: compare them exactly, never as floats.
const SCALE = 18
function units(amount: string): bigint {
  const [whole, fraction = ''] = amount.split('.')
  return BigInt(whole + fraction.padEnd(SCALE, '0').slice(0, SCALE))
}
export function add(a: string, b: string): string {
  const sum = (units(a) + units(b)).toString().padStart(SCALE + 1, '0')
  return `${sum.slice(0, -SCALE)}.${sum.slice(-SCALE)}`
}
export function compare(a: string, b: string): number {
  const diff = units(a) - units(b)
  return diff > 0n ? 1 : diff < 0n ? -1 : 0
}

function required(name: string): string {
  const value = process.env[name]
  if (!value) throw new Error(`Set ${name} (see README.md)`)
  return value
}
