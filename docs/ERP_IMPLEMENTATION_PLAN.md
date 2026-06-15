# Martin Logistics — ERP Implementation Plan

## Overview

This document defines a phased build plan to evolve the current fleet operations platform into a
complete Logistics ERP. Phases are ordered so that no feature is built before its dependencies exist.

---

## Dependency Map

```
Phase 1: Foundation
  Document Management ─┬─→ Contract Mgmt ──→ Invoicing ──→ AR
  Rate / Tariff Engine ─┘                   ↑             ↑
                                            │             │
Phase 2: Supply & Fuel                      │             │
  Procurement ──→ Fuel Tracking ────────────┤             │
                   (pump-to-tank)           │             │
                                            │             │
Phase 3: Commercial                         │             │
  Contract Management ──────────────────────┘             │
  Yard / Dock Management                                  │
                                                          │
Phase 4: Revenue                                          │
  Billing & Invoicing ────────────────────────────────────┘
  Accounts Receivable

Phase 5: Operations
  Warehouse / Inventory ←── Procurement
  Preventive Maintenance ←── Procurement + Docs
  Proof of Delivery    ←── Docs
  Yard / Dock Mgmt     ←── Vehicles + Drivers

Phase 6: Cost Intelligence
  ELD / HOS Compliance         ←── Drivers + Trips
  Trip Costing & Profitability ←── Fuel + Expenses + Invoicing

Phase 7: Engagement
  Customer Notifications ←── Orders + Invoicing
  Returns / Reverse Logs ←── Inventory + Invoicing
  HR / Payroll           ←── Docs (driver settlements)

Phase 8: Corporate
  Full Accounting / GL ──←── Invoicing + Procurement + HR/Payroll

Phase 9: Advanced
  Advanced Scheduling    ←── Maintenance + Orders
  Multi-Branch           ←── Accounting + Inventory
  Load Optimization      ←── Scheduling + Routes
  Public API & Webhooks  ←── Stable modules
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

**Why first:** Contracts, POs, PODs, maintenance records, and employee files all need somewhere to store documents.

---

### 1.2 Rate / Tariff Engine

A pricing engine that calculates freight costs automatically.

**Requirements:**
- `rate_cards` table: `name`, `effective_from`, `effective_to`, `client_id` (nullable for default), `is_active`
- `rate_card_items` table: `rate_card_id`, `charge_name`, `charge_type` (per_km, per_kg, per_container, flat, per_stop, fuel_surcharge), `amount`, `currency_id`, `min_charge`, `max_charge`
- `rate_calculator` service class: given origin/destination/distance/weight/vehicle type → compute total
- Admin CRUD UI for rate cards
- Preview calculated rate on Order create/edit

**Why first:** Billing, contracts, and trip profitability all depend on rates to function.

---

## Phase 2 — Supply & Fuel

*Builds on Phase 1.*

### 2.1 Procurement / Purchasing

Purchase orders for fuel, vehicle parts, office supplies, and services.

**Requirements:**
- `vendors` table: `name`, `contact`, `email`, `phone`, `address`, `tin`, `payment_terms`
- `purchase_orders` table: `reference`, `vendor_id`, `order_date`, `expected_date`, `status` (draft, sent, confirmed, received, cancelled), `notes`, `total_amount`, `currency_id`
- `po_items` table: `purchase_order_id`, `description`, `quantity`, `unit_price`, `total`
- PO attachments via Document Management (1.1)
- Receiving workflow (mark items as received with partial support)
- Vue pages: vendor list, PO list, PO create, PO receive

**Why second:** Creates the vendor/supplier base that fuel tracking and maintenance need. Also needs Document Management (1.1).

---

### 2.2 Pump-to-Tank Fuel Tracking

Dedicated fuel management module tracking the full fuel lifecycle from supplier pump to vehicle tank.

**Requirements:**

*Fuel Suppliers & Contracts:*
- Fuel supplier records (can use vendors from 2.1, or dedicated `fuel_suppliers` table)
- Fuel price contracts per supplier (price per liter, effective dates, fuel type)
- Fuel delivery scheduling

*Bulk Storage (on-site tanks):*
- `fuel_tanks` table: `code`, `capacity`, `current_level` (liters), `fuel_type` (diesel, petrol), `location`, `last_calibrated_at`
- Tank dip readings / level sensors
- Low-stock alerts when tank level drops below reorder threshold
- Tank-to-vehicle reconciliation (fuel drawn from which tank)

*Fuel Receipts & Dispensing:*
- `fuel_receipts` table: `supplier_id`, `tank_id`, `invoice_reference`, `quantity`, `unit_price`, `total`, `received_at`, `received_by`
- `fuel_dispense` table: `vehicle_id`, `driver_id`, `tank_id`, `quantity`, `odometer_at_dispense`, `dispensed_at`, `dispensed_by`, `notes`
- Pump-to-tank variance report (purchased vs dispensed over a period)
- Fuel consumption per vehicle (liters / 100km) with trend tracking

*Reporting:*
- Fuel cost per vehicle per trip/month
- Supplier price comparison
- Fuel efficiency trends per vehicle
- Monthly fuel consumption report by vehicle, driver, or tank

**Why this order:** Procurement (2.1) provides the vendor concept that fuel suppliers use. Fuel tracking then feeds Trip Costing (6.2).

---

## Phase 3 — Commercial

*Builds on Phases 1–2.*

### 3.1 Contract Management

Customer service agreements that tie clients to pricing terms, SLAs, and validity periods.

**Requirements:**
- `contracts` table: `reference`, `client_id`, `type` (monthly, yearly, spot), `rate_card_id`, `start_date`, `end_date`, `status`, `terms`, `sla_response_hours`, `sla_resolution_hours`
- Contract attachments via Document Management (1.1)
- Auto-expiry notifications (30/14/7 days before end)
- SLA breach detection (cron + notifications)
- Vue pages: contract list, contract detail with timeline, create/edit form

**Why third:** Contracts reference rate cards (1.2) and store attachments (1.1). Billing (4.1) depends on contracts to know which rates apply.

---

### 3.2 Yard & Dock Management

Manage truck check-in/check-out, dock door assignment, and loading/unloading schedules.

**Requirements:**
- `yard_entries` table: `vehicle_id`, `driver_id`, `check_in_at`, `check_out_at`, `purpose` (loading, unloading, parking, maintenance), `dock_door_id`, `notes`
- `dock_doors` table: `code`, `warehouse_id` (nullable), `is_occupied`, `current_vehicle_id`, `occupied_since`
- Yard capacity dashboard (doors occupied / free, trucks waiting)
- Check-in/check-out kiosk UI or mobile endpoint
- Waiting time tracking (check-in to dock assignment)
- Vue pages: yard dashboard, dock management, check-in log

**Dependencies:** Vehicles + Drivers (both exist)

---

## Phase 4 — Revenue

*Builds on Phases 1–3.*

### 4.1 Billing & Invoicing

Generate invoices from orders/trips, applying contract rates automatically.

**Requirements:**
- `invoices` table: `reference`, `client_id`, `contract_id`, `order_id` (nullable), `issue_date`, `due_date`, `status` (draft, sent, paid, overdue, cancelled), `subtotal`, `tax_total`, `discount_total`, `total`, `currency_id`, `notes`
- `invoice_items` table: `invoice_id`, `description`, `charge_type`, `quantity`, `unit_price`, `total`
- Auto-generate from delivered orders using Rate Engine (1.2) + Contract rates (3.1)
- Manual invoice creation with line items
- Invoice PDF generation (via Laravel DomPDF or similar)
- Credit notes and debit notes
- Bulk invoice generation for periodic billing
- Vue pages: invoice list, invoice detail, invoice create, printable view

**Dependencies:** Rate Engine (1.2), Contract Management (3.1), Orders (existing)

---

### 4.2 Accounts Receivable

Track customer payments, outstanding balances, and collection follow-ups.

**Requirements:**
- Extend existing `payments` table with: `invoice_id`, `payment_method` (cash, bank_transfer, mobile_money, check), `reference`, `notes`
- Payment reconciliation against invoices (partial payments, overpayments)
- Aging report (30/60/90+ days overdue)
- Automated payment reminders
- Client statements (PDF)
- Vue pages: payment list, payment registration, aging report, client statement

**Dependencies:** Invoicing (4.1)

---

## Phase 5 — Operations Deepening

*Builds on Phases 1–2.*

### 5.1 Warehouse / Inventory Management

Track stock across warehouse locations — parts, supplies, and goods.

**Requirements:**
- `warehouses` table: `name`, `code`, `location`, `is_active`
- `products` / `parts` table: `sku`, `name`, `description`, `category`, `unit_of_measure`, `unit_price`
- `stock_levels` table: `warehouse_id`, `product_id`, `quantity`, `min_quantity` (reorder point)
- Stock movements: `stock_movements` table (in/out/adjust/transfer) with user + reason
- Inventory adjustments with approval
- Reorder alerts when stock falls below minimum
- Integration with Procurement (2.1): POs auto-create stock-in movements
- Vue pages: warehouse list, product catalog, stock levels, stock movement log

**Dependencies:** Procurement (2.1) for stock-in from POs

---

### 5.2 Preventive Maintenance

Move beyond the current vehicle maintenance toggle to a full maintenance lifecycle.

**Requirements:**
- `maintenance_schedules` table: `vehicle_id`, `type` (oil_change, tire_rotation, brake_check, inspection, general), `interval_km`, `interval_days`, `last_done_at`, `last_done_km`, `is_active`
- `work_orders` table: `reference`, `vehicle_id`, `driver_id`, `type`, `priority`, `status` (open, in_progress, completed, cancelled), `scheduled_date`, `completed_date`, `notes`, `total_cost`
- `work_order_items` table: `work_order_id`, `description`, `part_id` (nullable, references products/parts), `quantity`, `unit_price`, `total`
- Auto-generate work orders when schedule is due (by km or date)
- Parts usage tracking via Inventory (5.1)
- Maintenance cost reporting per vehicle
- Maintenance downtime tracking (vehicle off-road during repairs)
- Vue pages: schedule management, work order list, work order detail, calendar view

**Dependencies:** Procurement (2.1) for parts purchasing, Document Management (1.1) for receipts/manuals

---

### 5.3 Proof of Delivery (POD)

Electronic POD workflow — the final mile closure for every delivery.

**Requirements:**
- `proofs_of_delivery` table: `order_id`, `trip_id`, `delivered_at`, `received_by_name`, `received_by_relation`, `signature_data` (base64 or SVG), `notes`, `gps_lat`, `gps_lng`
- Delivery photos via Document Management (1.1) polymorphic link
- Driver mobile endpoint for POD submission (photos + signature capture)
- Customer portal shows POD for delivered orders
- POD PDF generation (delivery receipt)
- Vue pages: POD view, POD list per order

**Dependencies:** Orders + Trips (existing), Document Management (1.1) for photos and generated PDFs

---

## Phase 6 — Cost Intelligence

*Builds on Phases 2–5.*

### 6.1 ELD / HOS Compliance

Electronic logging of driver hours to comply with hours-of-service regulations.

**Requirements:**
- `driver_logs` table: `driver_id`, `vehicle_id`, `date`, `start_time`, `end_time`, `total_hours`, `break_hours`, `driving_hours`, `status` (driving, on_duty, off_duty, sleeper)
- `driver_log_events` table: `driver_log_id`, `event_type`, `timestamp`, `gps_lat`, `gps_lng`, `odometer`
- Automatic duty-status detection based on trip activity and vehicle movement
- HOS rule engine (max driving hours, mandatory breaks, daily/weekly limits)
- Rest period alerts (push notification to driver mobile app)
- Daily log sheet generation (PDF for inspection)
- Vue pages: driver log list, daily log view, HOS compliance dashboard

**Dependencies:** Drivers (existing), Trips (existing), Vehicle Telemetry (existing)

---

### 6.2 Trip Costing & Profitability

Calculate per-trip and per-vehicle profitability by aggregating all cost lines.

**Requirements:**
- `trip_costs` table (or computed view): `trip_id`, `cost_category` (fuel, driver_allowance, toll, maintenance, fines, other), `description`, `amount`, `source_type`, `source_id`
- Cost sources:
  - Fuel: pull from Fuel Dispense (2.2) for the trip's vehicle + date range
  - Driver allowance: pull from driver settlement or allowance config per trip
  - Tolls: pull from trip expenses or toll receipts
  - Maintenance: allocate maintenance costs incurred during/after trip
  - Fines: pull existing TrafficFine records linked to the vehicle
- Revenue per trip: from invoice (4.1) if invoiced, or order value
- Profit / margin calculation: `revenue — total_costs`
- Dashboard: margin per trip, per vehicle, per driver, per client
- Trend reports: cost per km over time, fuel efficiency vs margin
- Vue pages: trip costing detail, cost breakdown, profitability dashboard

**Dependencies:** Fuel Tracking (2.2) for fuel cost, Invoicing (4.1) for trip revenue, Expenses/Requisitions (existing) for tolls, Maintenance (5.2) for repair costs

---

## Phase 7 — Engagement

*Builds on Phases 4–6.*

### 7.1 Customer Notifications

Automated email/SMS/push notifications triggered by system events.

**Requirements:**
- `notification_templates` table: `key` (order_confirmed, in_transit, delivered, invoice_sent, payment_received, pod_available, maintenance_due, etc.), `subject`, `body_html`, `channels` (email, sms)
- `notification_log` table: `notifiable_type`, `notifiable_id`, `channel`, `template_key`, `sent_at`, `status`, `error`
- Event → listener pipeline for key lifecycle events
- Customer portal notification preferences
- SMS integration (Twilio or similar)
- Queue: push to sync job for each channel
- Broadcast events via Pusher/Echo for real-time in-app notifications
- Vue components: notification bell dropdown, notification list

**Dependencies:** Orders (existing) for order events, Invoicing (4.1) for invoice events, POD (5.3) for delivery events

---

### 7.2 Returns / Reverse Logistics

Handle customer returns with inspection, disposition, and credit.

**Requirements:**
- `return_requests` table: `reference`, `order_id`, `client_id`, `reason`, `status` (pending, approved, rejected, collected, inspected, completed), `requested_at`, `approved_at`, `notes`
- `return_items` table: `return_request_id`, `description`, `quantity`, `condition` (damaged, wrong_item, expired, good)
- Inspection workflow: document condition, attach photos, decide restock vs disposal
- Credit note generation upon completion (links to Invoicing 4.1)
- Restock good items back into Inventory (5.1)
- Vue pages: return request list, return detail with inspection form

**Dependencies:** Orders (existing), Warehouse/Inventory (5.1) for restocking, Invoicing (4.1) for credit notes

---

### 7.3 HR / Payroll

Employee management, attendance, leave, and payroll — including driver settlements.

**Requirements:**
- `employees` table: `user_id` (nullable), `employee_code`, `department`, `position`, `hire_date`, `salary`, `bank_account`, `emergency_contact`, `status`
- `departments` table: `name`, `code`, `manager_id`
- `attendance` table: `employee_id`, `date`, `clock_in`, `clock_out`, `status`
- `leave_requests` table: `employee_id`, `type` (annual, sick, unpaid), `start_date`, `end_date`, `status`, `approved_by`
- Payroll processing: generate payslips from salary + attendance + leave
- `payslips` table: `employee_id`, `period_start`, `period_end`, `basic_pay`, `allowances`, `deductions`, `net_pay`, `status`, `paid_at`
- Driver-specific additions:
  - Driver settlement per trip (km-based allowance, overnight allowance, etc.)
  - Link to Driver model, track license/medical/permit expiry
  - Driver advance tracking (cash advance against future settlements)
- Vue pages: employee list, driver settlement sheet, attendance log, leave calendar

**Dependencies:** Users (existing), Document Management (1.1) for employee documents

---

## Phase 8 — Corporate

*Builds on Phases 4–7.*

### 8.1 Full Accounting / General Ledger

Double-entry accounting engine with auto-posting from operational modules.

**Requirements:**
- `chart_of_accounts` table: `code`, `name`, `type` (asset, liability, equity, revenue, expense), `is_active`, `parent_id`
- `journal_entries` table: `reference`, `description`, `date`, `created_by`, `status`
- `journal_entry_lines` table: `journal_entry_id`, `account_id`, `debit`, `credit`, `notes`
- Auto-posting from:
  - Invoices → Accounts Receivable + Revenue
  - Payments → Cash/Bank + Accounts Receivable
  - Purchase Orders received → Inventory + Accounts Payable
  - Fuel dispense → Fuel Expense
  - Payroll → Salary Expense + Bank/Wages Payable
  - Trip costs → Cost of Goods Sold
- Financial statements: Trial Balance, Profit & Loss, Balance Sheet, Cash Flow Statement
- Fiscal year management with opening/closing
- Cost center tracking (per branch, per department)
- Vue pages: chart of accounts, journal entry create, financial reports

**Dependencies:** Invoicing (4.1), Procurement (2.1), Fuel Tracking (2.2), HR/Payroll (7.3)

---

## Phase 9 — Advanced Operations

*Builds on multiple preceding phases.*

### 9.1 Advanced Scheduling / Calendar

Visual drag-and-drop scheduling for pickups, deliveries, driver shifts, and maintenance.

**Requirements:**
- Calendar view (weekly/daily) showing:
  - Scheduled pickups and deliveries per vehicle
  - Driver shifts and assignments
  - Maintenance downtime (5.2)
  - Yard dock reservations (3.2)
- Drag-and-drop rescheduling
- `time_slots` table: `order_id`, `vehicle_id`, `driver_id`, `scheduled_start`, `scheduled_end`, `type` (pickup, delivery, maintenance), `status`
- Conflict detection (vehicle double-booked, driver over-capacity)
- Vue component: FullCalendar integration with custom event rendering

**Dependencies:** Orders (existing), Drivers/Vehicles (existing), Maintenance (5.2), POD (5.3), Yard/Dock (3.2)

---

### 9.2 Multi-Branch / Multi-Warehouse

Support for multiple operational branches with data isolation and consolidated reporting.

**Requirements:**
- `branches` table: `name`, `code`, `address`, `phone`, `email`, `is_active`
- Add `branch_id` to: vehicles, drivers, orders, invoices, warehouses, employees, fuel tanks
- Branch-level user permissions (view own branch only vs all branches)
- Branch-level inventory (warehouse per branch)
- Branch-level fuel storage (tanks per branch)
- Branch-level P&L reporting using Accounting (8.1) cost centers
- Inter-branch transfers (vehicles, inventory, fuel)

**Dependencies:** Accounting (8.1) for branch P&L, Inventory (5.1), Fuel Tracking (2.2), Vehicles/Drivers (existing)

---

### 9.3 Load Optimization

Algorithmic optimization of load assignment to maximize vehicle utilization.

**Requirements:**
- Capacity calculation per vehicle (volume/weight/pallet positions)
- Multi-stop route sequencing (shortest path or least-cost)
- Load consolidation (group orders going in the same direction)
- Optimization engine (integration with external API like Routific/Route4Me or custom solver)
- What-if simulation UI
- Carrier rate comparison for spot loads
- Vue pages: load planner with drag-and-drop order-to-vehicle assignment

**Dependencies:** Scheduling (9.1), Routes (existing), Vehicles (existing), Rate Engine (1.2)

---

### 9.4 Public API & Webhooks

Expose system capabilities for third-party integration and EDI.

**Requirements:**
- RESTful API with API key authentication (separate from Sanctum)
- Rate limiting per key
- Endpoints: orders, tracking, rates, quotes, invoices, PODs, fuel usage
- Webhooks: `order.created`, `order.status_changed`, `delivery.confirmed`, `invoice.sent`, `invoice.paid`, `payment.received`, `fuel.dispensed`
- Webhook delivery log with retry mechanism (exponential backoff)
- Developer documentation (OpenAPI/Swagger 3.0)
- EDI support for large shipper customers (850, 856, 810 EDI transaction sets)

**Dependencies:** Stable modules across all phases

---

## Implementation Order Summary

```
Phase 1 ─── Foundation
  1.1  Document Management
  1.2  Rate / Tariff Engine

Phase 2 ─── Supply & Fuel
  2.1  Procurement / Purchasing
  2.2  Pump-to-Tank Fuel Tracking

Phase 3 ─── Commercial
  3.1  Contract Management
  3.2  Yard & Dock Management

Phase 4 ─── Revenue
  4.1  Billing & Invoicing
  4.2  Accounts Receivable

Phase 5 ─── Operations
  5.1  Warehouse / Inventory Management
  5.2  Preventive Maintenance
  5.3  Proof of Delivery

Phase 6 ─── Cost Intelligence
  6.1  ELD / HOS Compliance
  6.2  Trip Costing & Profitability

Phase 7 ─── Engagement
  7.1  Customer Notifications
  7.2  Returns / Reverse Logistics
  7.3  HR / Payroll

Phase 8 ─── Corporate
  8.1  Full Accounting / General Ledger

Phase 9 ─── Advanced
  9.1  Advanced Scheduling / Calendar
  9.2  Multi-Branch / Multi-Warehouse
  9.3  Load Optimization
  9.4  Public API & Webhooks
```

Each phase assumes all previous phases are complete. Within a phase, items can be built in
any order unless a dependency is noted.
