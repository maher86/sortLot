import { Badge } from "@/components/ui/badge";
import { formatStatus, type InvoiceStatus } from "@/lib/invoices";

const statusClass: Record<InvoiceStatus, string> = {
  draft: "bg-slate-100 text-slate-700",
  pending: "bg-amber-100 text-amber-800",
  partial: "bg-sky-100 text-sky-800",
  paid: "bg-emerald-100 text-emerald-800",
  overdue: "bg-red-100 text-red-800",
  cancelled: "bg-zinc-100 text-zinc-700",
  refunded: "bg-violet-100 text-violet-800",
  disputed: "bg-orange-100 text-orange-800",
  write_off: "bg-stone-200 text-stone-800",
};

export function InvoiceStatusBadge({ status }: { status: InvoiceStatus }) {
  return (
    <Badge className={statusClass[status]} variant="secondary">
      {formatStatus(status)}
    </Badge>
  );
}
