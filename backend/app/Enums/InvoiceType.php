<?php

namespace App\Enums;

enum InvoiceType: string
{
    case SalesOrder = 'sales_order';
    case PurchaseOrder = 'purchase_order';
    case CreditNote = 'credit_note';
}
