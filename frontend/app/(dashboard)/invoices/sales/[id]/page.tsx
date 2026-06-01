"use client";

import { useParams } from "next/navigation";

import { InvoiceDetail } from "@/components/invoices/InvoiceDetail";

export default function SalesOrderDetailPage() {
  const params = useParams<{ id: string }>();

  return <InvoiceDetail id={params.id} kind="sales" />;
}
