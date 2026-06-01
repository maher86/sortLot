"use client";

import { toast } from "sonner";

import { SupplierForm } from "@/components/contacts/ContactForms";
import { useCreateSupplier } from "@/lib/contacts";

export default function NewSupplierPage() {
  const createSupplier = useCreateSupplier();

  async function submit(payload: Record<string, unknown>) {
    const supplier = await createSupplier.mutateAsync(payload);
    toast.success("Supplier created");
    window.location.assign(`/suppliers/${supplier.id}`);
  }

  return (
    <section className="max-w-4xl space-y-5">
      <div>
        <h1 className="text-2xl font-semibold">New supplier</h1>
        <p className="mt-1 text-sm text-muted-foreground">Create a source profile for packages and purchase orders.</p>
      </div>
      <SupplierForm isSaving={createSupplier.isPending} onSubmit={submit} />
    </section>
  );
}
