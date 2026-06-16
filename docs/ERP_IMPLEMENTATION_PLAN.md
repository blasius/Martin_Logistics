# Martin Logistics — ERP Implementation Plan

## Overview

This document defines a phased build plan to evolve the current fleet operations platform into a
complete Logistics ERP. Phases are ordered so that no feature is built before its dependencies exist.

The plan is grounded in the real operational workflow:

```
Vehicle arrives at yard after trip
  ↓
Submits repair request → 2-level approval → parts purchase → repair → release
  ↓
Vehicle assigned to next trip
  ↓
Fuel dispensed based on route + 50L reserve → departure
  ↓
Route deviation monitored → auto-ticket if violated
  ↓
Post-trip fuel consumption flagged if abnormal
  ↓
Back to yard → repeat
```

---

## Dependency Map

```
Phase 1: Foundation
  Document Management ──────┬──→ Repair Requests (print receipts at each step)

Phase 2: Workshop
  Spare Parts Inventory      │
  └──→ Repair Request & Approval Workflow
        └──→ Procurement (parts purchasing)
              └──→ Workshop Dashboard (real-time status)

Phase 3: Fuel
  In-House Fuel Station ──── Routes (existing) → automated dispensing
  └──→ Route-Linked Dispensing (ratio + 50L reserve check)
        └──→ Post-Trip Consumption Analysis → flag bad drivers

Phase 4: Route Intelligence
  Deviation Detection Engine (Telemetry + Route geometry)
  └──→ Auto-Ticket Creation & Escalation Workflow

Phase 5: Operations
  Preventive Maintenance     ←── Parts + Workshop
  Proof of Delivery          ←── Trips
  Yard & Dock Management

Phase 6: Commercial
  Rate / Tariff Engine ──→ Contract Management

Phase 7: Revenue
  Billing & Invoicing ──→ Accounts Receivable

Phase 8: Engagement
  Customer Notifications
  Returns / Reverse Logistics
  HR / Payroll (driver settlements)

Phase 9: Corporate
  Full Accounting / General Ledger

Phase 10: Advanced
  Advanced Scheduling / Calendar
  Multi-Branch / Multi-Warehouse
  Load Optimization
  Public API & Webhooks
```

---

## Phase 1 — Foundation

*No external dependencies. Everything else builds on these.*

### 1.1 Document Management

A centralized file repository with operational document templates and print receipts.
Inspired by the bank counter workflow: each transaction is recorded in the system, a receipt
is printed, and the stakeholder signs to keep a legal paper record.

**Requirements:**
- `documents` table (polymorphic: `documentable_type`, `documentable_id`)
- Fields: `name`, `file_path`, `type` (repair_request, approval_slip, parts_issue, release_note, fuel_receipt, etc.), `expiry_date`, `notes`, `uploaded_by`
- File upload via S3 or local disk with access control
- Expiry alerts (cron checks documents expiring within N days)
- **Print templates** per document type — printable receipts with signature lines
- Vue components: document list, upload modal, expiry badge, print dialog

**Why first:** Every operational workflow (workshop, fuel, deviations) generates paper that needs a
system record and a signed printout for legal/audit purposes.

---

### Real-World Outcome After Phase 1

The system can store and print any document type needed in daily operations. When a mechanic
submits a repair request, the system prints a receipt with the request details and a signature
line — the mechanic signs it and the paper copy is filed for legal records. When a manager approves
it, a second receipt prints. When fuel is dispensed, a third receipt prints. The same pattern
extends to every operational step. Existing vehicle photos, driver license scans, and contract
files can also be uploaded and tagged with expiry dates so the system reminds you before they
expire. No more lost paper sheets or digging through filing cabinets — everything has a digital
record and a clean signed printout when you need it.

---

## Phase 2 — Workshop & Maintenance

*Builds on Phase 1. Covers the full workshop lifecycle: repair request → approval → parts purchase → repair → release.*

### 2.1 Spare Parts Inventory

A catalog of spare parts used across the fleet, with stock levels and reorder points.

**Requirements:**
- `parts` table: `sku`, `name`, `description`, `category` (engine, brake, electrical, body, tires, etc.), `unit_of_measure`, `unit_price`, `compatible_vehicle_makes`
- `warehouses` table: `name`, `code`, `location`, `is_active` (workshop stores)
- `stock_levels` table: `warehouse_id`, `part_id`, `quantity`, `min_quantity` (reorder point)
- `stock_movements` table: `warehouse_id`, `part_id`, `quantity`, `type` (in, out, adjust, transfer), `reference_type`, `reference_id`, `user_id`, `notes`
- Reorder alerts when stock falls below minimum
- Vue pages: parts catalog, stock levels, stock movement log

**Why first:** Workshop cannot function without knowing what parts are in stock.

---

### 2.2 Repair Request & Approval Workflow

Digitizes the paper-based workshop process for 120+ trucks. Each step generates a system
record and a printable receipt signed by the responsible person (bank counter model).

