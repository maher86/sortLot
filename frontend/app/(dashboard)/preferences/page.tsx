"use client";

import { useState } from "react";
import { Save } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import {
  useItemTypes,
  usePreferences,
  usePricingTiers,
  useUpdatePreferences,
  useUpsertItemType,
  useUpsertPricingTier,
  type ItemTypeOption,
  type PreferenceMap,
  type PricingTier,
} from "@/lib/preferences";

const tabs = ["Company", "Pricing Tiers", "Item Types", "Invoice Settings", "VAT Settings"] as const;

export default function PreferencesPage() {
  const [activeTab, setActiveTab] = useState<(typeof tabs)[number]>("Company");

  return (
    <section className="space-y-5">
      <div>
        <h1 className="text-2xl font-semibold">Preferences</h1>
        <p className="mt-1 text-sm text-muted-foreground">Maintain company, pricing, item, invoice, and VAT settings.</p>
      </div>
      <div className="flex flex-wrap gap-2 border-b pb-2">
        {tabs.map((tab) => (
          <button
            className={`rounded-md px-3 py-2 text-sm ${activeTab === tab ? "bg-primary text-primary-foreground" : "hover:bg-accent"}`}
            key={tab}
            onClick={() => setActiveTab(tab)}
            type="button"
          >
            {tab}
          </button>
        ))}
      </div>

      {activeTab === "Company" ? <CompanyPanel /> : null}
      {activeTab === "Pricing Tiers" ? <PricingTiersPanel /> : null}
      {activeTab === "Item Types" ? <ItemTypesPanel /> : null}
      {activeTab === "Invoice Settings" ? <InvoicePanel /> : null}
      {activeTab === "VAT Settings" ? <VatPanel /> : null}
    </section>
  );
}

function CompanyPanel() {
  const { data: preferences = {} } = usePreferences();
  const update = useUpdatePreferences();
  const [form, setForm] = useState<PreferenceMap>({});
  const values = { ...preferences, ...form };

  return (
    <PreferenceForm
      fields={[
        ["company_name", "Company name"],
        ["company_trn", "TRN"],
        ["default_currency", "Default currency"],
        ["company_logo_path", "Logo path"],
      ]}
      onChange={(key, value) => setForm((current) => ({ ...current, [key]: value }))}
      onSubmit={async () => {
        await update.mutateAsync(form);
        toast.success("Company preferences saved");
      }}
      values={values}
    />
  );
}

function InvoicePanel() {
  const { data: preferences = {} } = usePreferences();
  const update = useUpdatePreferences();
  const [form, setForm] = useState<PreferenceMap>({});
  const values = { ...preferences, ...form };

  return (
    <PreferenceForm
      fields={[
        ["invoice_prefix_sales", "Sales prefix"],
        ["invoice_prefix_purchase", "Purchase prefix"],
        ["payment_terms_days", "Payment terms days"],
        ["invoice_next_seq_sales", "Next sales sequence"],
      ]}
      onChange={(key, value) => setForm((current) => ({ ...current, [key]: value }))}
      onSubmit={async () => {
        await update.mutateAsync(form);
        toast.success("Invoice preferences saved");
      }}
      values={values}
    />
  );
}

function VatPanel() {
  const { data: preferences = {} } = usePreferences();
  const update = useUpdatePreferences();
  const [form, setForm] = useState<PreferenceMap>({});
  const values = { ...preferences, ...form };

  return (
    <PreferenceForm
      fields={[
        ["vat_rate_mainland", "Mainland VAT rate"],
        ["company_trn", "TRN"],
      ]}
      onChange={(key, value) => setForm((current) => ({ ...current, [key]: value }))}
      onSubmit={async () => {
        await update.mutateAsync(form);
        toast.success("VAT preferences saved");
      }}
      values={values}
    />
  );
}

