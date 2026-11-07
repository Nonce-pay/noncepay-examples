export const metadata = { title: 'TrezorPay checkout example' }

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body
        style={{ fontFamily: 'system-ui', maxWidth: 560, margin: '48px auto', padding: '0 16px' }}
      >
        {children}
      </body>
    </html>
  )
}