**Workflow:**
1. **Request** — Mechanic submits repair request for a vehicle
2. **Approval 1** — Logistics Manager approves or rejects
3. **Approval 2** — Operations Manager approves or rejects
4. **Parts picklist** — Workshop picks parts from inventory (or triggers procurement if out of stock)
5. **Repair** — Assigned mechanic works on vehicle
6. **Release** — Vehicle marked as road-ready and released from workshop

**Step-by-step requirements:**

*Step 1 — Request:*
- `repair_requests` table: `reference`, `vehicle_id`, `mechanic_id` (user), `driver_id` (who reported issue), `type` (mechanical, electrical, body, tire, brake, etc.), `priority` (low, medium, high, critical), `description`, `status` (draft, pending_approval, approved, parts_pending, in_progress, completed, released, cancelled), `submitted_at`
- `repair_request_items` table: `repair_request_id`, `description`, `part_id` (nullable, from 2.1), `estimated_quantity`, `estimated_unit_price`, `estimated_total`, `actual_quantity`, `actual_unit_price`, `actual_total`
- Attach photos or videos of the issue via Document Management (1.1)
- Print receipt: request form printed → mechanic signs → filed

*Step 2 & 3 — Two-level approval:*
- `approvals` table (polymorphic): `approvable_type`, `approvable_id`, `approver_id`, `approver_role`, `stage` (1 or 2), `status` (pending, approved, rejected), `comment`, `decided_at`
- Stage 1: Logistics Manager approves → Stage 2: Operations Manager approves
- Rejection at either stage sends request back to mechanic with comments
- Print receipt: approval slip printed → approver signs → filed

*Step 4 — Parts fulfillment:*
- If parts in stock → pick from inventory (stock movement out)
- If parts out of stock → trigger Procurement (2.3)
- Print receipt: parts issue slip printed → storekeeper signs → filed

*Step 5 — Repair execution:*
- `repair_assignments` table: `repair_request_id`, `mechanic_id` (user), `assigned_at`, `started_at`, `completed_at`
- Mechanics log time spent and actual parts used (vs estimated)
- Print receipt: work completion slip printed → mechanic signs → filed

*Step 6 — Release:*
- `repair_releases` table: `repair_request_id`, `released_by`, `released_at`, `odometer_at_release`, `notes`
- Vehicle status changes from `in_workshop` to `available`
- Print receipt: release note printed → mechanic + supervisor sign → filed

*Dashboard & Reporting:*
- Real-time board: which trucks are in workshop, assigned mechanic, status, ETA
- Cost per repair, cost per vehicle, cost per part category
- Average repair time by type and by mechanic
- Parts consumption trends

**Dependencies:** Document Management (1.1) for print receipts, Spare Parts (2.1) for parts tracking

---

### 2.3 Procurement for Spare Parts

Purchase orders for parts not in stock (triggered by repair workflow).

**Requirements:**
- `vendors` table: `name`, `contact`, `email`, `phone`, `address`, `tin`, `payment_terms`, `supply_categories`
- `purchase_orders` table: `reference`, `vendor_id`, `repair_request_id` (nullable link), `order_date`, `expected_date`, `status` (draft, sent, confirmed, partially_received, received, cancelled), `notes`, `total_amount`, `currency_id`
- `po_items` table: `purchase_order_id`, `part_id`, `description`, `quantity`, `unit_price`, `total`
- Receiving workflow: mark items as received (partial supported) → auto-update stock levels
- PO attachments via Document Management (1.1)
- Vue pages: vendor list, PO list, PO create, PO receive

**Dependencies:** Spare Parts (2.1), Document Management (1.1)

---

### 2.4 Workshop Dashboard

A real-time operational board for the workshop floor.

**Requirements:**
- Live status cards showing:
  - Total trucks in workshop
  - Trucks by status (pending approval, parts pending, in progress, ready for release)
  - Trucks by mechanic assignment
  - High-priority / overdue repairs
- Vehicle detail view: current repair request, estimated completion time, parts cost so far
- Time tracking: expected vs actual completion
- Cost tracking: estimated vs actual parts + labor per repair
- Vue pages: workshop dashboard (kanban-style), repair request timeline per vehicle

**Dependencies:** Repair Request Workflow (2.2), Procurement (2.3)

---

### Real-World Outcome After Phase 2

When a truck comes into the yard after a trip and the driver reports an issue, the mechanic opens
the system, selects the truck, describes the problem, estimates which parts are needed and how
much they cost, and hits submit. The system prints a repair request form that the mechanic signs.

The Logistics Manager logs in, sees all pending requests sorted by priority, and approves or
rejects with a comment. If approved, it goes to the Operations Manager for the second approval.
Each approval prints a slip that gets signed and filed.

If the parts are in the workshop store, the storekeeper picks them, the system deducts them from
stock, and a parts issue slip prints. If parts are out of stock, the system flags it and the
manager creates a purchase order to the vendor right there — no separate email or phone call.
When the parts arrive, the receiving clerk marks them received and stock updates automatically.

The workshop manager opens the dashboard and sees all 120+ trucks at a glance: which are waiting
for approval, which have parts on order, which are being worked on, which mechanic is assigned,
and how long each repair is expected to take. He can see at a glance that Truck ABC-123 has been
in the workshop for 3 days and is overdue, and that the engine parts for Truck XYZ-456 are
still waiting at the supplier.

