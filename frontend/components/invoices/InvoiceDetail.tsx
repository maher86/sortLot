"use client";

import Link from "next/link";
import { useState } from "react";
import { CreditCard, Download, FilePlus2, Mail, RotateCcw, Send } from "lucide-react";
import { toast } from "sonner";

import { InvoiceStatusBadge } from "@/components/invoices/InvoiceStatusBadge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { api } from "@/lib/api";
import { formatFils } from "@/lib/contacts";
import {
  formatStatus,
  invoiceEndpoint,
  paymentMethods,
  useCancelInvoice,
  useConfirmInvoice,
  useCreateCreditNote,
  useCreatePayment,
  useInvoice,
  usePayments,
  type InvoiceKind,
  type PaymentMethod,
} from "@/lib/invoices";

function today() {
  return new Date().toISOString().slice(0, 10);
}

export function InvoiceDetail({ id, kind }: { id: string; kind: InvoiceKind }) {
  const { data: invoice, isLoading, refetch } = useInvoice(kind, id);
  const { data: payments = [] } = usePayments(id);
  const confirmInvoice = useConfirmInvoice(kind, id);
  const cancelInvoice = useCancelInvoice(kind, id);
  const createCreditNote = useCreateCreditNote(id);
  const createPayment = useCreatePayment(id, kind);
  const [amountFils, setAmountFils] = useState("");
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>("bank_transfer");
  const [paymentDate, setPaymentDate] = useState(today());
  const [reference, setReference] = useState("");

  if (isLoading) {
    return <div className="rounded-md border p-6 text-sm text-muted-foreground">Loading invoice</div>;
  }

  if (!invoice) {
    return <div className="rounded-md border p-6 text-sm text-muted-foreground">Invoice not found</div>;
  }

  const party = invoice.customer ?? invoice.supplier;
  const canConfirm = invoice.status === "draft";
  const canCancel = !["cancelled", "paid"].includes(invoice.status);
  const canPay = !["draft", "cancelled", "paid"].includes(invoice.status) && invoice.balance_fils > 0;

  async function confirm() {
    await confirmInvoice.mutateAsync();
    await refetch();
    toast.success("Invoice confirmed");
  }

  async function cancel() {
    await cancelInvoice.mutateAsync();
    await refetch();
    toast.success("Invoice cancelled");
  }

  async function recordPayment(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await createPayment.mutateAsync({
      amount_fils: Number(amountFils || 0),
      payment_method: paymentMethod,
      payment_date: paymentDate,
      reference: reference || null,
    });
    setAmountFils("");
    setReference("");
    await refetch();
    toast.success("Payment recorded");
  }

  async function downloadPdf() {
    const currentInvoice = invoice;
    if (!currentInvoice) {
      return;
    }

    const response = await api.get(`${invoiceEndpoint(kind)}/${id}/pdf`, { responseType: "blob" });
    const contentType = String(response.headers["content-type"] ?? "");

    if (!contentType.includes("application/pdf")) {
      toast.info("PDF generation queued. Try download again in a moment.");
      return;
    }

    const url = window.URL.createObjectURL(response.data);
    const link = document.createElement("a");
    link.href = url;
    link.download = `${currentInvoice.number}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  }

  async function sendEmail() {
    const defaultEmail = invoice?.customer?.email ?? invoice?.supplier?.email ?? "";
    const email = window.prompt("Send invoice email to", defaultEmail);

    if (!email) {
      return;
    }

    await api.post(`${invoiceEndpoint(kind)}/${id}/send-email`, { email });
    await refetch();
    toast.success(`Invoice email sent to ${email}`);
  }

  async function creditNote() {
    const created = await createCreditNote.mutateAsync();
    toast.success(`Credit note ${created.number} created`);
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="text-2xl font-semibold">{invoice.number}</h1>
            <InvoiceStatusBadge status={invoice.status} />
          </div>
          <p className="mt-1 text-sm text-muted-foreground">
            {kind === "sales" ? "Sales order" : "Purchase order"} for {party?.name ?? "Unknown party"}
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button disabled={!canConfirm || confirmInvoice.isPending} onClick={confirm} variant="outline">
            <Send className="h-4 w-4" />
            Confirm
          </Button>
          <Button disabled={!canCancel || cancelInvoice.isPending} onClick={cancel} variant="outline">
            <RotateCcw className="h-4 w-4" />
            Cancel
          </Button>
          <Button onClick={downloadPdf} variant="outline">
            <Download className="h-4 w-4" />
            Download PDF
          </Button>
          <Button onClick={sendEmail} variant="outline">
            <Mail className="h-4 w-4" />
            Send email
          </Button>
          {kind === "sales" ? (
            <Button disabled={invoice.status === "draft" || createCreditNote.isPending} onClick={creditNote} variant="outline">
              <FilePlus2 className="h-4 w-4" />
              Credit note
            </Button>
          ) : null}
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-4">
        <Metric label="Issue" value={invoice.issue_date} />
        <Metric label="Due" value={invoice.due_date ?? "-"} />
        <Metric label="Total" value={formatFils(invoice.total_fils)} />
        <Metric label="Balance" value={formatFils(invoice.balance_fils)} />
      </div>

      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div className="space-y-4">
          <div className="overflow-hidden rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Description</TableHead>
                  <TableHead>Qty</TableHead>
                  <TableHead>Unit</TableHead>
                  <TableHead>Disc</TableHead>
                  <TableHead>Total</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {invoice.lines?.map((line) => (
                  <TableRow key={line.id}>
                    <TableCell className="font-medium">{line.description}</TableCell>
                    <TableCell>{line.quantity}</TableCell>
                    <TableCell>{formatFils(line.unit_price_fils)}</TableCell>
                    <TableCell>{line.discount_pct}%</TableCell>
                    <TableCell>{formatFils(line.line_total_fils)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>

          <div className="overflow-hidden rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Payment date</TableHead>
                  <TableHead>Method</TableHead>
                  <TableHead>Reference</TableHead>
                  <TableHead>Amount</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {payments.length === 0 ? (
                  <TableRow>
                    <TableCell className="py-8 text-center text-muted-foreground" colSpan={4}>
                      No payments recorded
                    </TableCell>
                  </TableRow>
                ) : null}
                {payments.map((payment) => (
                  <TableRow key={payment.id}>
                    <TableCell>{payment.payment_date}</TableCell>
                    <TableCell>{formatStatus(payment.payment_method)}</TableCell>
                    <TableCell>{payment.reference ?? "-"}</TableCell>
                    <TableCell>{formatFils(payment.amount_fils)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </div>

        <aside className="space-y-4 rounded-md border bg-background p-4">
          <h2 className="font-semibold">Record payment</h2>
          <form className="space-y-3" onSubmit={recordPayment}>
            <label className="space-y-1 text-sm font-medium">
              Amount fils
              <Input disabled={!canPay} onChange={(event) => setAmountFils(event.target.value)} required type="number" value={amountFils} />
            </label>
            <label className="space-y-1 text-sm font-medium">
              Method
              <select className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" disabled={!canPay} onChange={(event) => setPaymentMethod(event.target.value as PaymentMethod)} value={paymentMethod}>
                {paymentMethods.map((method) => (
                  <option key={method} value={method}>
                    {formatStatus(method)}
                  </option>
                ))}
              </select>
            </label>
            <label className="space-y-1 text-sm font-medium">
              Payment date
              <Input disabled={!canPay} onChange={(event) => setPaymentDate(event.target.value)} required type="date" value={paymentDate} />
            </label>
            <label className="space-y-1 text-sm font-medium">
              Reference
              <Input disabled={!canPay} onChange={(event) => setReference(event.target.value)} value={reference} />
            </label>
            <Button disabled={!canPay || createPayment.isPending} type="submit">
              <CreditCard className="h-4 w-4" />
              Record payment
            </Button>
          </form>
          <dl className="space-y-2 border-t pt-4 text-sm">
            <MetricRow label="Subtotal" value={formatFils(invoice.subtotal_fils)} />
            <MetricRow label="Discount" value={formatFils(invoice.discount_fils)} />
            <MetricRow label={`VAT ${invoice.vat_rate}%`} value={formatFils(invoice.vat_amount_fils)} />
            <MetricRow label="Paid" value={formatFils(invoice.paid_amount_fils)} />
          </dl>
          <Link className="text-sm text-primary hover:underline" href="/invoices">
            Back to invoices
          </Link>
        </aside>
      </div>
    </section>
  );
}

function Metric({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-md border bg-background p-3">
      <div className="text-xs uppercase text-muted-foreground">{label}</div>
      <div className="mt-1 font-semibold">{value}</div>
    </div>
  );
}

function MetricRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-center justify-between">
      <dt>{label}</dt>
      <dd>{value}</dd>
    </div>
  );
}
