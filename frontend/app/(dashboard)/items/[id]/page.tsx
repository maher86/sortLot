"use client";

import { useParams } from "next/navigation";
import { Barcode, Printer } from "lucide-react";
import { toast } from "sonner";

import { ItemStatusBadge } from "@/components/items/ItemStatusBadge";
import { Button } from "@/components/ui/button";
import { itemStatuses, useChangeItemStatus, useItem, type ItemStatus } from "@/lib/items";
import { formatStatus } from "@/lib/packages";

export default function ItemDetailPage() {
  const params = useParams<{ id: string }>();
  const itemId = params.id;
  const { data: item, isLoading } = useItem(itemId);
  const changeStatus = useChangeItemStatus(itemId);

  async function updateStatus(status: ItemStatus) {
    await changeStatus.mutateAsync({ id: itemId, status });
    toast.success("Item status updated");
  }

  if (isLoading || !item) {
    return <p className="text-sm text-muted-foreground">Loading item</p>;
  }

  return (
    <section className="max-w-4xl space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{item.sku}</h1>
          <p className="mt-1 text-sm text-muted-foreground">Package {item.package_reference ?? item.package_id}</p>
        </div>
        <ItemStatusBadge status={item.status} />
      </div>

      <div className="grid gap-4 md:grid-cols-[1fr_1fr]">
        <div className="rounded-md border bg-background p-4">
          <h2 className="text-base font-semibold">Item detail</h2>
          <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
            <Info label="Barcode" value={item.barcode ?? "-"} />
            <Info label="Season" value={formatStatus(item.season)} />
            <Info label="Gender" value={formatStatus(item.gender)} />
            <Info label="Type" value={item.item_type ?? String(item.item_type_id)} />
            <Info label="Pricing tier" value={item.pricing_tier ?? String(item.pricing_tier_id)} />
            <Info label="Unit price" value={`${item.unit_price_fils} fils`} />
          </dl>
        </div>

        <div className="rounded-md border bg-background p-4">
          <h2 className="text-base font-semibold">Barcode and status</h2>
          <div className="mt-4 rounded-md border border-dashed p-4">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Barcode className="h-4 w-4" />
              SKU / Barcode
            </div>
            <p className="mt-2 break-all font-mono text-lg">{item.barcode ?? item.sku}</p>
          </div>
          <div className="mt-4 flex flex-col gap-3 sm:flex-row">
            <select
              aria-label="Change item status"
              className="h-9 rounded-md border border-input bg-background px-3 text-sm"
              disabled={changeStatus.isPending}
              onChange={(event) => updateStatus(event.target.value as ItemStatus)}
              value={item.status}
            >
              {itemStatuses.map((status) => (
                <option key={status} value={status}>
                  {formatStatus(status)}
                </option>
              ))}
            </select>
            <Button onClick={() => toast.info("Print hook ready")} variant="outline">
              <Printer className="h-4 w-4" />
              Print
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <dt className="text-muted-foreground">{label}</dt>
      <dd className="font-medium">{value}</dd>
    </div>
  );
}