When the repair is done, the mechanic marks it complete, a supervisor inspects and releases the
truck, and the vehicle status changes from `in_workshop` to `available` in the system. A release
note prints with both signatures.

No more clipboards, no more walking to find the manager, no more wondering where a truck is in
the workshop process, no more Excel sheets to track parts costs.

---

## Phase 3 — Fuel Management

*Builds on Phase 2 (vendors for fuel suppliers). Integrates with existing Routes module to automate dispensing.*

### 3.1 In-House Fuel Station Management

Manage the on-site fuel station that supplies all fleet vehicles.

**Requirements:**

*Fuel Storage:*
- `fuel_tanks` table: `code`, `capacity` (liters), `current_level` (liters), `fuel_type` (diesel, petrol), `last_calibrated_at`, `is_active`
- Tank dip readings / level tracking (manual entry or sensor)
- Low-stock alert when tank level drops below reorder threshold

*Supplier Deliveries:*
- `fuel_deliveries` table: `supplier_id` (from vendors 2.3 or dedicated fuel supplier), `fuel_type`, `quantity`, `unit_price`, `total_amount`, `invoice_reference`, `tank_id`, `delivered_at`, `received_by`, `notes`
- Fuel price tracking per delivery (price variance reports)

*Dispensing:*
- `fuel_dispense` table: `vehicle_id`, `driver_id`, `tank_id`, `quantity`, `odometer_at_dispense`, `dispensed_at`, `dispensed_by`, `trip_id` (nullable), `route_id` (nullable)
- Dispensing is semi-automated (see 3.2) — the system suggests the amount, the attendant confirms
- Print receipt: fuel issue slip printed → driver signs → filed

*Reconciliation:*
- Pump-to-tank variance report: (total delivered + opening stock) — (total dispensed + closing stock)
- Fuel loss / theft detection
- Monthly fuel reconciliation

**Dependencies:** Procurement (2.3) for vendor/supplier system, Document Management (1.1) for receipts

---

### 3.2 Route-Linked Fuel Dispensing

Fuel calculation is automated based on the assigned route, vehicle fuel ratio, and minimum reserve policy.

**The business rules:**
- Each vehicle has a registered fuel consumption ratio per route (e.g., Route A = 2.5 km/L)
- Each vehicle must maintain a 50L minimum reserve at all times
- System knows: `current_level`, `route_distance`, `fuel_ratio`, `min_reserve`
- Calculated dispense: `route_consumption = distance / ratio`
- Dispense amount = `route_consumption — (current_level — min_reserve)`
- If `current_level - route_consumption >= min_reserve`, no fuel needed

**Requirements:**

*Vehicle-Route Fuel Ratios:*
- `vehicle_route_fuel_ratios` table: `vehicle_id`, `route_id`, `km_per_liter` (or `liters_per_100km`), `effective_from`, `effective_to`, `notes`
- Admin can set/update ratios per vehicle-route pair

*Dispensing Logic (service class):*
- Input: vehicle, route, current fuel level (from tank or last known)
- Output: recommended dispense amount + explanation
- Edge cases: tank level unknown (force manual entry), ratio not found (fallback to default), route not assigned (flag for manual)

*UI Flow:*
1. Select vehicle
2. Select route (from existing routes, or auto-selected from trip)
3. System calculates dispense amount based on route distance + ratio + current level + 50L reserve
4. Dispenser confirms/overrides amount (override requires reason)
5. System records dispense and updates vehicle's current fuel level
6. Receipt printed → driver signs

**Dependencies:** Routes (existing), Vehicles (existing), Fuel Station (3.1)

---

### 3.3 Post-Trip Fuel Consumption Analysis

After a trip completes, compare actual fuel consumption against the expected ratio and flag anomalies.

**Requirements:**
- `trip_fuel_analysis` table (or computed): `trip_id`, `vehicle_id`, `route_id`, `distance`, `fuel_used` (from dispense records during trip period), `expected_consumption` (distance / ratio), `variance_liters`, `variance_percent`, `flag` (normal, caution, excessive)
- Flag thresholds:
  - < 5% variance → normal
  - 5–15% variance → caution (yellow flag)
  - > 15% variance → excessive (red flag — auto-create support ticket)
- `driver_fuel_rating` table: `driver_id`, `period_start`, `period_end`, `avg_variance_percent`, `total_trips`, `flagged_trips`, `rating` (good, average, poor)
- Dashboard: fuel efficiency by driver, by vehicle, by route
- Auto-ticket creation for excessive consumption (links to Phase 4 escalation pattern)

**Dependencies:** Fuel Dispensing (3.1), Routes (existing), Trips (existing)

---

### Real-World Outcome After Phase 3

