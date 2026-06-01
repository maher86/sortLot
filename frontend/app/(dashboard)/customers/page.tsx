"use client";

import Link from "next/link";
import { useState } from "react";
import { Plus, Search } from "lucide-react";

import { CustomerQuickAddModal } from "@/components/contacts/QuickAddModals";
import { buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { formatFils, formatVatType, useCustomers, vatTypes } from "@/lib/contacts";

export default function CustomersPage() {
  const [search, setSearch] = useState("");
  const [vatType, setVatType] = useState("");
  const { data: customers = [], isLoading } = useCustomers({ search, vatType });

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Customers</h1>
          <p className="mt-1 text-sm text-muted-foreground">Manage buyers, balances, VAT treatment, and payment terms.</p>
        </div>
        <div className="flex gap-2">
          <CustomerQuickAddModal />
          <Link className={buttonVariants()} href="/customers/new">
            <Plus className="h-4 w-4" />
            New customer
          </Link>
        </div>
      </div>

      <div className="flex flex-col gap-3 rounded-md border bg-background p-3 sm:flex-row">
        <label className="relative flex-1">
          <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input className="pl-9" onChange={(event) => setSearch(event.target.value)} placeholder="Search customers" value={search} />
        </label>
        <select aria-label="Filter by VAT type" className="h-9 rounded-md border border-input bg-background px-3 text-sm" onChange={(event) => setVatType(event.target.value)} value={vatType}>
          <option value="">All VAT types</option>
          {vatTypes.map((type) => (
            <option key={type} value={type}>
              {formatVatType(type)}
            </option>
          ))}
        </select>
      </div>

      <div className="overflow-hidden rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Contact</TableHead>
              <TableHead>VAT</TableHead>
              <TableHead>Credit limit</TableHead>
              <TableHead>Terms</TableHead>
              <TableHead className="text-right">Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell className="py-8 text-center text-sm text-muted-foreground" colSpan={6}>
                  Loading customers
                </TableCell>
              </TableRow>
            ) : null}
            {!isLoading && customers.length === 0 ? (
              <TableRow>
                <TableCell className="py-8 text-center text-sm text-muted-foreground" colSpan={6}>
                  No customers found
                </TableCell>
              </TableRow>
            ) : null}
            {customers.map((customer) => (
              <TableRow key={customer.id}>
                <TableCell className="font-medium">{customer.name}</TableCell>
                <TableCell>{customer.email ?? customer.phone ?? "-"}</TableCell>
                <TableCell>{formatVatType(customer.vat_type)}</TableCell>
                <TableCell>{formatFils(customer.credit_limit_fils)}</TableCell>
                <TableCell>{customer.payment_terms_days} days</TableCell>
                <TableCell className="text-right">
                  <Link className={buttonVariants({ size: "sm", variant: "outline" })} href={`/customers/${customer.id}`}>
                    Open
                  </Link>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </section>
  );
}
