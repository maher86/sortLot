"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Barcode, Plus, Save, Search, Send, Trash2 } from "lucide-react";
import { toast } from "sonner";

import { CustomerQuickAddModal, SupplierQuickAddModal } from "@/components/contacts/QuickAddModals";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { api } from "@/lib/api";
import { formatFils, useCustomers, useSuppliers, type Customer, type Supplier } from "@/lib/contacts";
import { useItems, type SortLotItem } from "@/lib/items";
import { invoiceEndpoint, invoicePath, useCreateInvoice, type InvoiceKind } from "@/lib/invoices";

type DraftLine = {
  item_id?: string;
  description: string;
  quantity: string;
  unit_price_fils: string;
  discount_pct: string;
  sku?: string;
  meta?: string;
};

function today() {
  return new Date().toISOString().slice(0, 10);
}

function plusDays(days: number) {
  const date = new Date();
  date.setDate(date.getDate() + days);
  return date.toISOString().slice(0, 10);
}

export function InvoiceForm({ kind }: { kind: InvoiceKind }) {
  const router = useRouter();
  const [partyId, setPartyId] = useState("");
  const [partySearch, setPartySearch] = useState("");
  const [itemSearch, setItemSearch] = useState("");
  const [issueDate, setIssueDate] = useState(today());
  const [dueDate, setDueDate] = useState(plusDays(30));
  const [deliveryDate, setDeliveryDate] = useState(today());
  const [discountFils, setDiscountFils] = useState("0");
  const [discountPct, setDiscountPct] = useState("0");
  const [notes, setNotes] = useState("");
  const [terms, setTerms] = useState("");
  const [lines, setLines] = useState<DraftLine[]>([]);
  const [createdParty, setCreatedParty] = useState<Customer | Supplier | null>(null);

  const isSales = kind === "sales";
  const customers = useCustomers({ search: isSales ? partySearch : "" });
  const suppliers = useSuppliers({ search: isSales ? "" : partySearch });
  const items = useItems({ search: itemSearch, status: isSales ? "available" : "" });
  const createInvoice = useCreateInvoice(kind);

  const parties = useMemo(() => {
    const fetched = isSales ? customers.data ?? [] : suppliers.data ?? [];
    return createdParty && !fetched.some((party) => party.id === createdParty.id) ? [createdParty, ...fetched] : fetched;
  }, [createdParty, customers.data, isSales, suppliers.data]);
  const partyLabel = isSales ? "Customer" : "Supplier";

  const totals = useMemo(() => {
    const subtotal = lines.reduce((sum, line) => {
      const quantity = Number(line.quantity || 0);
      const unit = Number(line.unit_price_fils || 0);
      const discount = Number(line.discount_pct || 0);
      return sum + Math.round(quantity * unit * (1 - discount / 100));
    }, 0);
    const invoiceDiscount = Number(discountPct || 0) > 0 ? Math.round(subtotal * (Number(discountPct) / 100)) : Math.min(Number(discountFils || 0), subtotal);
    const party = parties.find((current) => current.id === partyId) as Customer | Supplier | undefined;
    const vatRate = isSales && party && party.vat_type === "mainland" ? 5 : 0;
    const taxable = Math.max(0, subtotal - invoiceDiscount);
    const vat = Math.round(taxable * (vatRate / 100));

    return { subtotal, invoiceDiscount, vatRate, vat, total: taxable + vat };
  }, [discountFils, discountPct, isSales, lines, parties, partyId]);

  function addItem(item: SortLotItem) {
    if (lines.some((line) => line.item_id === item.id)) {
      toast.info("Item already added");
      return;
    }

    setLines((current) => [
      ...current,
      {
        item_id: item.id,
        description: item.item_type ? `${item.item_type} ${item.season} ${item.gender}` : item.sku,
        quantity: "1",
        unit_price_fils: String(item.unit_price_fils),
        discount_pct: "0",
        sku: item.sku,
        meta: [item.season, item.gender, item.item_type].filter(Boolean).join(" / "),
      },
    ]);
  }

  function addManualLine() {
    setLines((current) => [...current, { description: "", quantity: "1", unit_price_fils: "0", discount_pct: "0" }]);
  }

  function updateLine(index: number, field: keyof DraftLine, value: string) {
    setLines((current) => current.map((line, lineIndex) => (lineIndex === index ? { ...line, [field]: value } : line)));
  }

  async function submit(confirm: boolean) {
    if (!partyId || lines.length === 0) {
      toast.error(`${partyLabel} and at least one line are required`);
      return;
    }

    const invoice = await createInvoice.mutateAsync({
      [isSales ? "customer_id" : "supplier_id"]: partyId,
      issue_date: issueDate,
      due_date: dueDate || null,
      delivery_date: deliveryDate || null,
      discount_fils: Number(discountFils || 0),
      discount_pct: Number(discountPct || 0),
      currency: "AED",
      exchange_rate: 1,
      notes: notes || null,
      terms: terms || null,
      lines: lines.map((line, index) => ({
        item_id: line.item_id,
        description: line.description,
        quantity: Number(line.quantity || 1),
        unit_price_fils: Number(line.unit_price_fils || 0),
        discount_pct: Number(line.discount_pct || 0),
        sort_order: index,
      })),
    });

    if (confirm) {
      await api.patch(`${invoiceEndpoint(kind)}/${invoice.id}/confirm`);
    }

    toast.success(confirm ? "Invoice confirmed" : "Draft saved");
    router.push(`${invoicePath(kind)}/${invoice.id}`);
  }

  return (
    <section className="space-y-5">
      <div>
        <h1 className="text-2xl font-semibold">{isSales ? "New sales order" : "New purchase order"}</h1>
        <p className="mt-1 text-sm text-muted-foreground">Build invoice lines, review VAT, then save or confirm.</p>
      </div>

      <div className="grid gap-4 rounded-md border bg-background p-4 lg:grid-cols-4">
        <label className="space-y-1 text-sm font-medium lg:col-span-2">
          {partyLabel}
          <div className="flex gap-2">
            <select aria-label={`${partyLabel} select`} className="h-9 min-w-0 flex-1 rounded-md border border-input bg-background px-3 text-sm" onChange={(event) => setPartyId(event.target.value)} value={partyId}>
              <option value="">Select {partyLabel.toLowerCase()}</option>
              {parties.map((party) => (
                <option key={party.id} value={party.id}>
                  {party.name}
                </option>
              ))}
            </select>
            {isSales ? (
              <CustomerQuickAddModal onCreated={(customer) => {
                setCreatedParty(customer);
                setPartyId(customer.id);
              }} />
            ) : (
              <SupplierQuickAddModal onCreated={(supplier) => {
                setCreatedParty(supplier);
                setPartyId(supplier.id);
              }} />
            )}
          </div>
        </label>
        <label className="space-y-1 text-sm font-medium">
          Search {partyLabel.toLowerCase()}
          <Input onChange={(event) => setPartySearch(event.target.value)} placeholder={`${partyLabel} name`} value={partySearch} />
        </label>
        <label className="space-y-1 text-sm font-medium">
          Issue date
          <Input onChange={(event) => setIssueDate(event.target.value)} type="date" value={issueDate} />
        </label>
        <label className="space-y-1 text-sm font-medium">
          Due date
          <Input onChange={(event) => setDueDate(event.target.value)} type="date" value={dueDate} />
        </label>
        <label className="space-y-1 text-sm font-medium">
          Delivery date
          <Input onChange={(event) => setDeliveryDate(event.target.value)} type="date" value={deliveryDate} />
        </label>
      </div>

      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div className="space-y-4">
          <div className="rounded-md border bg-background p-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <h2 className="font-semibold">Line items</h2>
              <Button onClick={addManualLine} type="button" variant="outline">
                <Plus className="h-4 w-4" />
                Manual line
              </Button>
            </div>
            <label className="mt-4 block">
              <span className="sr-only">Search items</span>
              <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input className="pl-9" onChange={(event) => setItemSearch(event.target.value)} placeholder="Search SKU, barcode, or description" value={itemSearch} />
              </div>
            </label>
            {itemSearch ? (
              <div className="mt-3 max-h-56 overflow-auto rounded-md border">
                {items.data?.slice(0, 8).map((item) => (
                  <button className="flex w-full items-center justify-between gap-3 border-b px-3 py-2 text-left text-sm last:border-b-0 hover:bg-accent" key={item.id} onClick={() => addItem(item)} type="button">
                    <span>
                      <span className="font-medium">{item.sku}</span>
                      <span className="ml-2 text-muted-foreground">{[item.season, item.gender, item.item_type].filter(Boolean).join(" / ")}</span>
                    </span>
                    <span className="text-muted-foreground">{formatFils(item.unit_price_fils)}</span>
                  </button>
                ))}
              </div>
            ) : null}

            <div className="mt-4 overflow-hidden rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Item</TableHead>
                    <TableHead>Qty</TableHead>
                    <TableHead>Unit fils</TableHead>
                    <TableHead>Disc %</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead />
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {lines.length === 0 ? (
                    <TableRow>
                      <TableCell className="py-8 text-center text-muted-foreground" colSpan={6}>
                        No lines added
                      </TableCell>
                    </TableRow>
                  ) : null}
                  {lines.map((line, index) => {
                    const lineTotal = Math.round(Number(line.quantity || 0) * Number(line.unit_price_fils || 0) * (1 - Number(line.discount_pct || 0) / 100));

                    return (
                      <TableRow key={`${line.item_id ?? "manual"}-${index}`}>
                        <TableCell className="min-w-56">
                          <div className="flex items-center gap-2">
                            {line.sku ? <Barcode className="h-4 w-4 text-muted-foreground" /> : null}
                            <Input aria-label={`Line ${index + 1} description`} onChange={(event) => updateLine(index, "description", event.target.value)} value={line.description} />
                          </div>
                          {line.meta ? <div className="mt-1 text-xs text-muted-foreground">{line.sku} / {line.meta}</div> : null}
                        </TableCell>
                        <TableCell><Input aria-label={`Line ${index + 1} quantity`} className="w-24" onChange={(event) => updateLine(index, "quantity", event.target.value)} type="number" value={line.quantity} /></TableCell>
                        <TableCell><Input aria-label={`Line ${index + 1} unit price fils`} className="w-32" onChange={(event) => updateLine(index, "unit_price_fils", event.target.value)} type="number" value={line.unit_price_fils} /></TableCell>
                        <TableCell><Input aria-label={`Line ${index + 1} discount`} className="w-24" onChange={(event) => updateLine(index, "discount_pct", event.target.value)} type="number" value={line.discount_pct} /></TableCell>
                        <TableCell>{formatFils(lineTotal)}</TableCell>
                        <TableCell>
                          <Button aria-label={`Remove line ${index + 1}`} onClick={() => setLines((current) => current.filter((_, lineIndex) => lineIndex !== index))} size="icon-sm" type="button" variant="ghost">
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </div>
          </div>

          <div className="grid gap-4 rounded-md border bg-background p-4 sm:grid-cols-2">
            <label className="space-y-1 text-sm font-medium">
              Notes
              <textarea className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" onChange={(event) => setNotes(event.target.value)} value={notes} />
            </label>
            <label className="space-y-1 text-sm font-medium">
              Terms
              <textarea className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" onChange={(event) => setTerms(event.target.value)} value={terms} />
            </label>
          </div>
        </div>

        <aside className="space-y-4 rounded-md border bg-background p-4">
          <h2 className="font-semibold">Totals</h2>
          <label className="space-y-1 text-sm font-medium">
            Flat discount fils
            <Input onChange={(event) => setDiscountFils(event.target.value)} type="number" value={discountFils} />
          </label>
          <label className="space-y-1 text-sm font-medium">
            Discount %
            <Input onChange={(event) => setDiscountPct(event.target.value)} type="number" value={discountPct} />
          </label>
          <dl className="space-y-2 text-sm">
            <Row label="Subtotal" value={formatFils(totals.subtotal)} />
            <Row label="Discount" value={formatFils(totals.invoiceDiscount)} />
            <Row label={`VAT ${totals.vatRate}%`} value={formatFils(totals.vat)} muted={totals.vatRate === 0} />
            <Row label="Total" value={formatFils(totals.total)} strong />
          </dl>
          <div className="flex flex-col gap-2">
            <Button disabled={createInvoice.isPending} onClick={() => submit(false)} type="button" variant="outline">
              <Save className="h-4 w-4" />
              Save draft
            </Button>
            <Button disabled={createInvoice.isPending} onClick={() => submit(true)} type="button">
              <Send className="h-4 w-4" />
              Confirm
            </Button>
          </div>
        </aside>
      </div>
    </section>
  );
}

function Row({ label, muted, strong, value }: { label: string; muted?: boolean; strong?: boolean; value: string }) {
  return (
    <div className={`flex items-center justify-between ${muted ? "text-muted-foreground" : ""} ${strong ? "border-t pt-2 text-base font-semibold" : ""}`}>
      <dt>{label}</dt>
      <dd>{value}</dd>
    </div>
  );
}
