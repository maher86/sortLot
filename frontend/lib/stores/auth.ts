"use client";

import { create } from "zustand";

import { api } from "@/lib/api";

export type User = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  is_active: boolean;
  roles: string[];
  permissions: string[];
};

type AuthState = {
  user: User | null;
  permissions: string[];
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  hydrate: () => Promise<void>;
  hasPermission: (permission: string) => boolean;
};

function rememberToken(token: string) {
  window.localStorage.setItem("sortlot_token", token);
  document.cookie = `sortlot_auth=1; path=/; max-age=${60 * 60 * 24 * 7}; SameSite=Lax`;
}

function forgetToken() {
  window.localStorage.removeItem("sortlot_token");
  document.cookie = "sortlot_auth=; path=/; max-age=0; SameSite=Lax";
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  permissions: [],
  isLoading: false,
  async login(email, password) {
    set({ isLoading: true });

    try {
      const response = await api.post("/auth/login", { email, password });
      const user = response.data.data.user as User;

      rememberToken(response.data.data.token);
      set({ user, permissions: user.permissions, isLoading: false });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },
  async logout() {
    try {
      await api.post("/auth/logout");
    } finally {
      forgetToken();
      set({ user: null, permissions: [] });
    }
  },
  async hydrate() {
    const token = window.localStorage.getItem("sortlot_token");
    if (!token || get().user) {
      return;
    }

    set({ isLoading: true });

    try {
      const response = await api.get("/auth/me");
      const user = response.data.data.user as User;
      set({ user, permissions: user.permissions, isLoading: false });
    } catch {
      forgetToken();
      set({ user: null, permissions: [], isLoading: false });
    }
  },
  hasPermission(permission) {
    return get().permissions.includes(permission);
  },
}));