A truck is assigned a trip from Kigali to Kampala on Route R-042. The driver goes to the fuel
station attendant, who opens the system and selects the truck. The system already knows which
route the truck is taking. It checks the truck's registered fuel ratio for Route R-042
(e.g., 3.2 km/L), looks up the route distance (430 km), and checks the current fuel level in the
truck (85L). It calculates: 430 ÷ 3.2 = 134L needed for the trip, truck has 85L, minimum reserve
is 50L, so dispense = 134 − (85 − 50) = 99L. The attendant confirms, the pump runs, a receipt
prints with the amount, and the driver signs. No more guessing, no more "just fill it up," no
more disputes over how much fuel a truck should have used.

After the trip is complete, the system compares actual fuel used (tracked from the dispense
record plus any refuels along the way) against the expected 134L. If the truck used 150L
(+12% variance), the system flags it as a caution and the fleet manager sees it on the fuel
efficiency dashboard. If it used 170L (+27% variance, red flag), the system automatically
creates a support ticket for investigation — was there a leak, was the driver taking detours,
or was the load heavier than declared? Over time, the system builds a fuel rating for each
driver, and the manager can see which drivers consistently get good, average, or poor efficiency.

The fuel station manager opens a dashboard showing each tank's current level, when the last
supplier delivery was, and what the pump-to-tank variance is for the month — so if 10,000L
were delivered but only 9,200L were dispensed into trucks, the 800L difference is flagged
for investigation.

---

## Phase 4 — Route Intelligence

*Builds on existing Telemetry and Routes modules. Fully leverages geofencing tables that already exist.*

### 4.1 Deviation Detection Engine

Detect when a vehicle deviates from its assigned route in real time and take action.

**The business scenario:**
- A vehicle is assigned a route (stored as geometry in `routes` table)
- The vehicle transmits GPS telemetry points (stored in `telemetry_points` table)
- The deviation engine periodically checks: is the vehicle's current position within the route corridor?
- If outside for too long → deviation event

**Requirements:**

*Route Corridor:*
- Each route already has geometry (`geometry` column as Geometry type)
- Define a configurable corridor width (e.g., 500m on each side of route line)
- Store corridor as a buffer polygon (or compute on the fly with spatial SQL)

*Deviation Check (cron / scheduler — every 5 minutes):*
- Query all in-transit vehicles (from trips table)
- Get latest telemetry point per vehicle
- Spatial check: is the point within the route corridor?
- If no, start a timer. If still out of corridor after `N` minutes (configurable, e.g., 10 min) → generate deviation event

*Deviation Events:*
- `route_deviations` table: `vehicle_id`, `trip_id`, `route_id`, `detected_at`, `gps_point` (Geometry), `distance_from_route` (meters), `duration_minutes`, `status` (new, investigating, resolved, escalated), `resolved_at`, `notes`
- Dashboard: active deviations, deviation history per vehicle

**Dependencies:** Routes (existing route geometry), Telemetry (existing telemetry_points table), Geofencing tables (existing schema)

---

### 4.2 Auto-Ticket Creation & Escalation Workflow

When a route deviation is detected, automatically create a support ticket, assign it to a dispatcher, and escalate if unresolved.

**Workflow:**

```
Deviation detected
  ↓
Auto-create support ticket (type: route_deviation, priority: based on duration)
  ↓
Auto-assign to dispatcher on duty
  ↓
Dispatcher reviews route deviation (stops, calls driver)
  ├── Reasonable deviation (traffic, road closure) → resolve ticket → close
  └── Unauthorized deviation → escalate to Operations Manager
        └── Escalate to Director of Operations if still unresolved after N hours
```

**Requirements:**

*Integration with existing Support Ticket system:*
- `support_tickets` is extended with: `source` field (manual, auto_route_deviation, auto_fuel_flag, etc.)
- Route deviation ticket auto-populates: vehicle, driver, route, deviation distance, duration, GPS coordinates
- Assign to dispatcher (role-based: get first available with dispatcher role)

*Escalation:*
- `escalation_rules` table: `ticket_type`, `level`, `escalate_after_hours`, `assign_to_role`
- Default rule: route deviation ticket not resolved in 2 hours → escalate to Operations Manager
- Second level: not resolved in 6 hours → escalate to Director of Operations
- Escalation updates: ticket reassigned, notification sent, escalation history logged

*Dispatcher UI:*
- Dedicated "Route Alerts" queue in the dispatcher dashboard
- Map view showing: current vehicle position, planned route, deviation point
- One-click actions: "Resolve" (with note), "Call Driver" (if phone integration), "Escalate"
- Ticket thread for communication (existing Support Ticket Message system)

*Notification:*
- Pusher/Echo broadcast when a new deviation ticket is created
- Email/SMS to dispatcher on duty (if not viewing the dashboard)

**Dependencies:** Deviation Detection (4.1), Support Tickets (existing), User Roles (existing)

---

### Real-World Outcome After Phase 4

A truck is en route from Kigali to Kampala on Route R-042. The system checks every 5 minutes:
is the truck's GPS position within 500 meters of the planned route? The driver takes an
unauthorized detour — maybe to pick up a personal cargo or because of a road closure. After
10 minutes outside the corridor, the system automatically creates a support ticket with the
truck number, driver name, route details, how far off-route the truck is, and how long it has
been deviating. The ticket is assigned to the dispatcher on duty.

