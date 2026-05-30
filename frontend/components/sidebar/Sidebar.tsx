"use client";

import Link from "next/link";
import { Boxes, LayoutDashboard, Package, Receipt, Settings, Users } from "lucide-react";

import { Gate } from "@/components/auth/Gate";

const navItems = [
  { href: "/dashboard", label: "Dashboard", icon: LayoutDashboard, permission: "dashboard.view" },
  { href: "/packages", label: "Packages", icon: Package, permission: "packages.view" },
  { href: "/items", label: "Items", icon: Boxes, permission: "items.view" },
  { href: "/customers", label: "Customers", icon: Users, permission: "customers.view" },
  { href: "/invoices", label: "Invoices", icon: Receipt, permission: "sales_orders.view" },
  { href: "/preferences", label: "Preferences", icon: Settings, permission: "preferences.view" },
];

export function Sidebar() {
  return (
    <aside className="hidden min-h-screen w-64 border-r bg-sidebar px-4 py-5 md:block">
      <Link className="flex items-center gap-2 px-2 text-lg font-semibold" href="/dashboard">
        <Package className="h-5 w-5" />
        SortLot
      </Link>
      <nav className="mt-8 space-y-1">
        {navItems.map((item) => (
          <Gate key={item.href} permission={item.permission}>
            <Link
              className="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-muted-foreground transition hover:bg-accent hover:text-foreground"
              href={item.href}
            >
              <item.icon className="h-4 w-4" />
              {item.label}
            </Link>
          </Gate>
        ))}
      </nav>
    </aside>
  );
}
