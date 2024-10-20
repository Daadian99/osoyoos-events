import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import Link from 'next/link';
import { AuthProvider } from './AuthContext';

const inter = Inter({ subsets: ["latin"] });

export const metadata: Metadata = {
  title: "Osoyoos Events",
  description: "Event ticketing system for Osoyoos",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <head>
        {/* ... other head content ... */}
        <meta httpEquiv="Content-Security-Policy" content="script-src 'self' 'unsafe-eval' 'unsafe-inline';" />
      </head>
      <body className={inter.className}>
        <AuthProvider>
          <header className="bg-blue-600 text-white p-4">
            <nav className="container mx-auto flex justify-between items-center">
              <Link href="/" className="text-2xl font-bold">Osoyoos Events</Link>
              <div>
                <Link href="/" className="mr-4">Home</Link>
                <Link href="/events" className="mr-4">Events</Link>
                <Link href="/login" className="mr-4">Login</Link>
                <Link href="/register" className="bg-white text-blue-600 px-4 py-2 rounded">Register</Link>
              </div>
            </nav>
          </header>
          <main className="container mx-auto mt-8 px-4">{children}</main>
          <footer className="bg-gray-100 mt-8 py-4 text-center">
            © 2024 Osoyoos Event Ticketing. All rights reserved.
          </footer>
        </AuthProvider>
      </body>
    </html>
  );
}
