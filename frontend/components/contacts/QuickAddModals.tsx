"use client";

import { useState } from "react";
import { Plus } from "lucide-react";
import { toast } from "sonner";

import { CustomerForm, SupplierForm } from "@/components/contacts/ContactForms";
import { Button } from "@/components/ui/button";
import { useCreateCustomer, useCreateSupplier, type Customer, type Supplier } from "@/lib/contacts";

export function CustomerQuickAddModal({ onCreated }: { onCreated?: (customer: Customer) => void }) {
  const [open, setOpen] = useState(false);
  const createCustomer = useCreateCustomer();

  async function submit(payload: Record<string, unknown>) {
    const customer = await createCustomer.mutateAsync(payload);
    toast.success("Customer added");
    setOpen(false);
    onCreated?.(customer);
  }

  return (
    <>
      <Button onClick={() => setOpen(true)} type="button" variant="outline">
        <Plus className="h-4 w-4" />
        Quick add
      </Button>
      {open ? (
        <Modal title="Quick add customer" onClose={() => setOpen(false)}>
          <CustomerForm isSaving={createCustomer.isPending} onSubmit={submit} />
        </Modal>
      ) : null}
    </>
  );
}

export function SupplierQuickAddModal({ onCreated }: { onCreated?: (supplier: Supplier) => void }) {
  const [open, setOpen] = useState(false);
  const createSupplier = useCreateSupplier();

  async function submit(payload: Record<string, unknown>) {
    const supplier = await createSupplier.mutateAsync(payload);
    toast.success("Supplier added");
    setOpen(false);
    onCreated?.(supplier);
  }

  return (
    <>
      <Button onClick={() => setOpen(true)} type="button" variant="outline">
        <Plus className="h-4 w-4" />
        Quick add
      </Button>
      {open ? (
        <Modal title="Quick add supplier" onClose={() => setOpen(false)}>
          <SupplierForm isSaving={createSupplier.isPending} onSubmit={submit} />
        </Modal>
      ) : null}
    </>
  );
}

function Modal({ children, onClose, title }: { children: React.ReactNode; onClose: () => void; title: string }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/35 p-4">
      <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-md bg-background p-4 shadow-lg">
        <div className="mb-4 flex items-center justify-between gap-3">
          <h2 className="text-lg font-semibold">{title}</h2>
          <Button onClick={onClose} type="button" variant="outline">
            Close
          </Button>
        </div>
        {children}
      </div>
    </div>
  );
}
