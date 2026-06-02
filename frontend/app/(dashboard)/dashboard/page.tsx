"use client";

import Link from "next/link";
import { ArrowUpRight, Banknote, Boxes, CheckCircle2, Clock3, PackageCheck, ReceiptText, Sparkles, Truck, Users } from "lucide-react";

import { buttonVariants } from "@/components/ui/button";
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
  const sales = useInvoices("sales");
  const purchase = useInvoices("purchase");
  const credit = useInvoices("credit");
  const payments = usePayments("");
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
  const revenuePulse = salesInvoices.slice(0, 8).map((invoice, index) => ({
    id: invoice.id,
    height: Math.max(18, pct(invoice.total_fils, Math.max(...salesInvoices.map((row) => row.total_fils), 1)) || 12) + index * 2,
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
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs uppercase text-white/60">Sorting velocity</p>
                <p className="mt-1 text-3xl font-semibold">{sortingProgress}%</p>
              </div>
              <div className="rounded-full bg-emerald-400/20 p-3 text-emerald-200">
                <Truck className="h-6 w-6" />
              </div>
            </div>
            <div className="mt-5 h-3 overflow-hidden rounded-full bg-white/15">
              <div className="h-full rounded-full bg-emerald-300 transition-all duration-700" style={{ width: `${sortingProgress}%` }} />
            </div>
            <div className="mt-5 grid grid-cols-3 gap-3 text-sm">
              <HeroStat label="Packages" value={packageRows.length} />
              <HeroStat label="Active" value={activePackages} />
              <HeroStat label="Items" value={itemRows.length} />
            </div>
          </div>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard delay="0ms" icon={Banknote} label="Receivables" tone="emerald" value={formatFils(openReceivables)} />
        <MetricCard delay="80ms" icon={Clock3} label="Payables" tone="amber" value={formatFils(openPayables)} />
        <MetricCard delay="160ms" icon={CheckCircle2} label="Collected" tone="sky" value={formatFils(cashCollected)} />
        <MetricCard delay="240ms" icon={ReceiptText} label="Credit notes" tone="rose" value={formatFils(credited)} />
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

function MetricCard({
  delay,
  icon: Icon,
  label,
  tone,
  value,
}: {
  delay: string;
  icon: typeof Banknote;
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

function MiniStat({ icon: Icon, label, value }: { icon: typeof Boxes; label: string; value: number }) {
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
