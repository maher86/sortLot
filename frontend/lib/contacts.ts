"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { api } from "@/lib/api";

export type VatType = "mainland" | "free_zone" | "international";

export type Customer = {
  id: string;
  name: string;
  contact_name: string | null;
  email: string | null;
  phone: string | null;
  country: string;
  emirate: string | null;
  address: string | null;
  vat_type: VatType;
  trn: string | null;
  credit_limit_fils: number;
  payment_terms_days: number;
  notes: string | null;
  is_active: boolean;
  created_at: string;
};

export type Supplier = {
  id: string;
  name: string;
  contact_name: string | null;
  email: string | null;
  phone: string | null;
  country: string;
  address: string | null;
  vat_type: VatType;
  trn: string | null;
  bank_name: string | null;
  bank_iban: string | null;
  bank_swift: string | null;
  notes: string | null;
  is_active: boolean;
  created_at: string;
};

export type CustomerStatement = {
  customer: Customer;
  balance_fils: number;
  credit_limit_fils: number;
  available_credit_fils: number;
  projected_invoice_fils: number;
  would_exceed_credit_limit: boolean;
  invoices: Array<{ id: string; number: string; status: string; balance_fils: number; total_fils: number }>;
};

type ListResponse<T> = {
  data: T[];
};

export const vatTypes: VatType[] = ["mainland", "free_zone", "international"];

export function formatVatType(vatType: string) {
  return vatType
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

export function formatFils(fils: number) {
  return `${(fils / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AED`;
}

export function useCustomers(filters: { search?: string; vatType?: string } = {}) {
  return useQuery({
    queryKey: ["customers", filters],
    queryFn: async () => {
      const response = await api.get<ListResponse<Customer>>("/customers", {
        params: {
          search: filters.search || undefined,
          "filter[vat_type]": filters.vatType || undefined,
        },
      });

      return response.data.data;
    },
  });
}

export function useCustomer(id: string) {
  return useQuery({
    queryKey: ["customers", id],
    queryFn: async () => {
      const response = await api.get<{ data: Customer }>(`/customers/${id}`);

      return response.data.data;
    },
  });
}

export function useCustomerStatement(id: string, projectedInvoiceFils = 0) {
  return useQuery({
    queryKey: ["customers", id, "statement", projectedInvoiceFils],
    queryFn: async () => {
      const response = await api.get<{ data: CustomerStatement }>(`/customers/${id}/statement`, {
        params: { projected_invoice_fils: projectedInvoiceFils },
      });

      return response.data.data;
    },
  });
}

export function useCreateCustomer() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      const response = await api.post<{ data: Customer }>("/customers", payload);

      return response.data.data;
    },
    onSuccess: (customer) => {
      queryClient.invalidateQueries({ queryKey: ["customers"] });
      queryClient.setQueryData(["customers", customer.id], customer);
    },
  });
}

export function useUpdateCustomer(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      const response = await api.patch<{ data: Customer }>(`/customers/${id}`, payload);

      return response.data.data;
    },
    onSuccess: (customer) => {
      queryClient.invalidateQueries({ queryKey: ["customers"] });
      queryClient.setQueryData(["customers", customer.id], customer);
    },
  });
}

export function useSuppliers(filters: { search?: string; vatType?: string } = {}) {
  return useQuery({
    queryKey: ["suppliers", filters],
    queryFn: async () => {
      const response = await api.get<ListResponse<Supplier>>("/suppliers", {
        params: {
          search: filters.search || undefined,
          "filter[vat_type]": filters.vatType || undefined,
        },
      });

      return response.data.data;
    },
  });
}

export function useSupplier(id: string) {
  return useQuery({
    queryKey: ["suppliers", id],
    queryFn: async () => {
      const response = await api.get<{ data: Supplier }>(`/suppliers/${id}`);

      return response.data.data;
    },
  });
}

export function useCreateSupplier() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      const response = await api.post<{ data: Supplier }>("/suppliers", payload);

      return response.data.data;
    },
    onSuccess: (supplier) => {
      queryClient.invalidateQueries({ queryKey: ["suppliers"] });
      queryClient.setQueryData(["suppliers", supplier.id], supplier);
    },
  });
}

export function useUpdateSupplier(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      const response = await api.patch<{ data: Supplier }>(`/suppliers/${id}`, payload);

      return response.data.data;
    },
    onSuccess: (supplier) => {
      queryClient.invalidateQueries({ queryKey: ["suppliers"] });
      queryClient.setQueryData(["suppliers", supplier.id], supplier);
    },
  });
}
