"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useState } from "react";
import { Download, Mail } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";
import { formatFils } from "@/lib/contacts";
import { formatStatus, invoicePath, usePayment, type InvoiceKind } from "@/lib/invoices";

function kindFor(type?: string): InvoiceKind {
  if (type === "purchase_order") {
    return "purchase";
  }

  if (type === "credit_note") {
    return "credit";
  }

  return "sales";
}

export default function PaymentDetailPage() {
  const params = useParams<{ id: string }>();
  const { data: payment, isLoading } = usePayment(params.id);
  const [isExporting, setIsExporting] = useState(false);

  if (isLoading) {
    return <div className="rounded-md border p-6 text-sm text-muted-foreground">Loading payment</div>;
  }

  if (!payment) {
    return <div className="rounded-md border p-6 text-sm text-muted-foreground">Payment not found</div>;
  }

  const currentPayment = payment;
  const invoice = currentPayment.invoice;
  const party = invoice?.customer ?? invoice?.supplier;

  async function exportPdf() {
    const popup = window.open("", "_blank");
    setIsExporting(true);

    try {
      if (popup) {
        popup.document.write("<title>Preparing payment receipt</title><p style='font-family: Arial; padding: 24px;'>Preparing payment receipt PDF...</p>");
      }

      const response = await api.get(`/payments/${currentPayment.id}/pdf`, { responseType: "blob" });
      const contentType = String(response.headers["content-type"] ?? "");

      if (!contentType.includes("application/pdf")) {
        const message = await response.data.text();
        popup?.close();
        toast.error(message || "Payment PDF could not be generated.");
        return;
      }

      const url = window.URL.createObjectURL(response.data);
      const dataUrl = await blobToDataUrl(response.data);
      const filename = `payment-receipt-${currentPayment.reference ?? currentPayment.id}.pdf`;

      if (popup) {
        popup.document.open();
        popup.document.write(`
          <!doctype html>
          <html>
            <head>
              <title>${filename}</title>
              <style>
                html, body { height: 100%; margin: 0; background: #111827; }
                iframe { border: 0; height: 100%; width: 100%; }
                a { background: #fff; border-radius: 6px; color: #111827; font: 14px Arial; left: 16px; padding: 8px 10px; position: fixed; top: 16px; text-decoration: none; z-index: 2; }
              </style>
            </head>
            <body>
              <a href="${dataUrl}" download="${filename}">Download PDF</a>
              <iframe src="${dataUrl}" title="${filename}"></iframe>
            </body>
          </html>
        `);
        popup.document.close();
      }

      const link = document.createElement("a");
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      setTimeout(() => window.URL.revokeObjectURL(url), 60000);
      toast.success("Payment receipt PDF ready");
    } catch {
      popup?.close();
      toast.error("Payment PDF export failed. Please sign in again and retry.");
    } finally {
      setIsExporting(false);
    }
  }

  async function sendEmail() {
    const email = window.prompt("Send payment receipt to", party?.email ?? "");
    if (!email) {
      return;
    }

    await api.post(`/payments/${currentPayment.id}/send-email`, { email });
    toast.success(`Payment receipt sent to ${email}`);
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Payment Receipt</h1>
          <p className="mt-1 text-sm text-muted-foreground">{currentPayment.id}</p>
          {invoice ? (
            <Link className="mt-1 inline-block text-sm text-primary hover:underline" href={`${invoicePath(kindFor(invoice.type))}/${invoice.id}`}>
              Invoice: {invoice.number}
            </Link>
          ) : null}
        </div>
        <div className="flex gap-2">
          <Button disabled={isExporting} onClick={exportPdf} variant="outline">
            <Download className="h-4 w-4" />
            {isExporting ? "Exporting" : "Export PDF"}
          </Button>
          <Button onClick={sendEmail} variant="outline">
            <Mail className="h-4 w-4" />
            Send email
          </Button>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-4">
        <Metric label="Amount" value={formatFils(currentPayment.amount_fils)} />
        <Metric label="Date" value={currentPayment.payment_date} />
        <Metric label="Method" value={formatStatus(currentPayment.payment_method)} />
        <Metric label="Reference" value={currentPayment.reference ?? "-"} />
      </div>
    </section>
  );
}

function blobToDataUrl(blob: Blob) {
  return new Promise<string>((resolve, reject) => {
    const reader = new FileReader();
    reader.addEventListener("loadend", () => resolve(String(reader.result)));
    reader.addEventListener("error", () => reject(reader.error));
    reader.readAsDataURL(blob);
  });
}

function Metric({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-md border bg-background p-3">
      <div className="text-xs uppercase text-muted-foreground">{label}</div>
      <div className="mt-1 font-semibold">{value}</div>
    </div>
  );
}
