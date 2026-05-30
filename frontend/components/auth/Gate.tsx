"use client";

import { type ReactNode } from "react";

import { useAuthStore } from "@/lib/stores/auth";

type GateProps = {
  permission: string;
  children: ReactNode;
};

export function Gate({ permission, children }: GateProps) {
  const hasPermission = useAuthStore((state) => state.hasPermission);

  if (!hasPermission(permission)) {
    return null;
  }

  return children;
}
