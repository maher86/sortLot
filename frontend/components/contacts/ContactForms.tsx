"use client";

import { useState } from "react";
import { Save } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatVatType, vatTypes, type Customer, type Supplier } from "@/lib/contacts";

type CustomerFormValue = {
  name: string;
  contact_name: string;
  email: string;
  phone: string;
  country: string;
  emirate: string;
  address: string;
  vat_type: string;
  trn: string;
  credit_limit_fils: string;
  payment_terms_days: string;
  notes: string;
};

type SupplierFormValue = {
  name: string;
  contact_name: string;
  email: string;
  phone: string;
  country: string;
  address: string;
  vat_type: string;
  trn: string;
  bank_name: string;
  bank_iban: string;
  bank_swift: string;
  notes: string;
};

export function CustomerForm({
  customer,
  isSaving,
  onSubmit,
}: {
  customer?: Customer;
  isSaving?: boolean;
  onSubmit: (payload: Record<string, unknown>) => Promise<void>;
}) {
  const [form, setForm] = useState<CustomerFormValue>({
    name: customer?.name ?? "",
    contact_name: customer?.contact_name ?? "",
    email: customer?.email ?? "",
    phone: customer?.phone ?? "",
    country: customer?.country ?? "AE",
    emirate: customer?.emirate ?? "",
    address: customer?.address ?? "",
    vat_type: customer?.vat_type ?? "mainland",
    trn: customer?.trn ?? "",
    credit_limit_fils: customer?.credit_limit_fils?.toString() ?? "0",
    payment_terms_days: customer?.payment_terms_days?.toString() ?? "0",
    notes: customer?.notes ?? "",
  });

  function update(field: keyof CustomerFormValue, value: string) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await onSubmit({
      ...form,
      contact_name: form.contact_name || null,
      email: form.email || null,
      phone: form.phone || null,
      emirate: form.emirate || null,
      address: form.address || null,
      trn: form.trn || null,
      credit_limit_fils: Number(form.credit_limit_fils || 0),
      payment_terms_days: Number(form.payment_terms_days || 0),
      notes: form.notes || null,
    });
  }

  return (
    <form className="grid gap-4 rounded-md border bg-background p-4 sm:grid-cols-2" onSubmit={submit}>
      <TextField label="Name" onChange={(value) => update("name", value)} required value={form.name} />
      <TextField label="Contact name" onChange={(value) => update("contact_name", value)} value={form.contact_name} />
      <TextField label="Email" onChange={(value) => update("email", value)} type="email" value={form.email} />
      <TextField label="Phone" onChange={(value) => update("phone", value)} value={form.phone} />
      <TextField label="Country" onChange={(value) => update("country", value)} required value={form.country} />
      <TextField label="Emirate" onChange={(value) => update("emirate", value)} value={form.emirate} />
      <VatSelect onChange={(value) => update("vat_type", value)} value={form.vat_type} />
      <TextField label="TRN" onChange={(value) => update("trn", value)} value={form.trn} />
      <TextField label="Credit limit fils" onChange={(value) => update("credit_limit_fils", value)} type="number" value={form.credit_limit_fils} />
      <TextField label="Payment terms days" onChange={(value) => update("payment_terms_days", value)} type="number" value={form.payment_terms_days} />
      <TextArea label="Address" onChange={(value) => update("address", value)} value={form.address} />
      <TextArea label="Notes" onChange={(value) => update("notes", value)} value={form.notes} />
      <FormActions isSaving={isSaving} />
    </form>
  );
}

export function SupplierForm({
  isSaving,
  onSubmit,
  supplier,
}: {
  supplier?: Supplier;
  isSaving?: boolean;
  onSubmit: (payload: Record<string, unknown>) => Promise<void>;
}) {
  const [form, setForm] = useState<SupplierFormValue>({
    name: supplier?.name ?? "",
    contact_name: supplier?.contact_name ?? "",
    email: supplier?.email ?? "",
    phone: supplier?.phone ?? "",
    country: supplier?.country ?? "US",
    address: supplier?.address ?? "",
    vat_type: supplier?.vat_type ?? "international",
    trn: supplier?.trn ?? "",
    bank_name: supplier?.bank_name ?? "",
    bank_iban: supplier?.bank_iban ?? "",
    bank_swift: supplier?.bank_swift ?? "",
    notes: supplier?.notes ?? "",
  });

  function update(field: keyof SupplierFormValue, value: string) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await onSubmit({
      ...form,
      contact_name: form.contact_name || null,
      email: form.email || null,
      phone: form.phone || null,
      address: form.address || null,
      trn: form.trn || null,
      bank_name: form.bank_name || null,
      bank_iban: form.bank_iban || null,
      bank_swift: form.bank_swift || null,
      notes: form.notes || null,
    });
  }

  return (
    <form className="grid gap-4 rounded-md border bg-background p-4 sm:grid-cols-2" onSubmit={submit}>
      <TextField label="Name" onChange={(value) => update("name", value)} required value={form.name} />
      <TextField label="Contact name" onChange={(value) => update("contact_name", value)} value={form.contact_name} />
      <TextField label="Email" onChange={(value) => update("email", value)} type="email" value={form.email} />
      <TextField label="Phone" onChange={(value) => update("phone", value)} value={form.phone} />
      <TextField label="Country" onChange={(value) => update("country", value)} required value={form.country} />
      <VatSelect onChange={(value) => update("vat_type", value)} value={form.vat_type} />
      <TextField label="TRN" onChange={(value) => update("trn", value)} value={form.trn} />
      <TextField label="Bank name" onChange={(value) => update("bank_name", value)} value={form.bank_name} />
      <TextField label="IBAN" onChange={(value) => update("bank_iban", value)} value={form.bank_iban} />
      <TextField label="SWIFT" onChange={(value) => update("bank_swift", value)} value={form.bank_swift} />
      <TextArea label="Address" onChange={(value) => update("address", value)} value={form.address} />
      <TextArea label="Notes" onChange={(value) => update("notes", value)} value={form.notes} />
      <FormActions isSaving={isSaving} />
    </form>
  );
}

function TextField({
  label,
  onChange,
  required,
  type = "text",
  value,
}: {
  label: string;
  onChange: (value: string) => void;
  required?: boolean;
  type?: string;
  value: string;
}) {
  return (
    <label className="space-y-1 text-sm font-medium">
      {label}
      <Input onChange={(event) => onChange(event.target.value)} required={required} type={type} value={value} />
    </label>
  );
}

function TextArea({ label, onChange, value }: { label: string; onChange: (value: string) => void; value: string }) {
  return (
    <label className="space-y-1 text-sm font-medium">
      {label}
      <textarea className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" onChange={(event) => onChange(event.target.value)} value={value} />
    </label>
  );
}

function VatSelect({ onChange, value }: { onChange: (value: string) => void; value: string }) {
  return (
    <label className="space-y-1 text-sm font-medium">
      VAT type
      <select className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" onChange={(event) => onChange(event.target.value)} value={value}>
        {vatTypes.map((vatType) => (
          <option key={vatType} value={vatType}>
            {formatVatType(vatType)}
          </option>
        ))}
      </select>
    </label>
  );
}

function FormActions({ isSaving }: { isSaving?: boolean }) {
  return (
    <div className="sm:col-span-2">
      <Button disabled={isSaving} type="submit">
        <Save className="h-4 w-4" />
        Save
      </Button>
    </div>
  );
}
