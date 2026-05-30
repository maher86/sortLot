<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->when($request->input('filter.invoice_id'), fn ($query, $invoiceId) => $query->where('invoice_id', $invoiceId))
            ->when($request->input('filter.method'), fn ($query, $method) => $query->where('payment_method', $method))
            ->when($request->input('from'), fn ($query, $from) => $query->whereDate('payment_date', '>=', $from))
            ->when($request->input('to'), fn ($query, $to) => $query->whereDate('payment_date', '<=', $to))
            ->latest('payment_date')
            ->paginate((int) $request->integer('per_page', 15));

        return PaymentResource::collection($payments);
    }

    public function store(PaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $payment = Payment::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return PaymentResource::make($payment)->response()->setStatusCode(201);
    }

    public function show(Payment $payment): PaymentResource
    {
        $this->authorize('view', $payment);

        return PaymentResource::make($payment);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);

        $payment->delete();

        return response()->json(status: 204);
    }
}
