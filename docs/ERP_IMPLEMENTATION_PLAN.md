# Martin Logistics — ERP Implementation Plan

## Overview

This document defines a phased build plan to evolve the current fleet operations platform into a
complete Logistics ERP. Phases are ordered so that no feature is built before its dependencies exist.

---

## Dependency Map

```
Document Management ─┬─→ Contract Management ──→ Billing & Invoicing ──→ Accounts Receivable
                     │
                     ├─→ Procurement ──→ Warehouse / Inventory
                     │                └─→ Preventive Maintenance
                     │
                     └─→ Proof of Delivery

Rate / Tariff Engine ──→ Contract Management ──→ Billing & Invoicing

HR / Payroll ──→ Full Accounting / GL
                      ↑
Billing & Invoicing ──┤
Procurement ──────────┤

Advanced Scheduling ──→ Load Optimization
```

---

## Phase 1 — Foundation

*No external dependencies. Everything else builds on these.*

### 1.1 Document Management

A centralized file repository tied to any entity (orders, vehicles, drivers, contracts, POs).

**Requirements:**
- `documents` table (polymorphic: `documentable_type`, `documentable_id`)
- Fields: `name`, `file_path`, `type` (contract, permit, POD, invoice, etc.), `expiry_date`, `notes`, `uploaded_by`
- File upload via S3 or local disk with access control
- Expiry alerts (cron checks documents expiring within N days)
- Vue components: document list, upload modal, expiry badge

**Why first:** Contracts, POs, PODs, and maintenance records all need somewhere to store files.

---

### 1.2 Rate / Tariff Engine

A pricing engine that calculates freight costs automatically.

**Requirements:**
- `rate_cards` table: `name`, `effective_from`, `effective_to`, `client_id` (nullable for default), `is_active`
- `rate_card_items` table: `rate_card_id`, `charge_name`, `charge_type` (per_km, per_kg, per_container, flat, per_stop, fuel_surcharge), `amount`, `currency_id`, `min_charge`, `max_charge`
- `rate_calculator` service class: given origin/destination/distance/weight/vehicle type → compute total
- Admin CRUD UI for rate cards
- Preview calculated rate on Order create/edit

**Why first:** Billing and contracts both depend on rates to function.

---

## Phase 2 — Commercial Agreements

*Builds on Phase 1.*

### 2.1 Contract Management

Customer service agreements that tie clients to pricing terms, SLAs, and validity periods.

**Requirements:**
- `contracts` table: `reference`, `client_id`, `type` (monthly, yearly, spot), `rate_card_id`, `start_date`, `end_date`, `status`, `terms`, `sla_response_hours`, `sla_resolution_hours`
- Contract attachments via Document Management
- Auto-expiry notifications (30/14/7 days before end)
- SLA breach detection (cron + notifications)
- Vue pages: contract list, contract detail with timeline, create/edit form

**Why second:** Contracts reference rate cards (Phase 1) and store attachments (Phase 1). Billing needs contracts to know which rates apply.

---

### 2.2 Procurement / Purchasing

Purchase orders for fuel, vehicle parts, office supplies, and services.

**Requirements:**
- `vendors` table: `name`, `contact`, `email`, `phone`, `address`, `tin`, `payment_terms`
- `purchase_orders` table: `reference`, `vendor_id`, `order_date`, `expected_date`, `status` (draft, sent, confirmed, received, cancelled), `notes`, `total_amount`, `currency_id`
- `po_items` table: `purchase_order_id`, `description`, `quantity`, `unit_price`, `total`
- PO attachments via Document Management
- Receiving workflow (mark items as received with partial support)
- Vue pages: vendor list, PO list, PO create, PO receive

**Why second:** Needs Document Management (Phase 1). Maintenance (Phase 4) needs procurement for parts.

---

## Phase 3 — Revenue

*Builds on Phases 1–2.*

### 3.1 Billing & Invoicing

Generate invoices from orders/trips, applying contract rates automatically.

**Requirements:**
- `invoices` table: `reference`, `client_id`, `contract_id`, `order_id` (nullable), `issue_date`, `due_date`, `status` (draft, sent, paid, overdue, cancelled), `subtotal`, `tax_total`, `discount_total`, `total`, `currency_id`, `notes`
- `invoice_items` table: `invoice_id`, `description`, `charge_type`, `quantity`, `unit_price`, `total`
- Auto-generate from delivered orders using Rate Engine (Phase 1) + Contract rates (Phase 2)
- Manual invoice creation with line items
- Invoice PDF generation (via Laravel DomPDF or similar)
- Credit notes
- Vue pages: invoice list, invoice detail, invoice create, printable view

**Dependencies:** Rate Engine (1.2), Contract Management (2.1), Orders (existing)

---

### 3.2 Accounts Receivable

Track customer payments and outstanding balances.