The dispatcher opens the "Route Alerts" queue and sees the deviation on a map — the truck's
current position, the planned route line, and where it went off. He calls the driver. The
driver says there's a market day blocking the usual road and adds 20 minutes. The dispatcher
notes it as a reasonable deviation and resolves the ticket. Done.

But if the driver doesn't answer, or the dispatcher finds the deviation is unauthorized, he
escalates to the Operations Manager. If the Operations Manager doesn't act within 2 hours,
it escalates again to the Director of Operations. Every escalation is logged, every phone
call is noted on the ticket, and at the end of the month you can see a report of all deviation
events, their causes, and how quickly they were resolved.

The same pattern applies to the fuel flags from Phase 3 — a driver who exceeded 15% fuel
consumption automatically gets a support ticket created, assigned to the fleet manager, and
can be escalated the same way if needed.

---

## Phase 5 — Operations Deepening

*Builds on Phases 1–4.*

### 5.1 Preventive Maintenance

Move beyond reactive repairs (Phase 2) to scheduled maintenance based on km or time intervals.

**Requirements:**
- `maintenance_schedules` table: `vehicle_id`, `type` (oil_change, tire_rotation, brake_check, inspection, general), `interval_km`, `interval_days`, `last_done_at`, `last_done_km`, `is_active`
- Auto-generate repair requests (using Phase 2.2 workflow) when schedule is due
- Parts usage tracking via Spare Parts Inventory (2.1)
- Maintenance cost reporting per vehicle
- Vue pages: schedule management, calendar view

**Dependencies:** Repair Workflow (2.2), Spare Parts (2.1)

---

### 5.2 Proof of Delivery (POD)

Electronic POD workflow for drivers and customers.

**Requirements:**
- `proofs_of_delivery` table: `order_id`, `trip_id`, `delivered_at`, `received_by_name`, `received_by_relation`, `signature_data` (base64 or SVG), `notes`, `gps_lat`, `gps_lng`
- Delivery photos via Document Management (1.1) polymorphic link
- Driver mobile endpoint for POD submission (photos + signature capture)
- Customer portal shows POD for delivered orders
- POD PDF generation (delivery receipt)
- Vue pages: POD view, POD list per order

**Dependencies:** Orders + Trips (existing), Document Management (1.1)

---

### 5.3 Yard & Dock Management

Manage truck check-in/check-out, dock door assignment, and loading/unloading schedules.

**Requirements:**
- `yard_entries` table: `vehicle_id`, `driver_id`, `check_in_at`, `check_out_at`, `purpose` (loading, unloading, parking, workshop), `dock_door_id`, `notes`
- `dock_doors` table: `code`, `warehouse_id` (nullable), `is_occupied`, `current_vehicle_id`, `occupied_since`
- Yard capacity dashboard (doors occupied / free, trucks waiting)
- Check-in/check-out UI or mobile endpoint
- Waiting time tracking (check-in to dock assignment)
- Vue pages: yard dashboard, dock management, check-in log

**Dependencies:** Vehicles + Drivers (both exist)

---

### Real-World Outcome After Phase 5

With Phases 1-4 solid, operations is running on the system: workshop, fuel, and route monitoring
are all digitized. Now Phase 5 takes the data already being generated and turns it into real-time
operational dashboards.

An operations manager walks in and opens "Today's Operations" — a dashboard showing every truck's
current status: 45 en route, 12 in workshop (with repair stage and ETA), 8 being fueled,
3 awaiting dispatch, 2 idle. Each status has a count and a color. He can filter by yard, by
region, or by customer.

He clicks "in workshop" and sees all 12 trucks sorted by repair stage: 3 awaiting approval,
2 waiting for parts, 4 being repaired, 3 awaiting release. He can see that Truck ABC-123 has
been in the workshop for 4 days for a simple brake fix — the mechanic was waiting for a part
that arrived yesterday — and he can send a system notification to the workshop manager.

A dispatcher uses the dispatch scheduling screen: she selects a customer, a loading point, and
a destination, and the system shows available trucks sorted by distance to the pickup point,
their current fuel level, and whether they're due for maintenance. She assigns the truck, the
driver gets a notification, and the trip is created in the system — no more phone calls to
ask "which trucks are free?"

When she dispatches a truck, the system automatically checks: is this truck due for a preventive
maintenance within 500 km? If yes, the system suggests assigning a different truck instead.
Preventive maintenance schedules are configured per vehicle — every 5,000 km, or every 3 months
for engine oil — and the system flags trucks approaching their next service.

At month end, the operations dashboard shows: total trips completed, average turnaround time,
on-time delivery rate, and a breakdown of delays by cause (workshop, fueling, driver, route).
The operations manager can see at a glance that the biggest delay cause this month was "waiting
for workshop parts" and can address the parts procurement process.

---

## Phase 6 — Commercial

*Builds on Phases 1–5.*

### 6.1 Rate / Tariff Engine

A pricing engine that calculates freight costs automatically.

