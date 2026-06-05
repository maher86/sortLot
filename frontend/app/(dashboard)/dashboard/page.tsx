"use client";

import Link from "next/link";
import { useState } from "react";
import {
  ArrowUpRight,
  Banknote,
  Boxes,
  CheckCircle2,
  Clock3,
  PackageCheck,
  Radar,
  ReceiptText,
  Sparkles,
  Truck,
  Users,
  type LucideIcon,
} from "lucide-react";

import { buttonVariants } from "@/components/ui/button";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatFils, useCustomers, useSuppliers } from "@/lib/contacts";
import { formatStatus as formatInvoiceStatus, useInvoices, usePayments, type Invoice } from "@/lib/invoices";
import { useItems } from "@/lib/items";
import { formatStatus as formatPackageStatus, packageStatuses, usePackages } from "@/lib/packages";

function sum(values: number[]) {
  return values.reduce((total, value) => total + value, 0);
}

function pct(value: number, total: number) {
  if (total <= 0) {
    return 0;
  }

  return Math.round((value / total) * 100);
}

export default function DashboardPage() {
  const [dateFilter, setDateFilter] = useState({ from: "", to: "" });
  const activeDateFilter = {
    from: dateFilter.from || undefined,
    to: dateFilter.to || undefined,
  };
  const sales = useInvoices("sales", activeDateFilter);
  const purchase = useInvoices("purchase", activeDateFilter);
  const credit = useInvoices("credit", activeDateFilter);
  const payments = usePayments("", activeDateFilter);
  const packages = usePackages({ page: 1 });
  const items = useItems({});
  const customers = useCustomers();
  const suppliers = useSuppliers();

  const salesInvoices = sales.data ?? [];
  const purchaseInvoices = purchase.data ?? [];
  const creditNotes = credit.data ?? [];
  const paymentRows = payments.data ?? [];
  const packageRows = packages.data?.data ?? [];
  const itemRows = items.data ?? [];

  const openReceivables = sum(salesInvoices.map((invoice) => invoice.balance_fils));
  const openPayables = sum(purchaseInvoices.map((invoice) => invoice.balance_fils));
  const cashCollected = sum(paymentRows.map((payment) => payment.amount_fils));
  const credited = sum(creditNotes.map((invoice) => invoice.total_fils));
  const availableItems = itemRows.filter((item) => item.status === "available").length;
  const reservedItems = itemRows.filter((item) => item.status === "reserved").length;
  const activePackages = packageRows.filter((row) => !["shipped", "closed"].includes(row.status)).length;
  const sortedPackages = packageRows.filter((row) => ["sorted", "partially_shipped", "shipped", "closed"].includes(row.status)).length;
  const sortingProgress = pct(sortedPackages, packageRows.length);
  const inventoryTotal = Math.max(itemRows.length, 1);
  const financeTotal = Math.max(openReceivables + openPayables + cashCollected + credited, 1);
  const maxInvoiceTotal = Math.max(...salesInvoices.map((row) => row.total_fils), 1);
  const revenuePulse = salesInvoices.slice(0, 8).map((invoice, index) => ({
    id: invoice.id,
    height: Math.max(18, pct(invoice.total_fils, maxInvoiceTotal) || 12) + index * 2,
  }));
  const recentInvoices = [...salesInvoices, ...purchaseInvoices, ...creditNotes]
    .sort((a, b) => b.issue_date.localeCompare(a.issue_date))
    .slice(0, 5);

  return (
    <section className="space-y-6">
      <div className="sortlot-fade-up overflow-hidden rounded-md border bg-[linear-gradient(120deg,#101828,#174e63,#3d2f68,#101828)] p-6 text-white shadow-sm sortlot-sheen">
        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
          <div className="space-y-5">
            <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium backdrop-blur">
              <Sparkles className="h-3.5 w-3.5" />
              SortLot command center
            </div>
            <div>
              <h1 className="max-w-3xl text-4xl font-semibold leading-tight tracking-normal md:text-5xl">
                Live control for used clothing packages, sorting, sales, and cash.
              </h1>
              <p className="mt-3 max-w-2xl text-sm leading-6 text-white/75">
                Every shipment, item, invoice, credit note, and payment in one sharp operating view.
              </p>
            </div>
            <div className="flex flex-wrap gap-2">
              <Link className={buttonVariants({ className: "bg-white text-slate-950 hover:bg-white/90" })} href="/packages">
                <PackageCheck className="h-4 w-4" />
                Open packages
              </Link>
              <Link className={buttonVariants({ className: "border-white/30 bg-white/10 text-white hover:bg-white/20", variant: "outline" })} href="/invoices">
                <ReceiptText className="h-4 w-4" />
                Finance
              </Link>
            </div>
          </div>

          <div className="sortlot-drift rounded-md border border-white/15 bg-white/10 p-4 backdrop-blur">
            <div className="grid grid-cols-2 gap-4">
              <RingGauge label="Sorted" tone="#6ee7b7" value={sortingProgress} />
              <RingGauge label="Reserved" tone="#93c5fd" value={pct(reservedItems, inventoryTotal)} />
            </div>
            <div className="mt-5 grid grid-cols-3 gap-3 text-sm">
              <HeroStat label="Packages" value={packageRows.length} />
              <HeroStat label="Active" value={activePackages} />
              <HeroStat label="Items" value={itemRows.length} />
            </div>
          </div>
        </div>
      </div>

      <div className="sortlot-fade-up flex flex-col gap-3 rounded-md border bg-background p-4 shadow-sm sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 className="text-base font-semibold">Dashboard Filters</h2>
          <p className="mt-1 text-sm text-muted-foreground">Filter finance, payments, and invoice activity by date.</p>
        </div>
        <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
          <label className="space-y-1 text-xs font-medium text-muted-foreground">
            From
            <Input
              className="w-full sm:w-40"
              onChange={(event) => setDateFilter((current) => ({ ...current, from: event.target.value }))}
              type="date"
              value={dateFilter.from}
            />
          </label>
          <label className="space-y-1 text-xs font-medium text-muted-foreground">
            To
            <Input
              className="w-full sm:w-40"
              onChange={(event) => setDateFilter((current) => ({ ...current, to: event.target.value }))}
              type="date"
              value={dateFilter.to}
            />
          </label>
          <Button onClick={() => setDateFilter({ from: "", to: "" })} variant="outline">
            All time
          </Button>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard delay="0ms" icon={Banknote} label="Receivables" tone="emerald" value={formatFils(openReceivables)} />
        <MetricCard delay="80ms" icon={Clock3} label="Payables" tone="amber" value={formatFils(openPayables)} />
        <MetricCard delay="160ms" icon={CheckCircle2} label="Collected" tone="sky" value={formatFils(cashCollected)} />
        <MetricCard delay="240ms" icon={ReceiptText} label="Credit notes" tone="rose" value={formatFils(credited)} />
      </div>

      <div className="grid gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
        <section className="sortlot-fade-up rounded-md border bg-slate-950 p-5 text-white shadow-sm [animation-delay:260ms]">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold">Finance Orbit</h2>
              <p className="mt-1 text-sm text-white/60">Money movement by category.</p>
            </div>
            <Radar className="h-5 w-5 text-cyan-200" />
          </div>
          <div className="mt-6 flex items-center justify-center">
            <DonutChart
              segments={[
                { color: "#34d399", label: "Receivables", value: pct(openReceivables, financeTotal) },
                { color: "#f59e0b", label: "Payables", value: pct(openPayables, financeTotal) },
                { color: "#38bdf8", label: "Collected", value: pct(cashCollected, financeTotal) },
                { color: "#fb7185", label: "Credit", value: pct(credited, financeTotal) },
              ]}
            />
          </div>
        </section>

        <section className="sortlot-fade-up rounded-md border bg-background p-5 shadow-sm [animation-delay:320ms]">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold">Inventory Rings</h2>
              <p className="mt-1 text-sm text-muted-foreground">Stock readiness and reservation pressure.</p>
            </div>
            <Link className={buttonVariants({ size: "sm", variant: "outline" })} href="/items">
              View items
              <ArrowUpRight className="h-4 w-4" />
            </Link>
          </div>
          <div className="mt-6 grid gap-4 sm:grid-cols-3">
            <CircleMetric color="#10b981" label="Available" value={pct(availableItems, inventoryTotal)} />
            <CircleMetric color="#2563eb" label="Reserved" value={pct(reservedItems, inventoryTotal)} />
            <CircleMetric color="#111827" label="Sorted packages" value={sortingProgress} />
          </div>
        </section>
      </div>

      <div className="grid gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(360px,0.75fr)]">
        <section className="sortlot-fade-up rounded-md border bg-background p-4 [animation-delay:280ms]">
          <div className="flex items-center justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold">Commercial Pulse</h2>
              <p className="mt-1 text-sm text-muted-foreground">Sales value, stock movement, and open finance pressure.</p>
            </div>
            <Link className={buttonVariants({ size: "sm", variant: "outline" })} href="/invoices">
              View invoices
              <ArrowUpRight className="h-4 w-4" />
            </Link>
          </div>

          <div className="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_240px]">
            <div className="flex h-56 items-end gap-3 rounded-md border bg-slate-50 p-4">
              {(revenuePulse.length ? revenuePulse : Array.from({ length: 8 }, (_, index) => ({ id: String(index), height: 20 + index * 6 }))).map((bar, index) => (
                <div key={bar.id} className="flex flex-1 flex-col items-center gap-2">
                  <div
                    className="w-full rounded-t-md bg-[linear-gradient(180deg,#14b8a6,#2563eb)] transition-all duration-700"
                    style={{ height: `${Math.min(bar.height, 96)}%`, animationDelay: `${index * 80}ms` }}
                  />
                  <div className="h-1.5 w-full rounded-full bg-slate-200" />
                </div>
              ))}
            </div>

            <div className="grid gap-3">
              <MiniStat icon={Boxes} label="Available stock" value={availableItems} />
              <MiniStat icon={PackageCheck} label="Reserved stock" value={reservedItems} />
              <MiniStat icon={Users} label="Customers" value={customers.data?.length ?? 0} />
              <MiniStat icon={Truck} label="Suppliers" value={suppliers.data?.length ?? 0} />
            </div>
          </div>
        </section>

        <section className="sortlot-fade-up rounded-md border bg-background p-4 [animation-delay:360ms]">
          <h2 className="text-lg font-semibold">Package Flow</h2>
          <p className="mt-1 text-sm text-muted-foreground">Operational load by package status.</p>
          <div className="mt-5 space-y-3">
            {packageStatuses.slice(0, 7).map((status) => {
              const count = packageRows.filter((row) => row.status === status).length;
              const width = Math.max(6, pct(count, Math.max(packageRows.length, 1)));

              return (
                <div key={status}>
                  <div className="mb-1 flex items-center justify-between text-xs">
                    <span className="font-medium">{formatPackageStatus(status)}</span>
                    <span className="text-muted-foreground">{count}</span>
                  </div>
                  <div className="h-2 overflow-hidden rounded-full bg-muted">
                    <div className="h-full rounded-full bg-slate-900 transition-all duration-700" style={{ width: `${width}%` }} />
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      </div>

      <div className="grid gap-4 xl:grid-cols-2">
        <ActivityPanel invoices={recentInvoices} />
        <PaymentPanel payments={paymentRows.slice(0, 5)} />
      </div>
    </section>
  );
}

function HeroStat({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-md border border-white/15 bg-white/10 p-3">
      <div className="text-xl font-semibold">{value}</div>
      <div className="text-xs text-white/60">{label}</div>
    </div>
  );
}

function RingGauge({ label, tone, value }: { label: string; tone: string; value: number }) {
  const radius = 42;
  const circumference = 2 * Math.PI * radius;
  const offset = circumference - (Math.min(value, 100) / 100) * circumference;

  return (
    <div className="rounded-md border border-white/15 bg-white/10 p-3 text-center">
      <svg className="mx-auto h-28 w-28 -rotate-90" viewBox="0 0 112 112">
        <circle cx="56" cy="56" fill="none" r={radius} stroke="rgba(255,255,255,.16)" strokeWidth="10" />
        <circle
          cx="56"
          cy="56"
          fill="none"
          r={radius}
          stroke={tone}
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          strokeLinecap="round"
          strokeWidth="10"
        />
      </svg>
      <div className="-mt-20 pb-8">
        <div className="text-2xl font-semibold">{value}%</div>
        <div className="text-xs text-white/60">{label}</div>
      </div>
    </div>
  );
}

function DonutChart({ segments }: { segments: Array<{ color: string; label: string; value: number }> }) {
  let current = 0;
  const gradient = segments
    .map((segment) => {
      const start = current;
      current += segment.value;
      return `${segment.color} ${start}% ${current}%`;
    })
    .join(", ");

  return (
    <div className="grid w-full gap-5 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-center">
      <div className="relative mx-auto h-44 w-44 rounded-full" style={{ background: `conic-gradient(${gradient || "#334155 0% 100%"})` }}>
        <div className="absolute inset-6 rounded-full bg-slate-950" />
        <div className="absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-3xl font-semibold">100%</span>
          <span className="text-xs text-white/55">tracked</span>
        </div>
      </div>
      <div className="space-y-3">
        {segments.map((segment) => (
          <div key={segment.label} className="flex items-center justify-between text-sm">
            <span className="flex items-center gap-2 text-white/70">
              <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: segment.color }} />
              {segment.label}
            </span>
            <span className="font-semibold">{segment.value}%</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function CircleMetric({ color, label, value }: { color: string; label: string; value: number }) {
  return (
    <div className="rounded-md border p-4 text-center">
      <div className="relative mx-auto h-28 w-28 rounded-full" style={{ background: `conic-gradient(${color} ${value}%, #e5e7eb ${value}% 100%)` }}>
        <div className="absolute inset-3 rounded-full bg-background" />
        <div className="absolute inset-0 flex items-center justify-center text-xl font-semibold">{value}%</div>
      </div>
      <p className="mt-3 text-sm font-medium">{label}</p>
    </div>
  );
}

function MetricCard({
  delay,
  icon: Icon,
  label,
  tone,
  value,
}: {
  delay: string;
  icon: LucideIcon;
  label: string;
  tone: "emerald" | "amber" | "sky" | "rose";
  value: string;
}) {
  const tones = {
    emerald: "bg-emerald-50 text-emerald-700",
    amber: "bg-amber-50 text-amber-700",
    sky: "bg-sky-50 text-sky-700",
    rose: "bg-rose-50 text-rose-700",
  };

  return (
    <section className="sortlot-fade-up rounded-md border bg-background p-4 shadow-sm" style={{ animationDelay: delay }}>
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm text-muted-foreground">{label}</p>
          <p className="mt-2 text-2xl font-semibold">{value}</p>
        </div>
        <div className={`rounded-md p-2 ${tones[tone]}`}>
          <Icon className="h-5 w-5" />
        </div>
      </div>
    </section>
  );
}

function MiniStat({ icon: Icon, label, value }: { icon: LucideIcon; label: string; value: number }) {
  return (
    <div className="flex items-center justify-between rounded-md border p-3">
      <div className="flex items-center gap-2">
        <Icon className="h-4 w-4 text-muted-foreground" />
        <span className="text-sm">{label}</span>
      </div>
      <span className="font-semibold">{value}</span>
    </div>
  );
}

function ActivityPanel({ invoices }: { invoices: Invoice[] }) {
  return (
    <section className="sortlot-fade-up rounded-md border bg-background p-4 [animation-delay:420ms]">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold">Recent Finance</h2>
        <Link className="text-sm text-primary hover:underline" href="/invoices">
          Open
        </Link>
      </div>
      <div className="mt-4 space-y-3">
        {invoices.length === 0 ? <EmptyLine text="No invoices yet" /> : null}
        {invoices.map((invoice) => (
          <div key={invoice.id} className="flex items-center justify-between rounded-md border p-3">
            <div>
              <p className="font-medium">{invoice.number}</p>
              <p className="text-xs text-muted-foreground">{formatInvoiceStatus(invoice.status)}</p>
            </div>
            <div className="text-right">
              <p className="font-semibold">{formatFils(invoice.total_fils)}</p>
              <p className="text-xs text-muted-foreground">{invoice.issue_date}</p>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}

function PaymentPanel({ payments }: { payments: Array<{ id: string; amount_fils: number; payment_date: string; reference: string | null }> }) {
  return (
    <section className="sortlot-fade-up rounded-md border bg-background p-4 [animation-delay:500ms]">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold">Payment Stream</h2>
        <Link className="text-sm text-primary hover:underline" href="/invoices">
          Review
        </Link>
      </div>
      <div className="mt-4 space-y-3">
        {payments.length === 0 ? <EmptyLine text="No payments recorded" /> : null}
        {payments.map((payment) => (
          <Link key={payment.id} className="flex items-center justify-between rounded-md border p-3 transition hover:bg-muted" href={`/invoices/payments/${payment.id}`}>
            <div>
              <p className="font-medium">{payment.reference ?? "Payment receipt"}</p>
              <p className="text-xs text-muted-foreground">{payment.payment_date}</p>
            </div>
            <p className="font-semibold">{formatFils(payment.amount_fils)}</p>
          </Link>
        ))}
      </div>
    </section>
  );
}

function EmptyLine({ text }: { text: string }) {
  return <div className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">{text}</div>;
}
