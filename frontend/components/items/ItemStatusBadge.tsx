import { Badge } from "@/components/ui/badge";
import { formatStatus } from "@/lib/packages";
import { type ItemStatus } from "@/lib/items";

const classes: Record<ItemStatus, string> = {
  available: "border-emerald-200 bg-emerald-50 text-emerald-700",
  reserved: "border-amber-200 bg-amber-50 text-amber-700",
  sold: "border-sky-200 bg-sky-50 text-sky-700",
  returned: "border-violet-200 bg-violet-50 text-violet-700",
  damaged: "border-rose-200 bg-rose-50 text-rose-700",
  missing: "border-slate-200 bg-slate-50 text-slate-700",
};

export function ItemStatusBadge({ status }: { status: ItemStatus }) {
  return (
    <Badge className={classes[status]} variant="outline">
      {formatStatus(status)}
    </Badge>
  );
}