**Requirements:**
- `payments` table already exists — extend with: `invoice_id`, `payment_method` (cash, bank_transfer, mobile_money, check), `reference`, `notes`
- Payment reconciliation against invoices
- Aging report (30/60/90+ days overdue)
- Payment reminders (automated emails)
- Vue pages: payment list, payment registration, aging report, client statement

**Dependencies:** Invoicing (3.1)

---

## Phase 4 — Operations Deepening

*Builds on Phases 1–2.*

### 4.1 Warehouse / Inventory Management

Track stock across warehouse locations.

**Requirements:**
- `warehouses` table: `name`, `code`, `location`, `is_active`
- `products` / `parts` table: `sku`, `name`, `description`, `category`, `unit_of_measure`, `unit_price`
- `stock_levels` table: `warehouse_id`, `product_id`, `quantity`, `min_quantity` (reorder point)
- Stock movements: `stock_movements` table (in/out/adjust/transfer) with user + reason
- Inventory adjustments with approval
- Reorder alerts when stock falls below minimum
- Vue pages: warehouse list, product catalog, stock levels, stock movement log

**Dependencies:** Procurement (2.2) for stock-in from POs

---

### 4.2 Preventive Maintenance

Move beyond the current maintenance toggle to a full maintenance lifecycle.

**Requirements:**
- `maintenance_schedules` table: `vehicle_id`, `type` (oil_change, tire_rotation, brake_check, inspection, general), `interval_km`, `interval_days`, `last_done_at`, `last_done_km`, `is_active`
- `work_orders` table: `reference`, `vehicle_id`, `driver_id`, `type`, `priority`, `status` (open, in_progress, completed, cancelled), `scheduled_date`, `completed_date`, `notes`, `total_cost`
- `work_order_items` table: `work_order_id`, `description`, `part_id` (nullable, references products), `quantity`, `unit_price`, `total`
- Auto-generate work orders when schedule is due (by km or date)
- Parts usage tracking via inventory
- Maintenance cost reporting per vehicle
- Vue pages: schedule management, work order list, work order detail, calendar view

**Dependencies:** Procurement (2.2) for parts, Document Management (1.1) for receipts/manuals

---

### 4.3 Proof of Delivery (POD)

Electronic POD workflow for drivers and customers.

**Requirements:**
- `proofs_of_delivery` table: `order_id`, `trip_id`, `delivered_at`, `received_by_name`, `received_by_relation`, `signature_data` (base64 or SVG), `notes`, `gps_lat`, `gps_lng`
- Delivery photos via Document Management (polymorphic link)
- Driver mobile endpoint for POD submission (photos + signature)
- Customer portal shows POD for delivered orders
- POD PDF generation (delivery receipt)
- Vue pages: POD view, POD list per order

**Dependencies:** Orders + Trips (existing), Document Management (1.1) for photos

---

## Phase 5 — Customer Experience

*Builds on Phase 3.*

### 5.1 Customer Notifications

Automated email/SMS notifications triggered by system events.

**Requirements:**
- `notification_templates` table: `key` (order_confirmed, in_transit, delivered, invoice_sent, payment_received, etc.), `subject`, `body_html`, `channels` (email, sms)
- `notification_log` table: `notifiable_type`, `notifiable_id`, `channel`, `template_key`, `sent_at`, `status`, `error`
- Event → listener pipeline for key lifecycle events
- Customer portal notification preferences
- SMS integration (Twilio or similar)
- Queue: push to sync job for each channel

**Dependencies:** Invoicing (3.1) for invoice notifications, Orders (existing) for order notifications

---

### 5.2 Returns / Reverse Logistics

Handle customer returns with inspection and restocking.

**Requirements:**
- `return_requests` table: `reference`, `order_id`, `client_id`, `reason`, `status` (pending, approved, rejected, collected, inspected, completed), `requested_at`, `approved_at`
- `return_items` table: `return_request_id`, `description`, `quantity`, `condition` (damaged, wrong_item, expired, good)
- Inspection workflow: document condition, attach photos, decide restock vs disposal
- Credit note generation upon completion (links to Invoicing 3.1)
- Vue pages: return request list, return detail with inspection form

**Dependencies:** Orders (existing), Warehouse/Inventory (4.1) for restocking, Invoicing (3.1) for credit notes

---

## Phase 6 — Corporate Backbone

*Builds on multiple preceding phases.*

### 6.1 HR / Payroll

Employee and payroll management.

**Requirements:**
- `employees` table: `user_id` (nullable), `employee_code`, `department`, `position`, `hire_date`, `salary`, `bank_account`, `emergency_contact`, `status`
- `departments` table: `name`, `code`, `manager_id`
- `attendance` table: `employee_id`, `date`, `clock_in`, `clock_out`, `status`
- `leave_requests` table: `employee_id`, `type` (annual, sick, unpaid), `start_date`, `end_date`, `status`, `approved_by`
- Payroll processing: generate payslips from salary + attendance + leave
- `payslips` table: `employee_id`, `period_start`, `period_end`, `basic_pay`, `allowances`, `deductions`, `net_pay`, `status`, `paid_at`
- Driver-specific: link to existing Driver model, track license/medical expiry

