"use client";

import Link from "next/link";
import { useState } from "react";
import { Plus, Search } from "lucide-react";

import { StatusBadge } from "@/components/packages/StatusBadge";
import { buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { formatStatus, packageStatuses, usePackages } from "@/lib/packages";

export default function PackagesPage() {
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const { data, isLoading } = usePackages({ search, status });
  const packages = data?.data ?? [];

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Packages</h1>
          <p className="mt-1 text-sm text-muted-foreground">Track lots from transit through sorting and shipment.</p>
        </div>
        <Link className={buttonVariants()} href="/packages/new">
            <Plus className="h-4 w-4" />
            New package
        </Link>
      </div>

      <div className="flex flex-col gap-3 rounded-md border bg-background p-3 sm:flex-row">
        <label className="relative flex-1">
          <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-9"
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Search reference"
            value={search}
          />
        </label>
        <select
          aria-label="Filter by status"
          className="h-9 rounded-md border border-input bg-background px-3 text-sm"
          onChange={(event) => setStatus(event.target.value)}
          value={status}
        >
          <option value="">All statuses</option>
          {packageStatuses.map((packageStatus) => (
            <option key={packageStatus} value={packageStatus}>
              {formatStatus(packageStatus)}
            </option>
          ))}
        </select>
      </div>

      <div className="overflow-hidden rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Reference</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Origin</TableHead>
              <TableHead>Weight</TableHead>
              <TableHead>Items</TableHead>
              <TableHead className="text-right">Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell className="py-8 text-center text-sm text-muted-foreground" colSpan={6}>
                  Loading packages
                </TableCell>
              </TableRow>
            ) : null}
            {!isLoading && packages.length === 0 ? (
              <TableRow>
                <TableCell className="py-8 text-center text-sm text-muted-foreground" colSpan={6}>
                  No packages found
                </TableCell>
              </TableRow>
            ) : null}
            {packages.map((sortlotPackage) => (
              <TableRow key={sortlotPackage.id}>
                <TableCell className="font-medium">{sortlotPackage.reference}</TableCell>
                <TableCell>
                  <StatusBadge status={sortlotPackage.status} />
                </TableCell>
                <TableCell>{sortlotPackage.origin_country}</TableCell>
                <TableCell>{sortlotPackage.weight_kg ?? "-"}</TableCell>
                <TableCell>{sortlotPackage.items_count}</TableCell>
                <TableCell className="text-right">
                  <Link className={buttonVariants({ size: "sm", variant: "outline" })} href={`/packages/${sortlotPackage.id}`}>
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
