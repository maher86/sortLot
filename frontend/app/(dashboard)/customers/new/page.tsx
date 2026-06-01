"use client";

import { toast } from "sonner";

import { CustomerForm } from "@/components/contacts/ContactForms";
import { useCreateCustomer } from "@/lib/contacts";

export default function NewCustomerPage() {
  const createCustomer = useCreateCustomer();

  async function submit(payload: Record<string, unknown>) {
    const customer = await createCustomer.mutateAsync(payload);
    toast.success("Customer created");
    window.location.assign(`/customers/${customer.id}`);
  }

  return (
    <section className="max-w-4xl space-y-5">
      <div>
        <h1 className="text-2xl font-semibold">New customer</h1>
        <p className="mt-1 text-sm text-muted-foreground">Create a buyer profile for sales orders and statements.</p>
      </div>
      <CustomerForm isSaving={createCustomer.isPending} onSubmit={submit} />
    </section>
  );
}
