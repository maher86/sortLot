"use client";

import { type ReactNode, useEffect } from "react";
import { LogOut } from "lucide-react";

import { Sidebar } from "@/components/sidebar/Sidebar";
import { Button } from "@/components/ui/button";
import { useAuthStore } from "@/lib/stores/auth";

export default function DashboardLayout({ children }: { children: ReactNode }) {
  const user = useAuthStore((state) => state.user);
  const hydrate = useAuthStore((state) => state.hydrate);
  const logout = useAuthStore((state) => state.logout);

  useEffect(() => {
    void hydrate();
  }, [hydrate]);

  async function handleLogout() {
    await logout();
    window.location.assign("/login");
  }

  return (
    <div className="min-h-screen bg-background text-foreground">
      <div className="flex">
        <Sidebar />
        <main className="min-h-screen flex-1">
          <header className="flex h-16 items-center justify-between border-b px-6">
            <div>
              <p className="text-sm text-muted-foreground">Signed in as</p>
              <p className="text-sm font-medium">{user?.name ?? "Loading"}</p>
            </div>
            <Button aria-label="Log out" disabled={!user} onClick={handleLogout} size="icon" variant="ghost">
              <LogOut className="h-4 w-4" />
            </Button>
          </header>
          <div className="p-6">{children}</div>
        </main>
      </div>
    </div>
  );
}
