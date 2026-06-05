"use client";

import { useParams } from "next/navigation";
import { useMemo, useState } from "react";
import { Boxes, CheckCircle2, Pencil, Plus, Play, Save, Trash2 } from "lucide-react";
import { toast } from "sonner";

import { StatusBadge } from "@/components/packages/StatusBadge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import {
  formatStatus,
  packageStatuses,
  useBulkCreateItems,
  useChangePackageStatus,
  useDeletePackage,
  usePackage,
  usePackageItems,
  usePreferenceOptions,
  useUpdatePackage,
} from "@/lib/packages";

const seasons = ["summer", "winter", "spring", "general"];
const genders = ["man", "woman", "girl", "boy"];
const mutableStatuses = ["in_transit", "at_port", "in_customs", "in_warehouse"];

export default function PackageDetailPage() {
  const params = useParams<{ id: string }>();
  const packageId = params.id;
  const { data: sortlotPackage, isLoading } = usePackage(packageId);
  const { data: items = [] } = usePackageItems(packageId);
  const { data: options } = usePreferenceOptions();
  const changeStatus = useChangePackageStatus(packageId);
  const bulkCreate = useBulkCreateItems(packageId);
  const updatePackage = useUpdatePackage(packageId);
  const deletePackage = useDeletePackage();
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [editForm, setEditForm] = useState({
    reference: "",
    origin_country: "",
    destination_country: "",
    weight_kg: "",
    number_of_bags: "",
    notes: "",
  });
  const [itemForm, setItemForm] = useState({
    item_type_id: "",
    pricing_tier_id: "",
    season: "general",
    gender: "man",
    barcode: "",
    unit_price_fils: "1000",
  });

  const canMutateDetails = sortlotPackage ? mutableStatuses.includes(sortlotPackage.status) : false;
  const canStartSorting = sortlotPackage ? mutableStatuses.includes(sortlotPackage.status) : false;
  const canFinishSorting = sortlotPackage?.status === "sorting" && items.length > 0;
  const canDelete = canMutateDetails && items.length === 0;
  const timeline = useMemo(
    () =>
      packageStatuses.map((status) => ({
        status,
        isActive: sortlotPackage?.status === status,
        isPast: sortlotPackage ? packageStatuses.indexOf(status) < packageStatuses.indexOf(sortlotPackage.status) : false,
      })),
    [sortlotPackage],
  );

  async function startSorting() {
    try {
      await changeStatus.mutateAsync("sorting");
      toast.success("Sorting started");
    } catch {
      toast.error("Sorting could not be started");
    }
  }

  async function finishSorting() {
    try {
      await changeStatus.mutateAsync("sorted");
      toast.success("Package marked as sorted");
    } catch {
      toast.error("Package could not be marked as sorted");
    }
  }

  function openEdit() {
    if (!sortlotPackage) {
      return;
    }

    setEditForm({
      reference: sortlotPackage.reference,
      origin_country: sortlotPackage.origin_country,
      destination_country: sortlotPackage.destination_country,
      weight_kg: sortlotPackage.weight_kg ?? "",
      number_of_bags: sortlotPackage.number_of_bags?.toString() ?? "",
      notes: sortlotPackage.notes ?? "",
    });
    setIsEditOpen(true);
  }

  async function savePackage(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      await updatePackage.mutateAsync({
        reference: editForm.reference,
        origin_country: editForm.origin_country,
        destination_country: editForm.destination_country,
        weight_kg: editForm.weight_kg || null,
        number_of_bags: editForm.number_of_bags ? Number(editForm.number_of_bags) : null,
        notes: editForm.notes || null,
      });
      setIsEditOpen(false);
      toast.success("Package updated");
    } catch {
      toast.error("Package can only be updated before sorting starts");
    }
  }

  async function removePackage() {
    if (!sortlotPackage || !window.confirm(`Delete package ${sortlotPackage.reference}?`)) {
      return;
    }

    try {
      await deletePackage.mutateAsync(sortlotPackage.id);
      toast.success("Package deleted");
      window.location.assign("/packages");
    } catch {
      toast.error("Package can only be deleted before sorting starts and before items are added");
    }
  }

  async function addItem(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    await bulkCreate.mutateAsync([
      {
        item_type_id: Number(itemForm.item_type_id),
        pricing_tier_id: Number(itemForm.pricing_tier_id),
        season: itemForm.season,
        gender: itemForm.gender,
        barcode: itemForm.barcode || null,
        unit_price_fils: Number(itemForm.unit_price_fils),
      },
    ]);

    toast.success("Item added");
    setIsAddOpen(false);
    setItemForm((current) => ({ ...current, barcode: "" }));
  }

  if (isLoading || !sortlotPackage) {
    return <p className="text-sm text-muted-foreground">Loading package</p>;
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{sortlotPackage.reference}</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            {sortlotPackage.origin_country} to {sortlotPackage.destination_country}
          </p>
        </div>
        <div className="flex gap-2">
          <Button disabled={!canMutateDetails || updatePackage.isPending} onClick={openEdit} variant="outline">
            <Pencil className="h-4 w-4" />
            Edit
          </Button>
          <Button disabled={!canDelete || deletePackage.isPending} onClick={removePackage} variant="outline">
            <Trash2 className="h-4 w-4" />
            Delete
          </Button>
          <Button disabled={!canStartSorting || changeStatus.isPending} onClick={startSorting} variant="outline">
            <Play className="h-4 w-4" />
            Start sorting
          </Button>
          <Button onClick={() => setIsAddOpen(true)}>
            <Plus className="h-4 w-4" />
            Add items
          </Button>
        </div>
      </div>

      <div className="rounded-md border bg-background p-4">
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 className="text-base font-semibold">Next action</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              {sortlotPackage.status === "sorting"
                ? "Sorting is open. Add the sorted items, then mark the package as sorted."
                : sortlotPackage.status === "sorted"
                  ? "Sorting is complete. Available items can now be used in sales orders."
                  : "Move the package to sorting when it arrives in the warehouse."}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            {sortlotPackage.status === "sorting" ? (
              <>
                <Button onClick={() => setIsAddOpen(true)} variant="outline">
                  <Plus className="h-4 w-4" />
                  Add items
                </Button>
                <Button disabled={!canFinishSorting || changeStatus.isPending} onClick={finishSorting}>
                  <CheckCircle2 className="h-4 w-4" />
                  Mark sorted
                </Button>
              </>
            ) : null}
            {canStartSorting ? (
              <Button disabled={changeStatus.isPending} onClick={startSorting}>
                <Play className="h-4 w-4" />
                Start sorting
              </Button>
            ) : null}
          </div>
        </div>
      </div>

      <div className="grid gap-4 lg:grid-cols-[1fr_2fr]">
        <div className="rounded-md border bg-background p-4">
          <div className="flex items-center justify-between">
            <h2 className="text-base font-semibold">Package info</h2>
            <StatusBadge status={sortlotPackage.status} />
          </div>
          <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
              <dt className="text-muted-foreground">Weight</dt>
              <dd className="font-medium">{sortlotPackage.weight_kg ?? "-"}</dd>
            </div>
            <div>
              <dt className="text-muted-foreground">Bags</dt>
              <dd className="font-medium">{sortlotPackage.number_of_bags ?? "-"}</dd>
            </div>
            <div>
              <dt className="text-muted-foreground">Items</dt>
              <dd className="font-medium">{sortlotPackage.items_count}</dd>
            </div>
            <div>
              <dt className="text-muted-foreground">Available</dt>
              <dd className="font-medium">{sortlotPackage.available_items_count}</dd>
            </div>
          </dl>
        </div>

        <div className="rounded-md border bg-background p-4">
          <h2 className="text-base font-semibold">Status timeline</h2>
          <ol className="mt-4 grid gap-2 sm:grid-cols-3 xl:grid-cols-5">
            {timeline.map((step) => (
              <li
                className={`rounded-md border px-3 py-2 text-sm ${
                  step.isActive || step.isPast ? "border-emerald-200 bg-emerald-50 text-emerald-800" : "text-muted-foreground"
                }`}
                key={step.status}
              >
                {formatStatus(step.status)}
              </li>
            ))}
          </ol>
        </div>
      </div>

      <div className="rounded-md border bg-background">
        <div className="flex items-center gap-2 border-b px-4 py-3">
          <Boxes className="h-4 w-4" />
          <h2 className="text-base font-semibold">Items</h2>
        </div>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>SKU</TableHead>
              <TableHead>Barcode</TableHead>
              <TableHead>Season</TableHead>
              <TableHead>Gender</TableHead>
              <TableHead>Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {items.length === 0 ? (
              <TableRow>
                <TableCell className="py-8 text-center text-sm text-muted-foreground" colSpan={5}>
                  No items yet
                </TableCell>
              </TableRow>
            ) : null}
            {items.map((item) => (
              <TableRow key={item.id}>
                <TableCell className="font-medium">{item.sku}</TableCell>
                <TableCell>{item.barcode ?? "-"}</TableCell>
                <TableCell>{formatStatus(item.season)}</TableCell>
                <TableCell>{formatStatus(item.gender)}</TableCell>
                <TableCell>{formatStatus(item.status)}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {isEditOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/35 p-4">
          <form className="w-full max-w-2xl rounded-md bg-background p-4 shadow-lg" onSubmit={savePackage}>
            <h2 className="text-lg font-semibold">Edit package</h2>
            <div className="mt-4 grid gap-3 sm:grid-cols-2">
              <label className="space-y-1 text-sm font-medium">
                Reference
                <Input
                  onChange={(event) => setEditForm((current) => ({ ...current, reference: event.target.value }))}
                  required
                  value={editForm.reference}
                />
              </label>
              <label className="space-y-1 text-sm font-medium">
                Origin country
                <Input
                  onChange={(event) => setEditForm((current) => ({ ...current, origin_country: event.target.value }))}
                  required
                  value={editForm.origin_country}
                />
              </label>
              <label className="space-y-1 text-sm font-medium">
                Destination country
                <Input
                  onChange={(event) => setEditForm((current) => ({ ...current, destination_country: event.target.value }))}
                  value={editForm.destination_country}
                />
              </label>
              <label className="space-y-1 text-sm font-medium">
                Weight kg
                <Input
                  onChange={(event) => setEditForm((current) => ({ ...current, weight_kg: event.target.value }))}
                  type="number"
                  value={editForm.weight_kg}
                />
              </label>
              <label className="space-y-1 text-sm font-medium">
                Bags
                <Input
                  onChange={(event) => setEditForm((current) => ({ ...current, number_of_bags: event.target.value }))}
                  type="number"
                  value={editForm.number_of_bags}
                />
              </label>
              <label className="space-y-1 text-sm font-medium sm:col-span-2">
                Notes
                <textarea
                  className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  onChange={(event) => setEditForm((current) => ({ ...current, notes: event.target.value }))}
                  value={editForm.notes}
                />
              </label>
            </div>
            <div className="mt-4 flex justify-end gap-2">
              <Button onClick={() => setIsEditOpen(false)} type="button" variant="outline">
                Cancel
              </Button>
              <Button disabled={updatePackage.isPending} type="submit">
                <Save className="h-4 w-4" />
                Save
              </Button>
            </div>
          </form>
        </div>
      ) : null}

      {isAddOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/35 p-4">
          <form className="w-full max-w-lg rounded-md bg-background p-4 shadow-lg" onSubmit={addItem}>
            <h2 className="text-lg font-semibold">Add item</h2>
            <div className="mt-4 grid gap-3 sm:grid-cols-2">
              <label className="space-y-1 text-sm font-medium">
                Item type
                <select
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                  onChange={(event) => setItemForm((current) => ({ ...current, item_type_id: event.target.value }))}
                  required
                  value={itemForm.item_type_id}
                >
                  <option value="">Select type</option>
                  {(options?.itemTypes ?? []).map((itemType) => (
                    <option key={itemType.id} value={itemType.id}>
                      {itemType.name}
                    </option>
                  ))}
                </select>
              </label>
              <label className="space-y-1 text-sm font-medium">
                Pricing tier
                <select
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                  onChange={(event) => setItemForm((current) => ({ ...current, pricing_tier_id: event.target.value }))}
                  required
                  value={itemForm.pricing_tier_id}
                >
                  <option value="">Select tier</option>
                  {(options?.pricingTiers ?? []).map((tier) => (
                    <option key={tier.id} value={tier.id}>
                      {tier.code} {tier.label}
                    </option>
                  ))}
                </select>
              </label>
              <label className="space-y-1 text-sm font-medium">
                Season
                <select
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                  onChange={(event) => setItemForm((current) => ({ ...current, season: event.target.value }))}
                  value={itemForm.season}
                >
                  {seasons.map((season) => (
                    <option key={season} value={season}>
                      {formatStatus(season)}
                    </option>
                  ))}
                </select>
              </label>
              <label className="space-y-1 text-sm font-medium">
                Gender
                <select
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                  onChange={(event) => setItemForm((current) => ({ ...current, gender: event.target.value }))}
                  value={itemForm.gender}
                >
                  {genders.map((gender) => (
                    <option key={gender} value={gender}>
                      {formatStatus(gender)}
                    </option>
                  ))}
                </select>
              </label>
              <label className="space-y-1 text-sm font-medium">
                Barcode
                <Input onChange={(event) => setItemForm((current) => ({ ...current, barcode: event.target.value }))} value={itemForm.barcode} />
              </label>
              <label className="space-y-1 text-sm font-medium">
                Unit price fils
                <Input
                  onChange={(event) => setItemForm((current) => ({ ...current, unit_price_fils: event.target.value }))}
                  required
                  type="number"
                  value={itemForm.unit_price_fils}
                />
              </label>
            </div>
            <div className="mt-4 flex justify-end gap-2">
              <Button onClick={() => setIsAddOpen(false)} type="button" variant="outline">
                Cancel
              </Button>
              <Button disabled={bulkCreate.isPending} type="submit">
                Add item
              </Button>
            </div>
          </form>
        </div>
      ) : null}
    </section>
  );
}
