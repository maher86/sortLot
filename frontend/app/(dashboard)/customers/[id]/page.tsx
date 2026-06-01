"use client";

import { useParams } from "next/navigation";
import { useState } from "react";
import { toast } from "sonner";

import { CustomerForm } from "@/components/contacts/ContactForms";
import { Button } from "@/components/ui/button";
import { formatFils, formatVatType, useCustomer, useCustomerStatement, useUpdateCustomer } from "@/lib/contacts";

export default function CustomerDetailPage() {
  const params = useParams<{ id: string }>();
  const customerId = params.id;
  const { data: customer, isLoading } = useCustomer(customerId);
  const { data: statement } = useCustomerStatement(customerId);
  const updateCustomer = useUpdateCustomer(customerId);
  const [editing, setEditing] = useState(false);

  if (isLoading || !customer) {
    return <p className="text-sm text-muted-foreground">Loading customer</p>;
  }

  async function submit(payload: Record<string, unknown>) {
    await updateCustomer.mutateAsync(payload);
    setEditing(false);
    toast.success("Customer updated");
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{customer.name}</h1>
          <p className="mt-1 text-sm text-muted-foreground">{customer.email ?? customer.phone ?? "No contact details"}</p>
        </div>
        <Button onClick={() => setEditing((value) => !value)} variant="outline">
          {editing ? "Close" : "Edit"}
        </Button>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <Metric label="Balance" value={formatFils(statement?.balance_fils ?? 0)} />
        <Metric label="Credit limit" value={formatFils(customer.credit_limit_fils)} />
        <Metric label="Available credit" value={formatFils(statement?.available_credit_fils ?? customer.credit_limit_fils)} />
      </div>

      <div className="rounded-md border bg-background p-4">
        <h2 className="text-base font-semibold">Customer info</h2>
        <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-3">
          <Info label="VAT type" value={formatVatType(customer.vat_type)} />
          <Info label="TRN" value={customer.trn ?? "-"} />
          <Info label="Payment terms" value={`${customer.payment_terms_days} days`} />
          <Info label="Country" value={customer.country} />
          <Info label="Emirate" value={customer.emirate ?? "-"} />
          <Info label="Address" value={customer.address ?? "-"} />
        </dl>
      </div>

      <div className="rounded-md border bg-background p-4">
        <h2 className="text-base font-semibold">Account statement</h2>
        <div className="mt-3 text-sm text-muted-foreground">
          {statement?.invoices.length ? `${statement.invoices.length} invoice(s)` : "No invoice history yet"}
        </div>
      </div>

      {editing ? <CustomerForm customer={customer} isSaving={updateCustomer.isPending} onSubmit={submit} /> : null}
    </section>
  );
}

function Metric({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-md border bg-background p-4">
      <div className="text-sm text-muted-foreground">{label}</div>
      <div className="mt-2 text-xl font-semibold">{value}</div>
    </div>
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
