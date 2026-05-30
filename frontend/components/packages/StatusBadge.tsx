import { Badge } from "@/components/ui/badge";
import { formatStatus, type PackageStatus } from "@/lib/packages";

const classes: Record<PackageStatus, string> = {
  in_transit: "border-sky-200 bg-sky-50 text-sky-700",
  at_port: "border-cyan-200 bg-cyan-50 text-cyan-700",
  in_customs: "border-amber-200 bg-amber-50 text-amber-700",
  in_warehouse: "border-blue-200 bg-blue-50 text-blue-700",
  sorting: "border-violet-200 bg-violet-50 text-violet-700",
  sorted: "border-emerald-200 bg-emerald-50 text-emerald-700",
  partially_shipped: "border-orange-200 bg-orange-50 text-orange-700",
  shipped: "border-lime-200 bg-lime-50 text-lime-700",
  closed: "border-slate-200 bg-slate-50 text-slate-700",
};

export function StatusBadge({ status }: { status: PackageStatus }) {
  return (
    <Badge className={classes[status]} variant="outline">
      {formatStatus(status)}
    </Badge>
  );
}