**Requirements:**
- `rate_cards` table: `name`, `effective_from`, `effective_to`, `client_id` (nullable for default), `is_active`
- `rate_card_items` table: `rate_card_id`, `charge_name`, `charge_type` (per_km, per_kg, per_container, flat, per_stop, fuel_surcharge), `amount`, `currency_id`, `min_charge`, `max_charge`
- `rate_calculator` service class: given origin/destination/distance/weight/vehicle type → compute total
- Admin CRUD UI for rate cards
- Preview calculated rate on Order create/edit

**Dependencies:** None (standalone)

---

### 6.2 Contract Management

Customer service agreements that tie clients to pricing terms, SLAs, and validity periods.

**Requirements:**
- `contracts` table: `reference`, `client_id`, `type` (monthly, yearly, spot), `rate_card_id`, `start_date`, `end_date`, `status`, `terms`, `sla_response_hours`, `sla_resolution_hours`
- Contract attachments via Document Management (1.1)
- Auto-expiry notifications (30/14/7 days before end)
- SLA breach detection
- Vue pages: contract list, contract detail, create/edit form

**Dependencies:** Rate Engine (6.1), Document Management (1.1)

---

### Real-World Outcome After Phase 6

A sales manager opens the rate card screen and sees a list of active tariffs — standard rates
per km per vehicle type, fuel surcharge percentages, and customer-specific negotiated rates.
When a new client asks for a quote, she creates a rate card for them: "USD 2.50/km for 20-ton
trucks, 15% fuel surcharge, $50/hr loading wait time." The tariff engine calculates that for a
300 km trip with a 20-ton truck and 2 hours loading wait: (300 × 2.50) + (300 × 2.50 × 0.15) +
(50 × 2) = $862.50 + $100 = $962.50. She generates a quote PDF and sends it to the client in 2
minutes instead of 20 minutes of manual calculation.

When a trip is completed, the system automatically calculates the invoice amount using the
assigned rate card. No re-typing. The invoice is generated, sent to the client, and tracked.
Finance can see at a glance which invoices are paid, overdue, or disputed.

When a new client signs up, a contract is created with start and end dates, the agreed rate
card is attached, and the system sends reminders 30 days before expiry. If the contract allows
auto-renewal, the system handles it automatically. If not, the account manager gets a
notification to negotiate.

Over time, the system builds a customer profitability report: for each client, total trips,
total revenue, total costs (fuel + maintenance + driver), and margin. The sales manager can
see that Client A generates 20% of revenue but has a 35% margin, while Client B generates 15%
of revenue but has a 55% margin — and can decide where to focus.

---

## Phase 7 — Revenue

*Builds on Phase 6.*

### 7.1 Billing & Invoicing

Generate invoices from orders/trips, applying contract rates automatically.

**Requirements:**
- `invoices` table: `reference`, `client_id`, `contract_id`, `order_id` (nullable), `issue_date`, `due_date`, `status` (draft, sent, paid, overdue, cancelled), `subtotal`, `tax_total`, `discount_total`, `total`, `currency_id`, `notes`
- `invoice_items` table: `invoice_id`, `description`, `charge_type`, `quantity`, `unit_price`, `total`
- Auto-generate from delivered orders using Rate Engine (6.1) + Contract rates (6.2)
- Manual invoice creation with line items
- Invoice PDF generation
- Credit notes and debit notes
- Vue pages: invoice list, invoice detail, invoice create

**Dependencies:** Rate Engine (6.1), Contract Management (6.2), Orders (existing)

---

### 7.2 Accounts Receivable

Track customer payments and outstanding balances.

**Requirements:**
- Extend existing `payments` table with `invoice_id`, `payment_method`, `reference`, `notes`
- Payment reconciliation against invoices
- Aging report (30/60/90+ days overdue)
- Automated payment reminders
- Client statements (PDF)
- Vue pages: payment list, aging report, client statement

**Dependencies:** Invoicing (7.1)

---

### Real-World Outcome After Phase 7

The finance manager opens the billing dashboard and sees invoices for the month: total invoiced,
total paid, total overdue, total disputed. She can see that Client X has an invoice from 45 days
ago that's still unpaid — the system has already sent two automated reminders. She clicks "Send
Final Notice" and a PDF reminder goes out with the overdue amount, late fee calculation, and
payment instructions.

When a payment arrives (or is manually recorded as received), the invoice is marked paid and the
client's account is credited. The customer portal shows the client their invoice history and
payment status in real time.

At month end, the system generates revenue reports: revenue by client, by route, by vehicle
type, by month. The finance team exports the data for the accounting software instead of
manually cross-referencing trip sheets against payments.

The aging report shows all outstanding invoices grouped by 0-30, 31-60, 61-90, and 90+ days.
The finance manager can see that 15% of outstanding revenue is over 60 days and decide to
freeze credit for those clients until they pay.

---

## Phase 8 — Engagement

*Builds on Phases 4–7.*

### 8.1 Customer Notifications

Automated email/SMS/push notifications triggered by system events.

**Requirements:**
- `notification_templates` table: `key` (order_confirmed, in_transit, delivered, invoice_sent, payment_received, pod_available, maintenance_due, etc.), `subject`, `body_html`, `channels` (email, sms)
- `notification_log` table: `notifiable_type`, `notifiable_id`, `channel`, `template_key`, `sent_at`, `status`, `error`
- Event → listener pipeline for key lifecycle events
- SMS integration (Twilio or similar)
- Real-time in-app notifications via Pusher/Echo
- Vue components: notification bell dropdown, notification list

