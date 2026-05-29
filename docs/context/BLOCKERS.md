# BLOCKERS & DEFERRED ITEMS

> Items that need a decision, external input, or are intentionally deferred.

---

## 🔴 Active Blockers

_(none currently — project in planning phase)_

---

## 🟡 Needs Owner Decision Before Phase 3

### B-001 — Invoice Number Format
**Question:** What format should invoice numbers take?  
**Options:**
- `INV-2024-00001` (sequential per year)
- `SO-00001` / `PO-00001` (Sales Order / Purchase Order prefix)
- Custom prefix from Preferences  
**Recommendation:** Custom prefix per document type, set in Preferences. E.g. `SO-` for sales, `PO-` for purchases, `CN-` for credit notes. Sequential, zero-padded, per-year reset optional.

### B-002 — Credit Notes
**Question:** When a customer returns items or there's a dispute, is a credit note issued or a refund invoice?  
**Recommendation:** Implement Credit Notes as a document type linked to original invoice. Reduces balance owed. This is standard accounting practice.

### B-003 — Payment Methods
**Question:** What payment methods do customers use?  
**Likely:** Cash, Bank Transfer, Cheque, Credit Card.  
**Impact:** Each invoice payment record needs `payment_method` field for reconciliation.

### B-004 — Item Barcode/QR
**Question:** Should individual items get a barcode or QR code for scanning during picking/shipping?  
**Recommendation:** Yes — add `barcode` field to `items` table. Auto-generate SKU. Phase 2 can include barcode printing. Speeds up warehouse ops significantly.

### B-005 — Weight Tracking
**Question:** Do packages have a physical weight tracked for logistics/cost purposes?  
**Recommendation:** Yes — `weight_kg` on `packages` table. Used for cost allocation per item and shipping cost tracking.

---

## 🟢 Intentionally Deferred

### D-001 — Multi-currency (USD)
Deferred to Phase 5. Base currency AED throughout. See DECISIONS.md D-010.

### D-002 — Mobile App
Not in scope. Web is responsive. Native app is a future phase.

### D-003 — B2B Customer Portal
Future: let customers log in and view their own invoices. Out of scope for v1.

### D-004 — Automated Bank Reconciliation
Future: connect bank feed (e.g. via Plaid/Lean) to auto-match payments to invoices.

### D-005 — AI Sorting Assist
Future: camera + ML model to auto-classify item type/condition. Architecture supports it (items table has all classification fields).
