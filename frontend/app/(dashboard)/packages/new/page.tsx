"use client";

import { useState } from "react";
import { Save } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useCreatePackage } from "@/lib/packages";

export default function NewPackagePage() {
  const createPackage = useCreatePackage();
  const [form, setForm] = useState({
    reference: "",
    origin_country: "US",
    destination_country: "AE",
    weight_kg: "",
    number_of_bags: "",
    notes: "",
  });

  function updateField(field: keyof typeof form, value: string) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const created = await createPackage.mutateAsync({
        reference: form.reference,
        origin_country: form.origin_country,
        destination_country: form.destination_country,
        weight_kg: form.weight_kg || null,
        number_of_bags: form.number_of_bags ? Number(form.number_of_bags) : null,
        notes: form.notes || null,
      });

      toast.success("Package created");
      window.location.assign(`/packages/${created.id}`);
    } catch {
      toast.error("Package could not be created");
    }
  }

  return (
    <section className="max-w-3xl space-y-5">
      <div>
        <h1 className="text-2xl font-semibold">New package</h1>
        <p className="mt-1 text-sm text-muted-foreground">Create the lot record before sorting starts.</p>
      </div>
      <form className="grid gap-4 rounded-md border bg-background p-4 sm:grid-cols-2" onSubmit={submit}>
        <label className="space-y-1 text-sm font-medium">
          Reference
          <Input
            onChange={(event) => updateField("reference", event.target.value)}
            placeholder="2026-PKG-001"
            required
            value={form.reference}
          />
        </label>
        <label className="space-y-1 text-sm font-medium">
          Origin country
          <Input onChange={(event) => updateField("origin_country", event.target.value)} required value={form.origin_country} />
        </label>
        <label className="space-y-1 text-sm font-medium">
          Destination country
          <Input onChange={(event) => updateField("destination_country", event.target.value)} value={form.destination_country} />
        </label>
        <label className="space-y-1 text-sm font-medium">
          Weight kg
          <Input onChange={(event) => updateField("weight_kg", event.target.value)} type="number" value={form.weight_kg} />
        </label>
        <label className="space-y-1 text-sm font-medium">
          Bags
          <Input onChange={(event) => updateField("number_of_bags", event.target.value)} type="number" value={form.number_of_bags} />
        </label>
        <label className="space-y-1 text-sm font-medium sm:col-span-2">
          Notes
          <textarea
            className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            onChange={(event) => updateField("notes", event.target.value)}
            value={form.notes}
          />
        </label>
        <div className="sm:col-span-2">
          <Button disabled={createPackage.isPending} type="submit">
            <Save className="h-4 w-4" />
            Save package
          </Button>
        </div>
      </form>
    </section>
  );
}