**Dependencies:** Orders (existing), Invoicing (7.1), POD (5.2)

---

### 8.2 Returns / Reverse Logistics

Handle customer returns with inspection and restocking.

**Requirements:**
- `return_requests` table: `reference`, `order_id`, `client_id`, `reason`, `status` (pending, approved, rejected, collected, inspected, completed), `requested_at`, `approved_at`, `notes`
- `return_items` table: `return_request_id`, `description`, `quantity`, `condition` (damaged, wrong_item, expired, good)
- Inspection workflow: document condition, attach photos, decide restock vs disposal
- Credit note generation (links to Invoicing 7.1)
- Vue pages: return request list, return detail with inspection form

**Dependencies:** Orders (existing), Invoicing (7.1) for credit notes

---

### 8.3 HR / Payroll

Employee management, attendance, leave, and payroll — including driver settlements.

**Requirements:**
- `employees` table: `user_id` (nullable), `employee_code`, `department`, `position`, `hire_date`, `salary`, `bank_account`, `status`
- `departments` table: `name`, `code`, `manager_id`
- `attendance` table: `employee_id`, `date`, `clock_in`, `clock_out`, `status`
- `leave_requests` table: `employee_id`, `type`, `start_date`, `end_date`, `status`, `approved_by`
- Payroll processing: generate payslips from salary + attendance + leave
- Driver-specific: driver settlement per trip (km-based allowance, overnight allowance), license/medical/permit expiry tracking, cash advance tracking
- Vue pages: employee list, driver settlement sheet, attendance log, leave calendar

**Dependencies:** Users (existing), Document Management (1.1) for employee documents

---

### Real-World Outcome After Phase 8

The customer portal is now a real business tool for clients. A customer logs in and sees their
dashboard: active orders in transit, recently delivered orders with POD documents, and their
invoice history. They can place a new order by filling out the form — pickup location, delivery
destination, vehicle type needed, and any special instructions. The system estimates the cost
using the rate card on file and shows it before the customer confirms.

When a customer places an order, the system creates it and assigns it to the dispatcher queue
automatically. The customer gets an email confirmation with an order number. As the order
progresses, the customer can check the tracking page and see where their goods are in real time.

If there's a problem — the driver is late, the truck broke down, the delivery address is wrong —
a support ticket is created and the customer can follow it through the portal. They can also
view their proof of delivery once the trip is complete, including the recipient's signature
and delivery photos.

For the logistics company, the customer portal reduces phone calls: customers check their
order status online rather than calling the dispatcher. It also reduces manual order entry:
customers submit their own orders instead of calling or emailing them in.

---

## Phase 9 — Corporate

*Builds on Phases 7–8.*

### 9.1 Full Accounting / General Ledger

Double-entry accounting engine with auto-posting from operational modules.

**Requirements:**
- `chart_of_accounts` table: `code`, `name`, `type` (asset, liability, equity, revenue, expense), `is_active`, `parent_id`
- `journal_entries` table: `reference`, `description`, `date`, `created_by`, `status`
- `journal_entry_lines` table: `journal_entry_id`, `account_id`, `debit`, `credit`, `notes`
- Auto-posting from: invoices, payments, POs received, fuel dispensed, payroll, trip costs
- Financial statements: Trial Balance, P&L, Balance Sheet, Cash Flow
- Fiscal year management with opening/closing
- Cost center tracking (per branch, per department)
- Vue pages: chart of accounts, journal entry create, financial reports

**Dependencies:** Invoicing (7.1), Procurement (2.3), Fuel Tracking (3.1), HR/Payroll (8.3)

---

### Real-World Outcome After Phase 9

The finance team opens the General Ledger and sees the full chart of accounts — assets,
liabilities, revenue, expenses — all configured for the company structure. When an invoice
is generated in Phase 7, a journal entry is automatically created: debit Accounts Receivable,
credit Revenue. When a payment comes in: debit Cash, credit Accounts Receivable. When parts
are purchased for the workshop: debit Workshop Parts Expense, credit Accounts Payable. When
fuel is dispensed: debit Fuel Expense, credit Inventory — Fuel. When payroll is processed:
debit Salary Expense, credit Salary Payable. Every transaction in the system flows directly
to the GL without manual double-entry bookkeeping.

At month end, the CFO generates a Trial Balance, Profit & Loss statement, and Balance Sheet
with one click. The P&L shows revenue from all trips, cost of sales (fuel, driver allowances,
maintenance), and overheads (admin salaries, office rent). The company can see exactly how
profitable the fleet is this month, which clients are the most profitable, and which routes
are losing money — all derived from operational transactions, not manual spreadsheets.

If the company has multiple branches (e.g., Kigali and Kampala yards), costs and revenue can
be tracked per cost center, so each branch's P&L can be reported independently.

---

## Phase 10 — Advanced Operations