**Dependencies:** Users (existing), Document Management (1.1) for employee documents

---

### 6.2 Full Accounting / General Ledger

Double-entry accounting engine.

**Requirements:**
- `chart_of_accounts` table: `code`, `name`, `type` (asset, liability, equity, revenue, expense), `is_active`, `parent_id`
- `journal_entries` table: `reference`, `description`, `date`, `created_by`, `status`
- `journal_entry_lines` table: `journal_entry_id`, `account_id`, `debit`, `credit`, `notes`
- Auto-posting from:
  - Invoices (→ AR + Revenue)
  - Payments (→ Cash/Bank + AR)
  - Purchase Orders received (→ Inventory + AP)
  - Payroll (→ Salary Expense + Bank)
- Financial statements: Trial Balance, P&L, Balance Sheet, Cash Flow
- Fiscal year management with closing
- Vue pages: chart of accounts, journal entry create, financial reports

**Dependencies:** Invoicing (3.1), Procurement (2.2), HR/Payroll (6.1)

---

## Phase 7 — Advanced Operations

*Builds on multiple preceding phases.*

### 7.1 Advanced Scheduling / Calendar

Visual scheduling for pickups, deliveries, and driver shifts.

**Requirements:**
- Calendar view (weekly/daily) showing:
  - Scheduled pickups and deliveries per vehicle
  - Driver shifts and assignments
  - Maintenance downtime (Phase 4.2)
- Drag-and-drop rescheduling
- `time_slots` table: `order_id`, `vehicle_id`, `driver_id`, `scheduled_start`, `scheduled_end`, `type` (pickup, delivery), `status`
- Conflict detection (vehicle double-booked, driver over-capacity)
- Vue component: FullCalendar integration with custom event rendering

**Dependencies:** Orders (existing), Drivers/Vehicles (existing), Maintenance (4.2), POD (4.3)

---

### 7.2 Multi-Branch / Multi-Warehouse

Support for multiple operational branches with isolation or shared data.

**Requirements:**
- `branches` table: `name`, `code`, `address`, `phone`, `email`, `is_active`
- Add `branch_id` to: vehicles, drivers, orders, invoices, warehouses, employees
- Branch-level user permissions (view own branch only vs all)
- Branch-level inventory (warehouse per branch)
- Branch-level P&L reporting

**Dependencies:** Accounting (6.2) for branch P&L, Inventory (4.1), Vehicles/Drivers (existing)

---

## Phase 8 — Intelligence & Integration

*Builds on stable operations.*

### 8.1 Load Optimization

Algorithmic optimization of load assignment.

**Requirements:**
- Capacity calculation per vehicle (volume/weight)
- Multi-stop route sequencing
- Load consolidation (group orders going same direction)
- Optimization service (integration with external API or custom solver)
- What-if simulation UI
- Vue pages: load planner with drag-and-drop order grouping

**Dependencies:** Scheduling (7.1), Routes (existing), Vehicles (existing)

---

### 8.2 Public API & Webhooks

Expose system capabilities for third-party integration.

**Requirements:**
- RESTful API with API key authentication (separate from Sanctum)
- Rate limiting per key
- Endpoints: orders, tracking, rates, invoices, PODs
- Webhooks: `order.created`, `order.status_changed`, `delivery.confirmed`, `invoice.sent`, `invoice.paid`
- Webhook delivery log with retry mechanism
- Developer documentation (OpenAPI/Swagger)

**Dependencies:** Stable modules across all phases (ongoing)

---

## Implementation Order Summary

```
Phase 1 ─── Foundation
  1.1  Document Management
  1.2  Rate / Tariff Engine

Phase 2 ─── Commercial Agreements
  2.1  Contract Management
  2.2  Procurement / Purchasing

Phase 3 ─── Revenue
  3.1  Billing & Invoicing
  3.2  Accounts Receivable

Phase 4 ─── Operations Deepening
  4.1  Warehouse / Inventory Management
  4.2  Preventive Maintenance
  4.3  Proof of Delivery

Phase 5 ─── Customer Experience
  5.1  Customer Notifications
  5.2  Returns / Reverse Logistics

Phase 6 ─── Corporate Backbone
  6.1  HR / Payroll
  6.2  Full Accounting / General Ledger

Phase 7 ─── Advanced Operations
  7.1  Advanced Scheduling / Calendar
  7.2  Multi-Branch / Multi-Warehouse

Phase 8 ─── Intelligence & Integration
  8.1  Load Optimization
  8.2  Public API & Webhooks
```

Each phase assumes all previous phases are complete. Within a phase, items can be built in
any order unless noted.
