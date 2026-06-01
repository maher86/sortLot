"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { Download, Plus, Search } from "lucide-react";

import { InvoiceStatusBadge } from "@/components/invoices/InvoiceStatusBadge";
import { Button, buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { formatFils } from "@/lib/contacts";
import { formatStatus, invoicePath, invoiceStatuses, useInvoices, usePayments, type Invoice, type InvoiceKind } from "@/lib/invoices";

type Tab = InvoiceKind | "payments";

export default function InvoicesPage() {
  const [tab, setTab] = useState<Tab>("sales");
  const [status, setStatus] = useState("");
  const [search, setSearch] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const sales = useInvoices("sales", { status, search, from, to });
  const purchase = useInvoices("purchase", { status, search, from, to });
  const payments = usePayments("");
  const invoices = useMemo(() => (tab === "purchase" ? purchase.data ?? [] : sales.data ?? []), [purchase.data, sales.data, tab]);
  const isLoading = tab === "purchase" ? purchase.isLoading : sales.isLoading;

  const exportHref = useMemo(() => {
    const rows = invoices.map((invoice) => [invoice.number, invoice.status, invoice.issue_date, invoice.customer?.name ?? invoice.supplier?.name ?? "", invoice.total_fils, invoice.balance_fils]);
    const csv = [["Number", "Status", "Issue date", "Party", "Total fils", "Balance fils"], ...rows].map((row) => row.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(",")).join("\n");
    return `data:text/csv;charset=utf-8,${encodeURIComponent(csv)}`;
  }, [invoices]);

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Invoicing</h1>
          <p className="mt-1 text-sm text-muted-foreground">Sales orders, purchase orders, PDF actions, and payments.</p>
        </div>
        <div className="flex gap-2">
          <Link className={buttonVariants()} href="/invoices/sales/new">
            <Plus className="h-4 w-4" />
            Sales order
          </Link>
          <Link className={buttonVariants({ variant: "outline" })} href="/invoices/purchase/new">
            <Plus className="h-4 w-4" />
            Purchase order
          </Link>
        </div>
      </div>

      <div className="flex flex-wrap gap-2">
        <TabButton active={tab === "sales"} onClick={() => setTab("sales")}>Sales Orders</TabButton>
        <TabButton active={tab === "purchase"} onClick={() => setTab("purchase")}>Purchase Orders</TabButton>
        <TabButton active={tab === "payments"} onClick={() => setTab("payments")}>Payments</TabButton>
      </div>

      {tab !== "payments" ? (
        <>
          <div className="space-y-3 rounded-md border bg-background p-3">
            <div className="flex flex-wrap gap-2">
              <StatusPill active={status === ""} label="All" onClick={() => setStatus("")} />
              {invoiceStatuses.map((current) => (
                <StatusPill active={status === current} key={current} label={formatStatus(current)} onClick={() => setStatus(current)} />
              ))}
            </div>
            <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_150px_150px_auto]">
              <label className="relative">
                <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input className="pl-9" onChange={(event) => setSearch(event.target.value)} placeholder="Search invoice number" value={search} />
              </label>
              <Input aria-label="From date" onChange={(event) => setFrom(event.target.value)} type="date" value={from} />
              <Input aria-label="To date" onChange={(event) => setTo(event.target.value)} type="date" value={to} />
              <a className={buttonVariants({ variant: "outline" })} download={`${tab}-orders.csv`} href={exportHref}>
                <Download className="h-4 w-4" />
                Export
              </a>
            </div>
          </div>
          <InvoiceTable invoices={invoices} isLoading={isLoading} kind={tab} />
        </>
      ) : (
        <div className="overflow-hidden rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Method</TableHead>
                <TableHead>Reference</TableHead>
                <TableHead>Amount</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {payments.data?.length === 0 ? (
                <TableRow>
                  <TableCell className="py-8 text-center text-muted-foreground" colSpan={4}>
                    No payments found
                  </TableCell>
                </TableRow>
              ) : null}
              {payments.data?.map((payment) => (
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
      )}
    </section>
  );
}

function InvoiceTable({ invoices, isLoading, kind }: { invoices: Invoice[]; isLoading: boolean; kind: InvoiceKind }) {
  return (
    <div className="overflow-hidden rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Number</TableHead>
            <TableHead>Party</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Issue date</TableHead>
            <TableHead>Total</TableHead>
            <TableHead>Balance</TableHead>
            <TableHead className="text-right">Action</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {isLoading ? (
            <TableRow>
              <TableCell className="py-8 text-center text-muted-foreground" colSpan={7}>
                Loading invoices
              </TableCell>
            </TableRow>
          ) : null}
          {!isLoading && invoices.length === 0 ? (
            <TableRow>
              <TableCell className="py-8 text-center text-muted-foreground" colSpan={7}>
                No invoices found
              </TableCell>
            </TableRow>
          ) : null}
          {invoices.map((invoice) => (
            <TableRow key={invoice.id}>
              <TableCell className="font-medium">{invoice.number}</TableCell>
              <TableCell>{invoice.customer?.name ?? invoice.supplier?.name ?? "-"}</TableCell>
              <TableCell><InvoiceStatusBadge status={invoice.status} /></TableCell>
              <TableCell>{invoice.issue_date}</TableCell>
              <TableCell>{formatFils(invoice.total_fils)}</TableCell>
              <TableCell>{formatFils(invoice.balance_fils)}</TableCell>
              <TableCell className="text-right">
                <Link className={buttonVariants({ size: "sm", variant: "outline" })} href={`${invoicePath(kind)}/${invoice.id}`}>
                  Open
                </Link>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

function TabButton({ active, children, onClick }: { active: boolean; children: React.ReactNode; onClick: () => void }) {
  return (
    <Button onClick={onClick} variant={active ? "default" : "outline"}>
      {children}
    </Button>
  );
}

function StatusPill({ active, label, onClick }: { active: boolean; label: string; onClick: () => void }) {
  return (
    <Button onClick={onClick} size="xs" variant={active ? "default" : "outline"}>
      {label}
    </Button>
  );
}
