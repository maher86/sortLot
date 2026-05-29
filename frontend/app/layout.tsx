import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "SortLot",
  description: "Used clothing package management",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
