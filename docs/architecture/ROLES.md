# ROLES & PERMISSIONS

---

## Roles

| Role | Description |
|------|-------------|
| `super_admin` | Full system access. Can manage users, roles, all data. |
| `manager` | Full operational access. Cannot manage users/roles. |
| `warehouse_staff` | Can manage packages and items only. Cannot access invoicing or financials. |
| `accountant` | Full invoicing and financial access. Read-only on packages/items. |
| `viewer` | Read-only across all modules. No create/edit/delete. |

---

## Permissions Matrix

Format: ✅ Full | 👁 Read-only | ❌ No access | 🔧 Limited (see note)

### Users & Auth
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `users.view` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `users.create` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `users.edit` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `users.delete` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `roles.manage` | ✅ | ❌ | ❌ | ❌ | ❌ |

### Packages
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `packages.view` | ✅ | ✅ | ✅ | 👁 | 👁 |
| `packages.create` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `packages.edit` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `packages.delete` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `packages.change_status` | ✅ | ✅ | ✅ | ❌ | ❌ |

### Items
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `items.view` | ✅ | ✅ | ✅ | 👁 | 👁 |
| `items.create` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `items.edit` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `items.delete` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `items.change_status` | ✅ | ✅ | ✅ | ❌ | ❌ |

### Customers
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `customers.view` | ✅ | ✅ | ❌ | ✅ | 👁 |
| `customers.create` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `customers.edit` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `customers.delete` | ✅ | ✅ | ❌ | ❌ | ❌ |

### Suppliers
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `suppliers.view` | ✅ | ✅ | ❌ | ✅ | 👁 |
| `suppliers.create` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `suppliers.edit` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `suppliers.delete` | ✅ | ✅ | ❌ | ❌ | ❌ |

### Invoicing — Sales Orders
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `sales_orders.view` | ✅ | ✅ | ❌ | ✅ | 👁 |
| `sales_orders.create` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `sales_orders.edit` | ✅ | ✅ | ❌ | 🔧 | ❌ |
| `sales_orders.delete` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `sales_orders.confirm` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `sales_orders.cancel` | ✅ | ✅ | ❌ | ❌ | ❌ |

> 🔧 Accountant can edit draft/pending invoices only. Cannot edit paid invoices.

### Invoicing — Purchase Orders
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `purchase_orders.view` | ✅ | ✅ | ❌ | ✅ | 👁 |
| `purchase_orders.create` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `purchase_orders.edit` | ✅ | ✅ | ❌ | 🔧 | ❌ |
| `purchase_orders.delete` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `purchase_orders.confirm` | ✅ | ✅ | ❌ | ✅ | ❌ |

### Payments
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `payments.view` | ✅ | ✅ | ❌ | ✅ | 👁 |
| `payments.create` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `payments.delete` | ✅ | ❌ | ❌ | ❌ | ❌ |

### Preferences
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `preferences.view` | ✅ | ✅ | ❌ | 👁 | ❌ |
| `preferences.edit` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `pricing_tiers.manage` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `item_types.manage` | ✅ | ✅ | ❌ | ❌ | ❌ |

### Reports & Dashboard
| Permission | super_admin | manager | warehouse_staff | accountant | viewer |
|-----------|-------------|---------|-----------------|------------|--------|
| `dashboard.view` | ✅ | ✅ | 🔧 | 🔧 | 🔧 |
| `reports.financial` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `reports.inventory` | ✅ | ✅ | ✅ | 👁 | 👁 |
| `reports.export` | ✅ | ✅ | ❌ | ✅ | ❌ |
| `audit_logs.view` | ✅ | ✅ | ❌ | ❌ | ❌ |

> 🔧 Warehouse sees inventory widgets only. Accountant sees financial widgets only. Viewer sees summary only.

---

## Implementation Notes

### Backend (Laravel)
```php
// Controller check
$this->authorize('packages.create');

// Or middleware
Route::middleware('permission:packages.create')->post('/packages', ...);

// Or in Policy
public function create(User $user): bool
{
    return $user->can('packages.create');
}
```

### Frontend (Next.js)
- `/auth/me` response includes: `{ user: {...}, roles: ['manager'], permissions: ['packages.view', ...] }`
- Store in Zustand auth store
- `<Gate permission="packages.create">` component wraps UI elements
- Route-level check in Next.js middleware for page access
- Server Components can also check via API call

### Permission Seeder Key
All permissions seeded in `PermissionSeeder.php`. Format: `{resource}.{action}`.  
Resources: `users`, `roles`, `packages`, `items`, `customers`, `suppliers`, `sales_orders`, `purchase_orders`, `payments`, `preferences`, `pricing_tiers`, `item_types`, `dashboard`, `reports`, `audit_logs`.  
Actions: `view`, `create`, `edit`, `delete`, `confirm`, `cancel`, `change_status`, `manage`, `export`, `financial`, `inventory`.