function PreferenceForm({
  fields,
  onChange,
  onSubmit,
  values,
}: {
  fields: [string, string][];
  onChange: (key: string, value: string) => void;
  onSubmit: () => Promise<void>;
  values: PreferenceMap;
}) {
  return (
    <form
      className="grid max-w-3xl gap-4 rounded-md border bg-background p-4 sm:grid-cols-2"
      onSubmit={(event) => {
        event.preventDefault();
        void onSubmit();
      }}
    >
      {fields.map(([key, label]) => (
        <label className="space-y-1 text-sm font-medium" key={key}>
          {label}
          <Input onChange={(event) => onChange(key, event.target.value)} value={values[key] ?? ""} />
        </label>
      ))}
      <div className="sm:col-span-2">
        <Button type="submit">
          <Save className="h-4 w-4" />
          Save
        </Button>
      </div>
    </form>
  );
}

function PricingTiersPanel() {
  const { data: tiers = [] } = usePricingTiers();
  const upsert = useUpsertPricingTier();
  const [draft, setDraft] = useState<Partial<PricingTier>>({ code: "", label: "", price_per_kg_fils: 0, sort_order: 0 });

  async function save(payload: Partial<PricingTier>) {
    await upsert.mutateAsync(payload);
    toast.success("Pricing tier saved");
    setDraft({ code: "", label: "", price_per_kg_fils: 0, sort_order: 0 });
  }

  return (
    <div className="space-y-4 rounded-md border bg-background p-4">
      <div className="grid gap-3 sm:grid-cols-[1fr_1.5fr_1fr_auto]">
        <Input aria-label="Tier code" onChange={(event) => setDraft((current) => ({ ...current, code: event.target.value }))} placeholder="Code" value={draft.code ?? ""} />
        <Input aria-label="Tier label" onChange={(event) => setDraft((current) => ({ ...current, label: event.target.value }))} placeholder="Label" value={draft.label ?? ""} />
        <Input
          aria-label="Price per kg fils"
          onChange={(event) => setDraft((current) => ({ ...current, price_per_kg_fils: Number(event.target.value) }))}
          placeholder="Price/kg"
          type="number"
          value={draft.price_per_kg_fils ?? ""}
        />
        <Button onClick={() => save(draft)}>Add</Button>
      </div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Code</TableHead>
            <TableHead>Label</TableHead>
            <TableHead>Price/kg</TableHead>
            <TableHead>Active</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {tiers.map((tier) => (
            <TableRow key={tier.id}>
              <TableCell className="font-medium">{tier.code}</TableCell>
              <TableCell>
                <Input
                  aria-label={`Label for ${tier.code}`}
                  defaultValue={tier.label}
                  onBlur={(event) => {
                    if (event.target.value !== tier.label) {
                      void save({ id: tier.id, label: event.target.value });
                    }
                  }}
                />
              </TableCell>
              <TableCell>{tier.price_per_kg_fils ?? "-"}</TableCell>
              <TableCell>{tier.is_active ? "Yes" : "No"}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

function ItemTypesPanel() {
  const { data: itemTypes = [] } = useItemTypes();
  const upsert = useUpsertItemType();
  const [draft, setDraft] = useState<Partial<ItemTypeOption>>({ name: "" });

  async function save(payload: Partial<ItemTypeOption>) {
    await upsert.mutateAsync(payload);
    toast.success("Item type saved");
    setDraft({ name: "" });
  }

  return (
    <div className="space-y-4 rounded-md border bg-background p-4">
      <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
        <Input aria-label="Item type name" onChange={(event) => setDraft({ name: event.target.value })} placeholder="Type name" value={draft.name ?? ""} />
        <Button onClick={() => save(draft)}>Add</Button>
      </div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Slug</TableHead>
            <TableHead>Active</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {itemTypes.map((itemType) => (
            <TableRow key={itemType.id}>
              <TableCell className="font-medium">{itemType.name}</TableCell>
              <TableCell>{itemType.slug}</TableCell>
              <TableCell>{itemType.is_active ? "Yes" : "No"}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
