"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { api } from "@/lib/api";

export type PreferenceMap = Record<string, string | null>;

export type PricingTier = {
  id: number;
  code: string;
  label: string;
  price_per_kg_fils: number | null;
  price_flat_fils: number | null;
  description: string | null;
  is_active: boolean;
  sort_order: number;
};

export type ItemTypeOption = {
  id: number;
  name: string;
  slug: string;
  is_active: boolean;
  sort_order: number;
};

export function usePreferences() {
  return useQuery({
    queryKey: ["preferences"],
    queryFn: async () => {
      const response = await api.get<{ data: PreferenceMap }>("/preferences");

      return response.data.data;
    },
  });
}

export function useUpdatePreferences() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: PreferenceMap) => {
      const response = await api.patch<{ data: PreferenceMap }>("/preferences", payload);

      return response.data.data;
    },
    onSuccess: (preferences) => queryClient.setQueryData(["preferences"], preferences),
  });
}

export function usePricingTiers() {
  return useQuery({
    queryKey: ["pricing-tiers"],
    queryFn: async () => {
      const response = await api.get<{ data: PricingTier[] }>("/preferences/pricing-tiers");

      return response.data.data;
    },
  });
}

export function useUpsertPricingTier() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Partial<PricingTier>) => {
      const endpoint = payload.id ? `/preferences/pricing-tiers/${payload.id}` : "/preferences/pricing-tiers";
      const method = payload.id ? api.patch : api.post;
      const response = await method<{ data: PricingTier }>(endpoint, payload);

      return response.data.data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["pricing-tiers"] }),
  });
}

export function useItemTypes() {
  return useQuery({
    queryKey: ["item-types"],
    queryFn: async () => {
      const response = await api.get<{ data: ItemTypeOption[] }>("/preferences/item-types");

      return response.data.data;
    },
  });
}

export function useUpsertItemType() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Partial<ItemTypeOption>) => {
      const endpoint = payload.id ? `/preferences/item-types/${payload.id}` : "/preferences/item-types";
      const method = payload.id ? api.patch : api.post;
      const response = await method<{ data: ItemTypeOption }>(endpoint, payload);

      return response.data.data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["item-types"] }),
  });
}
