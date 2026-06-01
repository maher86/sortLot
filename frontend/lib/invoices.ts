"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { api } from "@/lib/api";
import type { Customer, Supplier } from "@/lib/contacts";
import type { SortLotItem } from "@/lib/items";

export type InvoiceType = "sales_order" | "purchase_order" | "credit_note";
export type InvoiceStatus = "draft" | "pending" | "partial" | "paid" | "overdue" | "cancelled" | "refunded" | "disputed" | "write_off";
export type PaymentMethod = "cash" | "bank_transfer" | "card" | "cheque" | "credit_note" | "other";

export type InvoiceLine = {
  id: string;
  invoice_id: string;
  item_id: string | null;
  description: string;
  quantity: string | number;
  unit_price_fils: number;
  discount_pct: string | number;
  line_total_fils: number;
  sort_order: number;
  item?: SortLotItem;
};

export type Payment = {
  id: string;
  invoice_id: string;
  amount_fils: number;
  payment_method: PaymentMethod;
  payment_date: string;
  reference: string | null;
  bank_name: string | null;
  notes: string | null;
  created_at: string;
};

export type Invoice = {
  id: string;
  type: InvoiceType;
  number: string;
  reference: string | null;
  status: InvoiceStatus;
  customer_id: string | null;
  supplier_id: string | null;
  related_invoice_id: string | null;
  issue_date: string;
  due_date: string | null;
  delivery_date: string | null;
  subtotal_fils: number;
  discount_fils: number;
  discount_pct: string | number;
  vat_rate: string | number;
  vat_amount_fils: number;
  total_fils: number;
  paid_amount_fils: number;
  balance_fils: number;
  currency: string;
  exchange_rate: string | number;
  notes: string | null;
  internal_notes: string | null;
  terms: string | null;
  pdf_path: string | null;
  customer?: Customer;
  supplier?: Supplier;
  lines?: InvoiceLine[];
  payments?: Payment[];
};

export type InvoiceKind = "sales" | "purchase";

export const invoiceStatuses: InvoiceStatus[] = ["draft", "pending", "partial", "paid", "overdue", "cancelled", "refunded", "disputed", "write_off"];
export const paymentMethods: PaymentMethod[] = ["cash", "bank_transfer", "card", "cheque", "credit_note", "other"];

export function formatStatus(status: string) {
  return status
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

export function invoiceEndpoint(kind: InvoiceKind) {
  return kind === "sales" ? "/sales-orders" : "/purchase-orders";
}

export function invoicePath(kind: InvoiceKind) {
  return kind === "sales" ? "/invoices/sales" : "/invoices/purchase";
}

type ListResponse<T> = {
  data: T[];
};

export function useInvoices(kind: InvoiceKind, filters: { status?: string; search?: string; from?: string; to?: string } = {}) {
  return useQuery({
    queryKey: ["invoices", kind, filters],
    queryFn: async () => {
      const response = await api.get<ListResponse<Invoice>>(invoiceEndpoint(kind), {
        params: {
          per_page: 50,
          search: filters.search || undefined,
          from: filters.from || undefined,
          to: filters.to || undefined,
          "filter[status]": filters.status || undefined,
        },
      });

      return response.data.data;
    },
  });
}

export function useInvoice(kind: InvoiceKind, id: string) {
  return useQuery({
    queryKey: ["invoices", kind, id],
    queryFn: async () => {
      const response = await api.get<{ data: Invoice }>(`${invoiceEndpoint(kind)}/${id}`);

      return response.data.data;
    },
  });
}

export function usePayments(invoiceId?: string) {
  return useQuery({
    queryKey: ["payments", invoiceId ?? "all"],
    queryFn: async () => {
      const response = await api.get<ListResponse<Payment>>("/payments", {
        params: {
          per_page: 50,
          "filter[invoice_id]": invoiceId || undefined,
        },
      });

      return response.data.data;
    },
  });
}

export function useCreateInvoice(kind: InvoiceKind) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      const response = await api.post<{ data: Invoice }>(invoiceEndpoint(kind), payload);

      return response.data.data;
    },
    onSuccess: (invoice) => {
      queryClient.invalidateQueries({ queryKey: ["invoices", kind] });
      queryClient.setQueryData(["invoices", kind, invoice.id], invoice);
    },
  });
}

export function useConfirmInvoice(kind: InvoiceKind, id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      const response = await api.patch<{ data: Invoice }>(`${invoiceEndpoint(kind)}/${id}/confirm`);

      return response.data.data;
    },
    onSuccess: (invoice) => {
      queryClient.invalidateQueries({ queryKey: ["invoices", kind] });
      queryClient.setQueryData(["invoices", kind, invoice.id], invoice);
    },
  });
}

export function useCancelInvoice(kind: InvoiceKind, id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      const response = await api.patch<{ data: Invoice }>(`${invoiceEndpoint(kind)}/${id}/cancel`);

      return response.data.data;
    },
    onSuccess: (invoice) => {
      queryClient.invalidateQueries({ queryKey: ["invoices", kind] });
      queryClient.setQueryData(["invoices", kind, invoice.id], invoice);
    },
  });
}

export function useCreatePayment(invoiceId: string, kind?: InvoiceKind) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      const response = await api.post<{ data: Payment }>("/payments", { ...payload, invoice_id: invoiceId });

      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["payments", invoiceId] });
      if (kind) {
        queryClient.invalidateQueries({ queryKey: ["invoices", kind] });
      }
    },
  });
}

export function useCreateCreditNote(invoiceId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      const response = await api.post<{ data: Invoice }>(`/sales-orders/${invoiceId}/credit-note`);

      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["invoices", "sales"] });
    },
  });
}
