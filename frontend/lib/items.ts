"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { api } from "@/lib/api";

export type ItemStatus = "available" | "reserved" | "sold" | "returned" | "damaged" | "missing";

export type SortLotItem = {
  id: string;
  package_id: string;
  package_reference?: string;
  sku: string;
  barcode: string | null;
  season: string;
  gender: string;
  item_type_id: number;
  item_type?: string;
  pricing_tier_id: number;
  pricing_tier?: string;
  condition_notes: string | null;
  status: ItemStatus;
  quantity: number;
  weight_kg: string | null;
  unit_price_fils: number;
  sales_order_id: string | null;
  sorted_by: number | null;
  created_at: string;
  updated_at: string;
};

export const itemStatuses: ItemStatus[] = ["available", "reserved", "sold", "returned", "damaged", "missing"];
export const itemSeasons = ["summer", "winter", "spring", "general"];
export const itemGenders = ["man", "woman", "girl", "boy"];

export type ItemFilters = {
  search?: string;
  status?: string;
  season?: string;
  gender?: string;
  itemTypeId?: string;
  pricingTierId?: string;
  packageId?: string;
};

export function useItems(filters: ItemFilters) {
  return useQuery({
    queryKey: ["items", filters],
    queryFn: async () => {
      const response = await api.get<{ data: SortLotItem[] }>("/items", {
        params: {
          per_page: 50,
          search: filters.search || undefined,
          "filter[status]": filters.status || undefined,
          "filter[season]": filters.season || undefined,
          "filter[gender]": filters.gender || undefined,
          "filter[item_type_id]": filters.itemTypeId || undefined,
          "filter[pricing_tier_id]": filters.pricingTierId || undefined,
          "filter[package_id]": filters.packageId || undefined,
        },
      });

      return response.data.data;
    },
  });
}

export function useItem(id: string) {
  return useQuery({
    queryKey: ["items", id],
    queryFn: async () => {
      const response = await api.get<{ data: SortLotItem }>(`/items/${id}`);

      return response.data.data;
    },
  });
}

export function useChangeItemStatus(itemId?: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, status }: { id: string; status: ItemStatus }) => {
      const response = await api.patch<{ data: SortLotItem }>(`/items/${id}/status`, {
        status,
        reason: "Quick status update",
      });

      return response.data.data;
    },
    onSuccess: (updated) => {
      queryClient.setQueryData(["items", updated.id], updated);
      queryClient.invalidateQueries({ queryKey: ["items"] });
      if (itemId) {
        queryClient.invalidateQueries({ queryKey: ["items", itemId] });
      }
    },
  });
}
