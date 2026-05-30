"use client";

import Link from "next/link";
import { useState } from "react";
import { Search } from "lucide-react";
import { toast } from "sonner";

import { ItemStatusBadge } from "@/components/items/ItemStatusBadge";
import { buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { itemGenders, itemSeasons, itemStatuses, useChangeItemStatus, useItems, type ItemStatus } from "@/lib/items";
import { formatStatus, usePreferenceOptions } from "@/lib/packages";

export default function ItemsPage() {
  const [filters, setFilters] = useState({
    search: "",
    status: "",
    season: "",
    gender: "",
    itemTypeId: "",
    pricingTierId: "",
  });
  const { data: items = [], isLoading } = useItems(filters);
  const { data: options } = usePreferenceOptions();
  const changeStatus = useChangeItemStatus();

  function updateFilter(field: keyof typeof filters, value: string) {
    setFilters((current) => ({ ...current, [field]: value }));
  }

  async function quickStatus(id: string, status: ItemStatus) {
    await changeStatus.mutateAsync({ id, status });
    toast.success("Item status updated");
  }

  return (
    <section className="space-y-5">
      <div>
        <h1 className="text-2xl font-semibold">Items</h1>
        <p className="mt-1 text-sm text-muted-foreground">Search and update every sorted item across packages.</p>
      </div>

      <div className="grid gap-3 rounded-md border bg-background p-3 lg:grid-cols-[1.5fr_repeat(5,1fr)]">
        <label className="relative">
          <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            onChange={(event) => updateFilter("search", event.target.value)}
            placeholder="Search SKU or barcode"
            value={filters.search}
          />
        </label>
        <FilterSelect label="Status" onChange={(value) => updateFilter("status", value)} value={filters.status}>
          {itemStatuses.map((status) => (
            <option key={status} value={status}>
              {formatStatus(status)}
            </option>
          ))}
        </FilterSelect>
        <FilterSelect label="Season" onChange={(value) => updateFilter("season", value)} value={filters.season}>
          {itemSeasons.map((season) => (
            <option key={season} value={season}>
              {formatStatus(season)}
            </option>
          ))}
        </FilterSelect>
        <FilterSelect label="Gender" onChange={(value) => updateFilter("gender", value)} value={filters.gender}>
          {itemGenders.map((gender) => (
            <option key={gender} value={gender}>
              {formatStatus(gender)}
            </option>
          ))}
        </FilterSelect>
        <FilterSelect label="Type" onChange={(value) => updateFilter("itemTypeId", value)} value={filters.itemTypeId}>
          {(options?.itemTypes ?? []).map((itemType) => (
            <option key={itemType.id} value={itemType.id}>
              {itemType.name}
            </option>
          ))}
        </FilterSelect>
        <FilterSelect label="Tier" onChange={(value) => updateFilter("pricingTierId", value)} value={filters.pricingTierId}>
          {(options?.pricingTiers ?? []).map((tier) => (
            <option key={tier.id} value={tier.id}>
              {tier.code}
            </option>
          ))}
        </FilterSelect>
      </div>

      <div className="overflow-hidden rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>SKU</TableHead>
              <TableHead>Barcode</TableHead>
              <TableHead>Package</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Tier</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Quick status</TableHead>
              <TableHead className="text-right">Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell className="py-8 text-center text-sm text-muted-foreground" colSpan={8}>
                  Loading items
                </TableCell>
              </TableRow>
            ) : null}
            {!isLoading && items.length === 0 ? (
              <TableRow>
                <TableCell className="py-8 text-center text-sm text-muted-foreground" colSpan={8}>
                  No items found
                </TableCell>
              </TableRow>
            ) : null}
            {items.map((item) => (
              <TableRow key={item.id}>
                <TableCell className="font-medium">{item.sku}</TableCell>
                <TableCell>{item.barcode ?? "-"}</TableCell>
                <TableCell>{item.package_reference ?? item.package_id}</TableCell>
                <TableCell>{item.item_type ?? item.item_type_id}</TableCell>
                <TableCell>{item.pricing_tier ?? item.pricing_tier_id}</TableCell>
                <TableCell>
                  <ItemStatusBadge status={item.status} />
                </TableCell>
                <TableCell>
                  <select
                    aria-label={`Change status for ${item.sku}`}
                    className="h-8 rounded-md border border-input bg-background px-2 text-sm"
                    disabled={changeStatus.isPending}
                    onChange={(event) => quickStatus(item.id, event.target.value as ItemStatus)}
                    value={item.status}
                  >
                    {itemStatuses.map((status) => (
                      <option key={status} value={status}>
                        {formatStatus(status)}
                      </option>
                    ))}
                  </select>
                </TableCell>
                <TableCell className="text-right">
                  <Link className={buttonVariants({ size: "sm", variant: "outline" })} href={`/items/${item.id}`}>
                    Open
                  </Link>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </section>
  );
}

function FilterSelect({
  children,
  label,
  onChange,
  value,
}: {
  children: React.ReactNode;
  label: string;
  onChange: (value: string) => void;
  value: string;
}) {
  return (
    <select
      aria-label={`Filter by ${label.toLowerCase()}`}
      className="h-9 rounded-md border border-input bg-background px-3 text-sm"
      onChange={(event) => onChange(event.target.value)}
      value={value}
    >
      <option value="">All {label.toLowerCase()}</option>
      {children}
    </select>
  );
}
