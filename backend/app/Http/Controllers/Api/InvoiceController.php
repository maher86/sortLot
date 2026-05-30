<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function salesOrders(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewSales', Invoice::class);

        return $this->index($request, InvoiceType::SalesOrder);
    }

    public function storeSalesOrder(InvoiceRequest $request): JsonResponse
    {
        $this->authorize('createSales', Invoice::class);

        $validated = $request->validated();
        if (empty($validated['customer_id'])) {
            return response()->json([
                'message' => 'Customer is required for sales orders.',
                'code' => 'CUSTOMER_REQUIRED',
            ], 422);
        }

        $invoice = $this->invoiceService->create([
            ...$validated,
            'type' => InvoiceType::SalesOrder,
            'customer_id' => $validated['customer_id'],
            'created_by' => $request->user()->id,
        ]);

        return InvoiceResource::make($invoice)->response()->setStatusCode(201);
    }

    public function purchaseOrders(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewPurchase', Invoice::class);

        return $this->index($request, InvoiceType::PurchaseOrder);
    }

    public function storePurchaseOrder(InvoiceRequest $request): JsonResponse
    {
        $this->authorize('createPurchase', Invoice::class);

        $validated = $request->validated();
        if (empty($validated['supplier_id'])) {
            return response()->json([
                'message' => 'Supplier is required for purchase orders.',
                'code' => 'SUPPLIER_REQUIRED',
            ], 422);
        }

        $invoice = $this->invoiceService->create([
            ...$validated,
            'type' => InvoiceType::PurchaseOrder,
            'supplier_id' => $validated['supplier_id'],
            'created_by' => $request->user()->id,
        ]);

        return InvoiceResource::make($invoice)->response()->setStatusCode(201);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        return InvoiceResource::make($invoice->load(['customer', 'supplier', 'lines.item', 'payments']));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);

        $validated = $request->validated();
        unset($validated['lines']);
        $invoice->update([...$validated, 'updated_by' => $request->user()->id]);
        $invoice->recalculate();

        return InvoiceResource::make($invoice->fresh(['customer', 'supplier', 'lines.item', 'payments']));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);

        if (! in_array($invoice->status->value, ['draft', 'cancelled'], true)) {
            return response()->json([
                'message' => 'Only draft or cancelled invoices can be deleted.',
                'code' => 'INVOICE_DELETE_NOT_ALLOWED',
            ], 409);
        }

        $invoice->delete();

        return response()->json(status: 204);
    }

    public function confirm(Invoice $invoice): InvoiceResource|JsonResponse
    {
        $this->authorize('confirm', $invoice);

        try {
            $this->invoiceService->confirm($invoice);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => 'INVOICE_CONFIRM_FAILED',
            ], 409);
        }

        return InvoiceResource::make($invoice->fresh(['customer', 'supplier', 'lines.item', 'payments']));
    }

    public function cancel(Invoice $invoice): InvoiceResource|JsonResponse
    {
        $this->authorize('cancel', $invoice);

        try {
            $this->invoiceService->cancel($invoice);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => 'INVOICE_CANCEL_FAILED',
            ], 409);
        }

        return InvoiceResource::make($invoice->fresh(['customer', 'supplier', 'lines.item', 'payments']));
    }

    public function creditNote(Invoice $invoice): JsonResponse
    {
        $this->authorize('createSales', Invoice::class);

        $creditNote = $this->invoiceService->generateCreditNote($invoice);

        return InvoiceResource::make($creditNote)->response()->setStatusCode(201);
    }

    private function index(Request $request, InvoiceType $type): AnonymousResourceCollection
    {
        $partyFilter = $type === InvoiceType::SalesOrder ? 'customer_id' : 'supplier_id';

        $invoices = Invoice::query()
            ->type($type)
            ->with(['customer', 'supplier'])
            ->withCount('payments')
            ->when($request->input('filter.status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->input("filter.{$partyFilter}"), fn ($query, $partyId) => $query->where($partyFilter, $partyId))
            ->when($request->input('from'), fn ($query, $from) => $query->whereDate('issue_date', '>=', $from))
            ->when($request->input('to'), fn ($query, $to) => $query->whereDate('issue_date', '<=', $to))
            ->when($request->input('search'), fn ($query, $search) => $query->where('number', 'like', "%{$search}%"))
            ->latest('issue_date')
            ->paginate((int) $request->integer('per_page', 15));

        return InvoiceResource::collection($invoices);
    }
}