*Builds on multiple preceding phases.*

### 10.1 Advanced Scheduling / Calendar

Visual drag-and-drop scheduling for pickups, deliveries, driver shifts, and maintenance.

**Requirements:**
- Calendar view (weekly/daily): pickups, deliveries, driver shifts, maintenance downtime, yard dock reservations
- Drag-and-drop rescheduling
- `time_slots` table: `order_id`, `vehicle_id`, `driver_id`, `scheduled_start`, `scheduled_end`, `type`, `status`
- Conflict detection
- FullCalendar integration

**Dependencies:** Orders (existing), Drivers/Vehicles (existing), Maintenance (5.1), POD (5.2), Yard/Dock (5.3)

---

### 10.2 Multi-Branch / Multi-Warehouse

Support for multiple operational branches.

**Requirements:**
- `branches` table: `name`, `code`, `address`, `phone`, `email`, `is_active`
- Add `branch_id` to: vehicles, drivers, orders, invoices, warehouses, employees, fuel tanks
- Branch-level user permissions
- Branch-level inventory and fuel storage
- Branch-level P&L reporting

**Dependencies:** Accounting (9.1), Inventory (2.1), Fuel (3.1)

---

### 10.3 Load Optimization

Algorithmic optimization of load assignment.

**Requirements:**
- Capacity calculation per vehicle (volume/weight)
- Multi-stop route sequencing
- Load consolidation
- Optimization engine (external API or custom solver)
- Load planner UI with drag-and-drop

**Dependencies:** Scheduling (10.1), Routes (existing), Vehicles (existing), Rate Engine (6.1)

---

### 10.4 Public API & Webhooks

Expose system capabilities for third-party integration.

**Requirements:**
- RESTful API with API key authentication
- Rate limiting per key
- Endpoints: orders, tracking, rates, quotes, invoices, PODs, workshop status, fuel usage
- Webhooks: order.*, delivery.confirmed, invoice.*, payment.*, fuel.dispensed, route_deviation.created
- Webhook delivery log with retry
- OpenAPI / Swagger documentation
- EDI support for large shipper customers

**Dependencies:** Stable modules across all phases

---

### Real-World Outcome After Phase 10

A fleet scheduler opens the advanced scheduling calendar and sees a drag-and-drop timeline of
all trips for the next week. Trucks are color-coded by status (available, in trip, in workshop,
reserved). She can click on a truck to see its upcoming maintenance schedule, current fuel level,
and last trip details. She drags a new trip onto a truck and the system checks: Is the truck
available? Is there enough fuel? Is maintenance due soon? If all checks pass, the trip is
created and the driver is notified.

If the company has multiple branches (Kigali, Kampala, Mombasa), the system manages inventory,
trucks, and drivers per branch. A truck from the Kigali branch can be dispatched on a trip to
Mombasa and the system tracks which branch it belongs to, where it currently is, and when it's
expected back.

For high-volume routes, the load optimization engine suggests how to combine multiple smaller
shipments onto a single truck to maximize utilization. Instead of two trucks running at 60%
capacity, the system suggests consolidating into one truck at 95% capacity, saving fuel, driver
cost, and maintenance.

A key client who moves 500 shipments per month integrates their system directly via the API.
When they create an order in their warehouse management system, it auto-creates an order in
Martin Logistics via the API, a truck is dispatched automatically based on pre-configured rules,
and the client receives a webhook when the delivery is completed with POD data. No phone calls,
no emails, no manual order entry — the entire flow is automated end to end.

---

## Implementation Order Summary

```
Phase 1  ─── Foundation
  1.1  Document Management (print receipts, legal records)

Phase 2  ─── Workshop & Maintenance
  2.1  Spare Parts Inventory
  2.2  Repair Request & Approval Workflow (2-level)
  2.3  Procurement for Spare Parts
  2.4  Workshop Dashboard (real-time)

Phase 3  ─── Fuel Management
  3.1  In-House Fuel Station Management
  3.2  Route-Linked Fuel Dispensing (ratio + 50L reserve)
  3.3  Post-Trip Fuel Consumption Analysis

Phase 4  ─── Route Intelligence
  4.1  Deviation Detection Engine
  4.2  Auto-Ticket Creation & Escalation Workflow

Phase 5  ─── Operations
  5.1  Preventive Maintenance
  5.2  Proof of Delivery
  5.3  Yard & Dock Management

Phase 6  ─── Commercial
  6.1  Rate / Tariff Engine
  6.2  Contract Management

Phase 7  ─── Revenue
  7.1  Billing & Invoicing
  7.2  Accounts Receivable

Phase 8  ─── Engagement
  8.1  Customer Notifications
  8.2  Returns / Reverse Logistics
  8.3  HR / Payroll

Phase 9  ─── Corporate
  9.1  Full Accounting / General Ledger

Phase 10 ─── Advanced
  10.1 Advanced Scheduling / Calendar
  10.2 Multi-Branch / Multi-Warehouse
  10.3 Load Optimization
  10.4 Public API & Webhooks
```

Each phase assumes all previous phases are complete. Within a phase, items can be built in
any order unless a dependency is noted.
