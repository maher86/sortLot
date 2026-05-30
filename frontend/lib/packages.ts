"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { api } from "@/lib/api";

export type PackageStatus =
  | "in_transit"
  | "at_port"
  | "in_customs"
  | "in_warehouse"
  | "sorting"
  | "sorted"
  | "partially_shipped"
  | "shipped"
  | "closed";

export type SortLotPackage = {
  id: string;
  reference: string;
  supplier_id: string | null;
  purchase_order_id: string | null;
  origin_country: string;
  destination_country: string;
  status: PackageStatus;
  weight_kg: string | null;
  number_of_bags: number | null;
  notes: string | null;
  arrived_at: string | null;
  sorting_started_at: string | null;
  sorting_completed_at: string | null;
  sorted_by: number | null;
  created_by: number;
  items_count: number;
  available_items_count: number;
  created_at: string;
  updated_at: string;
};

export type ItemSummary = {
  id: string;
  sku: string;
  barcode: string | null;
  season: string;
  gender: string;
  status: string;
  item_type_id?: number;
  pricing_tier_id?: number;
  unit_price_fils?: number;
};

export type OptionRecord = {
  id: number;
  name?: string;
  code?: string;
  label?: string;
};

type PackageListResponse = {
  data: SortLotPackage[];
  meta?: {
    current_page: number;
    last_page: number;
  };
};

export const packageStatuses: PackageStatus[] = [
  "in_transit",
  "at_port",
  "in_customs",
  "in_warehouse",
  "sorting",
  "sorted",
  "partially_shipped",
  "shipped",
  "closed",
];

export function formatStatus(status: string) {
  return status
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

export function usePackages(filters: { status?: string; search?: string; page?: number }) {
  return useQuery({
    queryKey: ["packages", filters],
    queryFn: async () => {
      const response = await api.get<PackageListResponse>("/packages", {
        params: {
          page: filters.page ?? 1,
          search: filters.search || undefined,
          "filter[status]": filters.status || undefined,
        },
      });

      return response.data;
    },
  });
}

export function usePackage(id: string) {
  return useQuery({
    queryKey: ["packages", id],
    queryFn: async () => {
      const response = await api.get<{ data: SortLotPackage }>(`/packages/${id}`);

      return response.data.data;
    },
  });
}

export function usePackageItems(packageId: string) {
  return useQuery({
    queryKey: ["package-items", packageId],
    queryFn: async () => {
      const response = await api.get<{ data: ItemSummary[] }>("/items", {
        params: { "filter[package_id]": packageId, per_page: 50 },
      });

      return response.data.data;
    },
  });
}

export function usePreferenceOptions() {
  return useQuery({
    queryKey: ["preference-options"],
    queryFn: async () => {
      const [itemTypes, pricingTiers] = await Promise.all([
        api.get<{ data: OptionRecord[] }>("/preferences/item-types"),
        api.get<{ data: OptionRecord[] }>("/preferences/pricing-tiers"),
      ]);

      return {
        itemTypes: itemTypes.data.data,
        pricingTiers: pricingTiers.data.data,
      };
    },
  });
}

export function useCreatePackage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      const response = await api.post<{ data: SortLotPackage }>("/packages", payload);

      return response.data.data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["packages"] }),
  });
}

export function useChangePackageStatus(packageId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (status: PackageStatus) => {
      const previous = queryClient.getQueryData<SortLotPackage>(["packages", packageId]);
      if (previous) {
        queryClient.setQueryData(["packages", packageId], { ...previous, status });
      }

      const response = await api.patch<{ data: SortLotPackage }>(`/packages/${packageId}/status`, { status });

      return response.data.data;
    },
    onSuccess: (updated) => {
      queryClient.setQueryData(["packages", packageId], updated);
      queryClient.invalidateQueries({ queryKey: ["packages"] });
    },
  });
}

export function useBulkCreateItems(packageId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (items: Record<string, unknown>[]) => {
      const response = await api.post<{ data: ItemSummary[] }>(`/packages/${packageId}/items/bulk`, { items });

      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["package-items", packageId] });
      queryClient.invalidateQueries({ queryKey: ["packages", packageId] });
      queryClient.invalidateQueries({ queryKey: ["packages"] });
    },
  });
}
