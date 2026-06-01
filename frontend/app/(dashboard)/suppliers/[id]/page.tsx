"use client";

import { useParams } from "next/navigation";
import { useState } from "react";
import { toast } from "sonner";

import { SupplierForm } from "@/components/contacts/ContactForms";
import { Button } from "@/components/ui/button";
import { formatVatType, useSupplier, useUpdateSupplier } from "@/lib/contacts";

export default function SupplierDetailPage() {
  const params = useParams<{ id: string }>();
  const supplierId = params.id;
  const { data: supplier, isLoading } = useSupplier(supplierId);
  const updateSupplier = useUpdateSupplier(supplierId);
  const [editing, setEditing] = useState(false);

  if (isLoading || !supplier) {
    return <p className="text-sm text-muted-foreground">Loading supplier</p>;
  }

  async function submit(payload: Record<string, unknown>) {
    await updateSupplier.mutateAsync(payload);
    setEditing(false);
    toast.success("Supplier updated");
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{supplier.name}</h1>
          <p className="mt-1 text-sm text-muted-foreground">{supplier.email ?? supplier.phone ?? "No contact details"}</p>
        </div>
        <Button onClick={() => setEditing((value) => !value)} variant="outline">
          {editing ? "Close" : "Edit"}
        </Button>
      </div>

      <div className="rounded-md border bg-background p-4">
        <h2 className="text-base font-semibold">Supplier info</h2>
        <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-3">
          <Info label="VAT type" value={formatVatType(supplier.vat_type)} />
          <Info label="TRN" value={supplier.trn ?? "-"} />
          <Info label="Country" value={supplier.country} />
          <Info label="Bank" value={supplier.bank_name ?? "-"} />
          <Info label="IBAN" value={supplier.bank_iban ?? "-"} />
          <Info label="SWIFT" value={supplier.bank_swift ?? "-"} />
          <Info label="Address" value={supplier.address ?? "-"} />
        </dl>
      </div>

      {editing ? <SupplierForm isSaving={updateSupplier.isPending} onSubmit={submit} supplier={supplier} /> : null}
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
