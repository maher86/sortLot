import type { Metadata } from "next";
import "./globals.css";

import { AppProviders } from "@/components/providers/AppProviders";

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
      <body>
        <AppProviders>{children}</AppProviders>
      </body>
    </html>
  );
}
