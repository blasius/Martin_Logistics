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
Vehicle assigned to next trip ──→ Trip owner assigned (one dispatcher per trip)
  ↓
Fuel dispensed based on route + 50L reserve → departure
  ↓
Route deviation monitored → auto-ticket goes to trip's dispatcher
  ↓
Post-trip fuel consumption flagged if abnormal → auto-ticket goes to trip's dispatcher
  ↓
Back to yard → repeat
```

---

## Dependency Map

```
Phase 1: Foundation
  Document Management ──────┬──→ Legal documents (payment receipts, POD, contracts)
  Role Builder & Permissions ──→ All modules (who can do what)

Phase 2: Workshop
  Spare Parts Inventory      │
  └──→ Repair Request & Approval Workflow
        └──→ Procurement (parts purchasing)
              └──→ Workshop Dashboard (real-time status)

Phase 3: Fuel
  In-House Fuel Station ──── Routes (existing) → automated dispensing
  └──→ Route-Linked Dispensing (ratio + 50L reserve check)
        └──→ Post-Trip Consumption Analysis → flag bad drivers

Phase 4: Route Intelligence & Trip Ownership
  Trip Ownership & Dispatcher Assignment (one dispatcher per trip, history preserved)
  └──→ Deviation Detection + Delay Detection (Telemetry + Route geometry + schedules)
        └──→ Auto-Ticket Creation & Escalation Workflow (Dispatcher → Mgr → Dir Ops → MD)

Phase 5: Operations
  Preventive Maintenance     ←── Parts + Workshop
  Proof of Delivery          ←── Trips
  Yard, Dock & Queue Management  ←── Vehicles + all yard services
  Expense Management (catalog + fixed/variable + any-level conversion)  ←── Tickets + Approvals
  Container & Demurrage Tracking  ←── Trips + Vehicles + Warehouses
  Performance Rating & Scoring    ←── Multiple data sources + Human ratings
  Wallet & Ledger                 ←── Users + all source modules (polymorphic)
  Unified Reporting & Analytics   ←── All preceding phases (data sources)

Phase 6: Commercial
  Rate / Tariff Engine ──→ Contract Management
  Order-to-Dispatch Workflow  ←── Rates + Trip Ownership + Fuel + Docs

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

A centralized file repository with print templates for documents that legally require a
signed paper copy. Inspired by the bank counter model: when money changes hands or legal
custody transfers, the system prints a double-copy receipt that both parties sign — one
copy for the company, one for the counterparty.

**Requirements:**
- `documents` table (polymorphic: `documentable_type`, `documentable_id`)
- Fields: `name`, `file_path`, `type` (payment_receipt, proof_of_delivery, fuel_receipt, contract, etc.), `expiry_date`, `notes`, `uploaded_by`
- File upload via S3 or local disk with access control
- Expiry alerts (cron checks documents expiring within N days)
- **Print templates** only for document types that legally require a signed paper record (payment receipts, POD)
- Vue components: document list, upload modal, expiry badge, print dialog

**Why first:** Legal documents (payment proofs, delivery receipts) need a system record and
a printable signature form. Everything else stays digital — no printing for internal steps.

---

### 1.2 Role Builder & Permission Manager

A dedicated UI where a Super Admin can create roles and titles (Logistics Manager, Finance Officer,
Director of Finance, Dispatcher, etc.) with transparent, granular permissions. Every role shows
exactly what powers it has — no hidden access, no guessing who can do what.

**The business problem:**
- Roles and permissions are hardcoded or undocumented — no one knows exactly what a "Logistics
  Manager" can vs cannot do
- When a new hire joins, there's no way to quickly create a custom role matching their
  responsibilities
- No transparency: a dispatcher doesn't know if they can approve expenses; a manager doesn't
  know if they can reassign trips
- Scaling from 6 to 60+ staff requires clear, auditable role definitions

**Requirements:**

*Role & Permission Engine:*
- `roles` table: `name`, `slug`, `description`, `is_super_admin` (boolean — bypasses all checks),
  `is_active`, `created_by`, `created_at`
- `permissions` table: `name`, `slug`, `group` (trips, vehicles, expenses, users, reports, fines,
  containers, fuel, workshop, etc.), `description`
- `role_permissions` pivot: `role_id`, `permission_id`, `allowed` (boolean — can also explicitly deny)
- `user_roles` pivot: `user_id`, `role_id`, `assigned_by`, `assigned_at`

*Permission Granularity (examples — each maps to a feature in this plan):*
- Trips: view_assigned, view_all, create, assign_truck, assign_dispatcher, cancel, approve_deviation
- Expenses: view_own, view_all, create_from_ticket, approve_stage1, approve_stage2, manage_catalog, pay
- Vehicles: view, edit, transfer, deactivate
- Users: view, create, assign_role, manage_permissions
- Workshop: view_queue, assign_mechanic, approve_repair, release
- Fuel: dispense, view_reports, manage_tanks, approve_supplier_delivery
- Finance: view_pending_payments, process_payment, view_reports, reconcile
- Containers: view, track, impute_demurrage
- Reports: view_operational, view_financial, view_driver_performance, export

*Super Admin UI:*
- Role list view: all roles with user count, active status
- Role detail/editor:
  - Role name, description, active toggle
  - Permissions grouped by module (expandable sections)
  - Each permission: checkbox (allow) or explicit deny
  - "Effective permissions" summary showing what this role can actually do in plain language
- User role manager:
  - Select user → assign one or more roles
  - Effective permissions preview: "User X can: approve expenses up to Stage 1, assign trucks, view all trips"
- Audit log: every role/permission change logged with who made it and when

*Built-in Default Roles (seeded on install):*
| Role | Scope |
|------|-------|
| Super Admin | Full access to everything |
| Director of Operations | Approve Stage 2, view all, escalate to MD |
| Logistics Manager | Assign trucks, approve Stage 1, manage expense catalog |
| Operations Manager | Review deviations, reassign trips (legacy role) |
| Dispatcher | View assigned trips, create tickets, convert to expense |
| Finance Officer | View pending payments, process payments, view financial reports |
| Driver | Mobile app: view trips, submit tickets, view wallet, acknowledge |
| Sales Person | View client orders, create truck requests, view rates |
| Customer | Portal: place orders, track, view invoices, rate driver |

**Dependencies:** Users (existing, or seeded via initial setup)

---

### Real-World Outcome After Phase 1

The system stores all operational files in one place: vehicle photos, driver license scans,
contracts, insurance certificates — each tagged with expiry dates so it reminds you before
they expire. When a customer makes a payment, the system prints a double-copy receipt with
signature lines — the cashier and customer both sign, one copy for each party. The same
pattern applies to proof of delivery. No more digging through filing
cabinets; everything has a digital record, and a clean signed printout is available when
the law requires it. Internal workflows (repair requests, approvals, parts movements) live
entirely in the system — no paper.

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

Digitizes the paper-based workshop process for 120+ trucks. All steps are fully digital —
no printing required. Only the final repair cost (if paid in cash) would use Document
Management for a legal receipt.

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

*Step 2 & 3 — Two-level approval:*
- `approvals` table (polymorphic): `approvable_type`, `approvable_id`, `approver_id`, `approver_role`, `stage` (1 or 2), `status` (pending, approved, rejected), `comment`, `decided_at`
- Stage 1: Logistics Manager approves → Stage 2: Operations Manager approves
- Rejection at either stage sends request back to mechanic with comments

*Step 4 — Parts fulfillment:*
- If parts in stock → pick from inventory (stock movement out)
- If parts out of stock → trigger Procurement (2.3)

*Step 5 — Repair execution:*
- `repair_assignments` table: `repair_request_id`, `mechanic_id` (user), `assigned_at`, `started_at`, `completed_at`
- Mechanics log time spent and actual parts used (vs estimated)

*Step 6 — Release:*
- `repair_releases` table: `repair_request_id`, `released_by`, `released_at`, `odometer_at_release`, `notes`
- Vehicle status changes from `in_workshop` to `available`

*Dashboard & Reporting:*
- Real-time board: which trucks are in workshop, assigned mechanic, status, ETA
- Cost per repair, cost per vehicle, cost per part category
- Average repair time by type and by mechanic
- Parts consumption trends

**Dependencies:** Spare Parts (2.1) for parts tracking

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
much they cost, attaches a photo of the damaged part, and hits submit. Done — no paper.

The Logistics Manager opens his pending requests, sees the issue with the photo, and approves
with a comment: "Check brake drum wear too." It moves to the Operations Manager, who approves
as well. Both approvals happen in the system — no walking between offices, no paper slips.

If the parts are in the workshop store, the storekeeper picks them, the system deducts them from
stock. If parts are out of stock, the system flags it and the manager creates a purchase order
to the vendor right in the same screen — no separate email or phone call. When the parts arrive,
the receiving clerk marks them received, and stock updates automatically.

The workshop manager opens the dashboard and sees all 120+ trucks at a glance: which are waiting
for approval, which have parts on order, which are being worked on, which mechanic is assigned,
and how long each repair is expected to take. He sees that Truck ABC-123 has been in the workshop
for 3 days and is overdue, and that the engine parts for Truck XYZ-456 are still waiting at the
supplier.

When the repair is done, the mechanic marks it complete, a supervisor inspects and releases the
truck, and the vehicle status changes from `in_workshop` to `available` in the system.

No more clipboards, no more walking to find the manager, no more wondering where a truck is in
the workshop process, no more Excel sheets to track parts costs. No paper at all — until a cash
payment needs a legal receipt.

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
- All dispensing records are digital. No printing required — this is an internal asset transfer.

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
6. Driver acknowledges in the system (digital record)

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
is 50L, so dispense = 134 − (85 − 50) = 99L. The attendant confirms, the pump runs, and the
system records the dispense digitally. No more guessing, no more "just fill it up," no more
disputes over how much fuel a truck should have used.

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

## Phase 4 — Route Intelligence & Trip Ownership

*Builds on existing Telemetry and Routes modules. Fully leverages geofencing tables that already exist.*

### 4.1 Trip Ownership & Dispatcher Assignment

Assign a single accountable dispatcher to every trip from creation to completion,
eliminating the "6 people, 6 stories" problem. Follows the same pattern as existing
`driver_vehicle_assignments` and `trailer_assignments` tables (active record =
null end date; re-assignment closes old + inserts new; full history preserved).

**The business problem:**
- 6 dispatchers "follow" 120+ trucks with no formal attribution
- Drivers text one dispatcher, call another, and escalate to the manager — no single source of truth
- Fuel flags, deviation tickets, and delays have no clear owner per trip
- Handoffs between shifts or reassignments lose context
- No one can answer "who is responsible for this trip right now?"

**Requirements:**

*Assignment Table:*
- `trip_dispatcher_assignments` table: `id`, `trip_id`, `dispatcher_id` (FK users with dispatcher role),
  `assigned_at`, `unassigned_at` (nullable = currently active), `reason` (nullable — why reassigned,
  e.g. "shift handoff", "workload rebalance", "manager override"), timestamps
- Current dispatcher for a trip = record with null `unassigned_at`
- Re-assignment logic (in a service class, matching the existing pattern):
  - Close current active record: `→ whereNull('unassigned_at') → update(['unassigned_at' => $now])`
  - Insert new record with `assigned_at = $now`
- History query: `trip_dispatcher_assignments` for a trip ID, ordered by `assigned_at` DESC

*Assignment Rules:*
- **Auto-assignment at trip creation:** round-robin across available dispatchers (configurable weight)
- **Manager override:** Operations Manager can reassign any trip to any dispatcher (with reason)
- **Shift handoff:** end-of-shift reassigns all active trips to the incoming dispatcher
- **Reassignment limits monitor:** optional; if a trip gets reassigned >N times (e.g. 3), flag for manager review

*Usage Throughout the System:*
- Every auto-created ticket (fuel flag, route deviation) is assigned to the trip's current dispatcher first
- Dispatcher dashboard: "My Trips" filtered to trips where `current dispatcher = me`
- Operations dashboard: filter trips by dispatcher, see workload balance
- Driver communication: trip detail page shows "Your Dispatcher: Alice (call +234 XXX XXXX)"
- Trip history page shows full dispatcher assignment timeline with reasons

*Dispatcher Views:*
- "My Trips" list: all active trips where `unassigned_at IS NULL AND dispatcher_id = me`
- Each trip card shows: truck, driver, route, status, elapsed time, next action
- "Team Trips" for managers: all trips grouped by dispatcher, color-coded by load
- Reassignment modal: select new dispatcher + enter reason

**Dependencies:** Trips (existing), Users (existing with dispatcher role)

---

### 4.2 Deviation Detection Engine

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

*Delay Detection (added alongside deviation monitoring):*
- Not every problem is a route deviation — trips can also be delayed at origin, at loading, or at delivery
- `delay_events` table: `trip_id`, `vehicle_id`, `delay_type` (late_departure, loading_delay, breakdown,
  traffic, customer_delay, delivery_delay), `detected_at`, `expected_time`, `actual_time`,
  `duration_minutes`, `status` (new, investigating, resolved, escalated), `notes`
- **Late departure detection**: cron checks trips with `departure_time` past due + status still `assigned`
  → auto-create delay event
- **Loading delay**: if check-in at dock (5.3) exceeds expected load time threshold → auto-create delay event
- **Arrival delay**: if ETA has passed + trip still `on_route` → auto-create delay event
- All delay events feed into the same Auto-Ticket system (4.3)

**Dependencies:** Routes (existing route geometry), Telemetry (existing telemetry_points table), Geofencing tables (existing schema)

---

### 4.3 Auto-Ticket Creation & Escalation Workflow

When a route deviation is detected, automatically create a support ticket, assign it to the trip's assigned dispatcher (from 4.1), and escalate if unresolved.

**Workflow:**

```
Problem detected (deviation, delay, fuel flag)
  ↓
Auto-create support ticket
  ↓
Assign to trip's current dispatcher (trip_dispatcher_assignments where unassigned_at IS NULL)
  ↓
Dispatcher reviews
  ├── Resolved → close ticket
  ├── Needs manager input → escalate to Logistics Manager
  │     ├── Resolved → close
  │     └── Needs higher authority → escalate to Director of Operations
  │           ├── Resolved → close
  │           └── Company-wide impact → escalate to Managing Director
  └── At any level: anyone in the chain can also convert the issue to an expense
        with amounts and justification (see Phase 5.4 Expense Management)
```

**Requirements:**

*Integration with existing Support Ticket system:*
- `support_tickets` is extended with: `source` field (manual, auto_route_deviation, auto_delay,
  auto_fuel_flag, etc.)
- Each ticket auto-populates: vehicle, driver, route, context details (deviation distance, delay
  duration, GPS coordinates, etc.)
- Assign to trip's current dispatcher (from `trip_dispatcher_assignments`), fallback to role-based pool

*Escalation:*
- `escalation_rules` table: `ticket_type`, `level`, `escalate_after_hours`, `assign_to_role`
- Default four-level chain:
  - Level 1: Dispatcher → Logistics Manager (unresolved after N hours)
  - Level 2: Logistics Manager → Director of Operations (unresolved after N more hours)
  - Level 3: Director of Operations → Managing Director (unresolved after N more hours)
- Escalation updates: ticket reassigned, notification sent, escalation history logged
- If the trip's dispatcher is reassigned during an active ticket, the ticket stays with the trip's new dispatcher
- Anyone in the chain (dispatcher, manager, director, MD) can convert the issue into an expense
  with amounts and justification (see Phase 5.4)

*Dispatcher UI:*
- Dedicated "Route Alerts" queue showing only tickets for the dispatcher's assigned trips
- "Unassigned Tickets" queue for tickets where no trip dispatcher could be determined
- Map view showing: current vehicle position, planned route, deviation point
- One-click actions: "Resolve" (with note), "Call Driver" (if phone integration), "Escalate"
- Ticket thread for communication (existing Support Ticket Message system)

*Notification:*
- Pusher/Echo broadcast when a new deviation ticket is created
- Email/SMS to dispatcher on duty (if not viewing the dashboard)

**Dependencies:** Trip Ownership (4.1), Deviation Detection (4.2), Support Tickets (existing), User Roles (existing)

---

### Real-World Outcome After Phase 4

A trip is created for Truck ABC-123, Kigali → Kampala. The system automatically assigns it to
Dispatcher Alice (round-robin among 6 dispatchers). The trip detail page shows "Your Dispatcher:
Alice (+250 788 XXX XXX)". When the driver has a question, he calls Alice — not 5 other people.
When Alice is off shift, the incoming dispatcher opens the system, closes Alice's assignments
with reason "end of shift" and takes ownership. The driver now calls the new dispatcher. One
person, one story, at all times.

A fuel flag from Phase 3 fires: the truck used 27% more fuel than expected. The system
auto-creates a support ticket and assigns it to Alice — the trip's current dispatcher. Alice
checks: the driver took a detour through hilly terrain. She notes it, resolves the ticket,
and follows up with the driver about route discipline.

The truck deviates from Route R-042. After 10 minutes outside the 500m corridor, the system
auto-creates another ticket. It goes to Alice, not a random dispatcher. She opens the Route
Alerts queue, sees only her trips' deviations. She calls the driver — market day is blocking
the road, he adds 20 minutes. She notes it, resolves it. Done.

But if Alice can't reach the driver and the ticket is unresolved for 2 hours, it escalates
to the Operations Manager. The escalation chain is clear because there was one accountable
person from the start. If Alice's assignment was reassigned mid-trip (shift change), the
ticket moves with the trip to the new dispatcher — no lost context.

The Operations Manager opens the dashboard and sees: "Alice: 12 trips, 2 tickets this week,
83% resolved within SLA. Bob: 10 trips, 5 tickets this week, 60% within SLA." He sees the
workload balance and can reassign trips if needed. At the end of the month, every deviation,
fuel flag, and delay has a clear owner — no more "I thought you were handling that."

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

### 5.3 Yard, Dock & Queue Management

Manage truck check-in/check-out, dock door assignment, loading/unloading schedules, and
FIFO/priority queues for all yard services: loading docks, workshop entry, fueling bay,
and car wash. Every service has a queue to avoid chaos and ensure ordered service.

**The business problem:**
- 120+ trucks competing for limited services: fueling bay, workshop, wash bay, loading docks
- No queue = drivers skip ahead, disputes, frustrated staff
- No visibility on wait times: "how many trucks are ahead of me at the wash bay?"
- No way to prioritize: an urgent breakdown should skip the workshop queue

**Queue System (common to all service types):**
- `service_queues` table: `id`, `vehicle_id`, `service_type` (loading_dock, unload_dock, workshop,
  fueling_bay, car_wash, inspection), `priority` (1=critical, 2=urgent, 3=normal, 4=low),
  `status` (waiting, in_progress, completed, skipped, cancelled), `position` (computed or ordered),
  `requested_at`, `started_at`, `completed_at`, `assigned_station` (nullable — which specific dock/bay),
  `notes`
- Queue ordering: FIFO within same priority level; higher priority jumps the line
- Each service type has its own queue view (dispatchers see relevant queues)
- Estimated wait time: computed from average service time × trucks ahead in queue
- Mobile check-in: driver checks in at arrival → auto-added to the appropriate queue
- Station assignment: when a dock/bay becomes free, next truck in queue is auto-assigned

**Requirements:**

*Yard & Docks:*
- `yard_entries` table: `vehicle_id`, `driver_id`, `check_in_at`, `check_out_at`, `purpose` (loading, unloading, parking, workshop), `dock_door_id`, `notes`
- `dock_doors` table: `code`, `warehouse_id` (nullable), `is_occupied`, `current_vehicle_id`, `occupied_since`
- Yard capacity dashboard (doors occupied / free, trucks waiting per queue)
- Waiting time tracking (check-in to dock assignment)

*Service Queues:*
- **Car wash queue**: trucks arrive at wash bay → check in → FIFO queue → wash → complete
- **Workshop queue**: repair requests (Phase 2.2) feed into workshop queue once approved; priority based
  on repair priority (critical over normal)
- **Fueling queue**: trucks arriving at the yard petroleum station are queued; the route-linked
  dispensing (3.2) is triggered when the truck reaches the pump
- **Loading/unloading queues**: trucks arrive at yard → join loading or unloading queue → assigned to
  next available dock door

*Dashboard:*
- Unified queue view: tabs for each service type showing current queue length, estimated wait time,
  and current vehicle in service
- Per-queue detail: list of waiting vehicles with priority badges, elapsed wait time
- "Next up" indicator: which vehicle goes next to which station
- Manual override: authorized user can reorder queue (logged)

*Driver Mobile View:*
- "My Position" screen: shows which queues the driver's truck is in, position, estimated wait
- Push notification: "Your truck is next at the fueling bay" or "Dock 3 is ready for you"

**Dependencies:** Vehicles + Drivers (both exist), Workshop (2.2) for workshop queue, Fuel (3.1) for fueling queue

---

### 5.4 Expense Management

A unified expense module covering every cost incurred on a vehicle, linked to its full operational
context: trip, route, driver, location, odometer. Supports pre-approved fixed expense items as
well as variable/unpredictable expenses. Any authorized user (dispatcher, manager, director, MD)
can create an expense — either from a support ticket during escalation or as a direct entry.

**The business problem:**
- Expenses are scattered across support tickets, WhatsApp messages, phone calls, and paper receipts
- No catalog of common expense items with pre-approved amounts — every tire replacement goes
  through the same approval as a one-off toll fee
- No way to track total cost of ownership per vehicle because small on-road expenses are invisible
- Dispatchers and managers waste time re-approving the same type of expense over and over
- Reporting is impossible: "how much did we spend on tires vs tolls last month?" is a manual
  spreadsheet exercise

**Two expense types:**

| Type | Example | Approval needed |
|------|---------|-----------------|
| **Fixed** (pre-approved catalog item) | Tire replacement 22R — 85,000 RWF, Oil change — 45,000 RWF | None or auto-approve (manager can audit) |
| **Variable** (not in catalog or over limit) | Emergency tow from unknown garage, road permit fee | 2-level approval (Logistics Manager → Director of Operations) |

**Workflow (two entry points):**

*Entry point A — From support ticket during escalation (any level can convert):*
```
Driver submits support ticket describing the issue
  ↓  (e.g. "Blew a tire 30 km after Kabale, replaced at local shop")
Ticket flows through escalation chain (4.3):
  Dispatcher → Logistics Manager → Director of Ops → Managing Director
  ↓
At any level, the current assignee can click "Convert to Expense":
  ├── Selects from pre-approved expense catalog (e.g. "Tire replacement 22R — 85,000 RWF")
  │     → Fixed expense, no approval needed, goes directly to Finance queue
  └── Or types custom: name, description, estimated amount, justification
        → Variable expense, enters approval workflow (approval starts from the
          level ABOVE whoever created it — e.g. if manager converts, director approves)
  ↓
Expense recorded with full context (populated from ticket):
  vehicle, trip, route, driver, location, odometer, timestamp
```

*Entry point B — Direct entry (for known/recurring costs not tied to a ticket):*
```
Authorized user (dispatcher/manager) creates expense directly
  ├── Selects from catalog (fixed, no approval)
  └── Enters custom item (variable, needs approval)
  ↓
Expense recorded with full context (entered manually)
```

*Approval & payment (for variable expenses only):*
```
Variable expense created (draft)
  ↓
Logistics Manager approves/rejects
  ↓
Director of Operations approves/rejects
  ↓
Finance queue — approved expenses pending payment
  ↓
Finance officer records payment + uploads proof of payment
  ↓
Expense marked as paid → if linked to a support ticket, ticket auto-resolves
  ↓
Push notification to driver (if applicable)
```

**Requirements:**

*1. Expense Types Catalog (pre-approved items):*
- `expense_types` table: `name`, `description`, `category` (tires, engine, brakes, electrical,
  body, tolls, permits, accommodation, meals, fuel_external, towing, other), `expense_class`
  (fixed, variable), `default_amount` (for fixed), `currency_id`, `status` (draft, pending_approval,
  active, rejected), `created_by`, `approved_by`, `approved_at`, `is_active`
- Logistics Manager can create a new expense type as "draft" → submits for approval
- Director of Operations approves/rejects new expense type before it becomes active
- Once approved, the expense type is available in the catalog for anyone to use
- A fixed expense type means its default_amount is pre-approved; no further approval needed per instance
- A variable expense type still requires the full approval chain per instance

*2. Unified Expense Records with Full Context:*
- `expenses` table (renamed from `expense_claims`): `reference`, `expense_type_id` (FK, nullable),
  `support_ticket_id` (FK, nullable), `vehicle_id` (FK), `driver_id` (FK, nullable), `trip_id` (FK, nullable),
  `route_id` (FK, nullable), `name`, `description`, `category`, `amount`, `currency_id`,
  `location` (point geometry or text description), `odometer` (nullable), `expense_class` (fixed, variable),
  `status` (pending, approved, paid, rejected, cancelled),
  `rejection_reason`, `paid_at`, `payment_method`, `payment_reference`, `proof_of_payment_file_id`,
  `created_by`, `created_at`, `updated_at`
- Receipt/document upload via Document Management (1.1)
- If created from a support ticket: `support_ticket_id` populated; ticket status changes to `converted_to_expense`

*3. Converting a Ticket to an Expense (from any escalation level):*
- A "Convert to Expense" button on any open support ticket — visible to whoever currently holds
  the ticket (dispatcher, manager, director, or MD)
- Step 1: Search/select from the expense catalog. Shows pre-approved items with their default amounts.
  If a match exists, pick it → expense_class = fixed, amount pre-filled
- Step 2: If not in catalog, toggle to custom entry: type name, description, estimated amount,
  and a justification note → expense_class = variable
- Step 3: Review and confirm. Pre-populated fields from the ticket: vehicle, driver, trip, route.
  User adds: location (from ticket context or manual), odometer
- Fixed expense → created directly at "approved" status, goes to Finance queue
- Variable expense → created at "pending" status, enters approval workflow
- **Approval rule**: the expense is approved by the level ABOVE whoever converted it:
  - If dispatcher converted → Logistics Manager + Director of Operations approve
  - If Logistics Manager converted → Director of Operations + (if amount > threshold) MD approve
  - If Director of Operations converted → Managing Director approves
  - If MD converted → auto-approved (MD is top of chain)

*4. Direct Expense Entry (no ticket):*
- A "New Expense" form available to dispatchers and managers
- Same flow: pick from catalog or enter custom → full context required (vehicle is mandatory)
- Same approval rules based on expense class

*5. Approval Workflow (variable expenses only):*
- Uses the existing `approvals` table (polymorphic, same as Phase 2.2): `approvable_type` = `expense`
- Stage 1: Logistics Manager approves (or rejects with reason)
- Stage 2: Director of Operations approves (or rejects with reason)
- Rejection at either stage sends it back to creator with comments

*6. Finance Queue & Payment:*
- "Pending Payment" list: all approved expenses (both fixed and variable)
- Sortable by: date, amount, vehicle, category
- Finance officer records payment: `paid_at`, `payment_method`, `payment_reference`,
  `proof_of_payment_file` (upload via Document Management)
- System marks expense as `paid`; if linked to a support ticket, ticket auto-resolves

*7. Driver Notification:*
- When an expense linked to a support ticket is marked as paid, push notification via mobile app:
  "Your expense of 85,000 RWF for tire replacement has been paid."
- Notification includes amount paid and link to view proof of payment

*8. Reporting:*
- Expense by category (monthly): tires, engine, tolls, permits, etc.
- Expense by vehicle: total cost of ownership per truck
- Expense by driver: who generates the most costs
- Fixed vs variable expense split: what percentage of costs are predictable vs unpredictable
- Expense by trip: how much each trip costs beyond planned fuel
- Pending payment aging: how long approved expenses wait for finance to pay
- Catalog usage report: which pre-approved items are used most, which are never used

**Dependencies:** Support Tickets (existing), Approvals (2.2), Document Management (1.1),
Trip Ownership (4.1) for dispatcher assignment, Mobile Companion App (existing)

---

### 5.5 Container & Demurrage Tracking

Track every container in the fleet — company-owned (private) or shipping line containers —
from arrival to return. Link containers to vehicles, warehouses, and trips. Calculate demurrage
and detention penalties in real time so dispatchers know where every container is, how late it
is, and how much is owed.

**The business problem:**
- Containers arrive at port or depot and need to be picked up, moved, loaded, and returned
- Shipping lines charge demurrage (container sitting at port beyond free days) and detention
  (container at customer/yard beyond free days) — these add up fast
- Dispatchers don't have a single view of "where is container X, whose is it, is it late?"
- No system tracks: free days remaining, daily penalty rate, or total accrued charges
- Company-owned (private) containers vs shipping line containers need different handling:
  private containers are assets to maximize utilization; shipping line containers are liabilities
  to minimize penalty charges

**Key concepts:**

| Term | Meaning |
|------|---------|
| **Container** | A shipping container (20ft, 40ft, etc.) tracked by its unique ID |
| **Owner** | Company-owned ("private") or a specific shipping line (Maersk, MSC, CMA-CGM, etc.) |
| **Free days** | Number of days allowed before demurrage/detention charges start (per shipping line contract) |
| **Demurrage** | Charge for container staying at port/depot beyond free days |
| **Detention** | Charge for container staying at customer/warehouse beyond free days |
| **Daily rate** | Penalty per day after free period expires (often tiered: days 1-5 = $X, days 6-10 = $Y) |

**Requirements:**

*1. Container Registry:*
- `containers` table: `container_id` (unique identifier, e.g. "MSCU1234567"), `size` (20ft, 40ft, 40hc),
  `type` (dry, reefer, open_top, flat_rack, tank), `owner_type` (private, shipping_line),
  `shipping_line_id` (FK, nullable — if owned by a line), `is_owned` (boolean: true = company asset,
  false = shipping line asset), `purchase_value` (for private containers), `purchase_date`,
  `current_status` (at_port, in_transit, at_warehouse, at_customer, empty_returned, damaged, scrapped),
  `current_location_id` (FK to warehouses or ports, nullable), `last_known_gps`, `is_active`
- `container_movements` table: `container_id`, `from_location_type` (port, warehouse, customer, yard),
  `from_location_id`, `to_location_type`, `to_location_id`, `movement_type` (port_pickup, delivery_to_customer,
  return_to_depot, reposition, transfer), `vehicle_id` (FK — which truck moved it), `driver_id`,
  `trip_id`, `seal_number`, `departed_at`, `arrived_at`, `notes`, `created_at`

*2. Demurrage/Detention Configuration:*
- `shipping_line_contracts` table: `shipping_line_id`, `name`, `free_demurrage_days`, `free_detention_days`,
  `demurrage_tiers` (JSON: e.g. [{"days_from": 1, "days_to": 5, "daily_rate": 50}, {"days_from": 6, "days_to": 10, "daily_rate": 75}]),
  `detention_tiers` (JSON, same structure), `currency_id`, `effective_from`, `effective_to`, `is_active`
- Default tier values can apply if no specific contract is configured for a shipping line
- Private containers: no demurrage/detention charges, but utilization tracking instead

*3. Real-Time Delay & Penalty Calculation:*
- A scheduled job (cron) or on-demand service class calculates:
  - For each container at a port: days since arrival − free_demurrage_days = demurrage days overdue
  - For each container at customer/warehouse: days since arrival − free_detention_days = detention days overdue
  - Apply tiered daily rates to compute total penalty owed
- Penalties accrue daily; system keeps a running total per container
- `container_penalties` table (snapshot or running): `container_id`, `penalty_type` (demurrage, detention),
  `days_overdue`, `daily_rate`, `total_amount`, `calculated_at`

*4. Dispatcher Dashboard — Container View:*
- Overview cards: total containers on-site, containers at port, containers at customer, overdue containers
- Per-container detail card:
  - Container ID, size, type, owner (company logo or shipping line name)
  - Current location (port/warehouse/customer name)
  - Days at current location, free days remaining or overdue count
  - Penalty status: "On track" (green), "Warning — 2 free days left" (yellow), "Overdue — 5 days, $250 owed" (red)
  - Movement history timeline
- Map view: show container positions (last known GPS or location pin)
- Filter by: owner (private vs shipping line), status, location, shipping line

*5. Trip and Vehicle Integration:*
- When a trip is created for container movement (port pickup or delivery), the container is linked to
  the trip via `trip_id` on the container or via `container_movements`
- The vehicle transporting the container is linked (already on the trip)
- Dispatcher sees: which containers are on which truck, where they're going, ETA

*6. Notifications & Alerts:*
- When a container is picked up from port: start free-day countdown clock
- Notification when free days are about to expire: "Container MSCU1234567 has 2 free days remaining at Port of Mombasa"
- Escalation if overdue: notify dispatcher, then operations manager
- For private containers: notification if container sits idle for more than N days (utilization alert)

*7. Reporting:*
- Demurrage/detention cost by shipping line (monthly): which lines cost the most in penalties
- Penalty cost by customer (if customer-related delays)
- Container utilization report (private containers): days in use vs idle
- Container movement log per container: full lifecycle trace
- Total demurrage/detention cost trend (month over month)

**Dependencies:** Trips (existing), Vehicles (existing), Warehouses (5.3 or existing),
Rate Engine (6.1) for potential per_container charges

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

A driver on the road blows a tire. He opens the mobile app, submits a support ticket: "Tire
blew 30 km after Kabale, replaced at local shop — 85,000 RWF", and attaches a photo. The
ticket goes to his trip's assigned dispatcher.

The dispatcher reviews it, decides this qualifies as an expense, and clicks "Convert to Expense".
She searches the catalog and finds "Tire replacement 22R — 85,000 RWF" — it's a pre-approved
fixed item. She selects it, adds the odometer reading and location, and confirms. Since it's
a fixed expense from the catalog, no approval needed — it goes directly to the Finance queue.

A different scenario: a driver pays a road permit fee not in the catalog. The dispatcher
can't find a matching item, so she toggles to custom entry, types "Rwanda Road Permit —
Kabale border, 25,000 RWF", estimated amount, and submits. Since it's a variable expense,
it enters the approval chain. Logistics Manager approves within 10 minutes,
Director of Operations approves 5 minutes later, and it moves to "Pending
Payment" in Finance.

The finance officer processes it end of day, records the payment, and uploads a scanned copy
of the bank transfer receipt. The driver's phone buzzes: "Your expense of 85,000 RWF for
tire replacement has been paid. View proof of payment." No phone calls, no WhatsApp, no
"I haven't been reimbursed from last month."

A dispatcher opens the Container Tracking dashboard. She sees: 12 containers on-site, 8 at
Port of Mombasa, 4 at customer sites. One is flagged red — "MSCU1234567 (Maersk) — Port of
Mombasa — 8 days overdue — $400 owed." She clicks it. The system shows: this 40ft container
was picked up 18 days ago, has 10 free demurrage days, is now 8 days overdue at $50/day.
The shipping line contract says days 1-5 = $50/day, days 6-10 = $75/day — the system
calculated $400 automatically. She checks the movement history: picked up by Truck XYZ-456
on June 1, delivered to Customer ABC on June 3, still sitting there 15 days later with only
5 free detention days. She calls the customer to arrange return before it hits $75/day tier.

A green container on the same screen shows "Private — Container ML-001 — In transit —
delivery to Kigali — ETA tomorrow." She clicks it: this is a company-owned container last
moved on Trip T-892, currently on Truck XYZ-789, estimated arrival 10:00. No penalties,
just utilization tracking.

A driver pulls into the yard after completing a trip. He checks in via the mobile app — the
system adds him to the car wash queue (position 3, est. wait 20 min) and the workshop queue
(his pre-existing repair request is already in the queue at position 5). He sees: "Car wash:
3 ahead of you (~20 min). Workshop: 5 ahead of you (~2 hours)." When his truck reaches the
front of the wash bay, his phone buzzes: "Your truck is next at the wash bay — proceed to
Bay 2." After the wash, the system automatically moves him to the fueling queue where the
attendant is waiting. No shouting, no skipping, no chaos — just ordered service.

A traffic fine comes in for Truck XYZ-456 — speeding camera, 45,000 RWF. A manager reviews it,
determines the driver was responsible, and opens the fine record. He clicks "Deduct from User
Wallet", enters the amount, and submits. The system creates a debit transaction on the driver's
wallet with category=fine, linked to the fine record via polymorphic source. The driver's phone
buzzes: "45,000 RWF deducted from your wallet — traffic violation. Tap to view." The driver
opens "My Wallet" — current balance: -12,000 RWF (negative). He sees the full history: last
week's trip allowance +35,000 RWF (credit, source=trip T-892), a cash advance of -50,000 RWF
(debit, source=cash_advance #CA-003), and this fine -45,000 RWF. The same mechanism works for
any money movement — fuel deduction, expense reimbursement, manual adjustment — all just
transactions with different categories and source references.

---

### 5.6 Performance Rating & Scoring

A continuous rating system for drivers, dispatchers, and store managers. Combines automated
scores (from operational data) and human ratings (from managers and customers) into a unified
performance profile. Scales to 120+ drivers where no one knows everyone personally.

**Why this matters for a 120+ fleet (the Uber insight):**
- With many drivers and staff, reputation replaces personal knowledge
- A driver with a 4.8 rating gets assigned to premium customers; a 3.2 rating triggers retraining
- Dispatchers with fast resolution times and low escalation rates get promoted
- Without a rating system, performance conversations are based on who complained loudest last week

**Two score types:**

| Score type | What it measures | How it's generated | Update frequency |
|------------|-----------------|-------------------|-----------------|
| **Automated (operational data)** | Fuel efficiency, on-time delivery, ticket resolution time, deviation frequency, expense claims volume | Pulled from existing modules (3.3 fuel rating, 4.3 deviations, 5.4 expenses, 5.2 POD) | Daily (cron) |
| **Human rating** | Professionalism, communication, cargo care, customer satisfaction | Submitted by managers, customers, or peers | Per-event |

**Requirements:**

*1. Rating Profiles (one per user role):*
- `ratings` table: `id`, `rateable_type` (driver, dispatcher, store_manager), `rateable_id`,
  `score_type` (automated, human), `score_category`, `score` (decimal, 1.0–5.0),
  `source` (system_generated, manager_review, customer_review, peer_review),
  `period_start`, `period_end`, `notes`, `created_at`
- `rating_submissions` table: `id`, `rating_id` (FK), `rater_id` (FK users, nullable for automated),
  `rating` (1–5), `comment`, `submission_context_type` (trip_id, support_ticket_id, delivery_id),
  `submission_context_id`, `created_at`
- A `performance_scores` table can store the calculated composite: `rateable_type`, `rateable_id`,
  `overall_score`, `automated_score`, `human_score`, `total_submissions`, `period`

*2. Automated Scoring Sources (existing data, no new input needed):*
- **Fuel efficiency score** (from 3.3 — driver_fuel_rating): avg variance %, flagged trips ratio
- **On-time delivery score** (from 5.2 POD): % of deliveries made within window
- **Ticket resolution score** (from support tickets): avg resolution time, escalation rate for the
  driver's trip dispatcher
- **Deviation frequency** (from 4.3): how often the driver's trips triggered deviation tickets
- **Expense regularity** (from 5.4): number and amount of expense claims per trip (abnormal = red flag)
- **Workshop efficiency** (for store managers from 2.2): avg repair time, parts wait time, rework rate

*3. Human Rating Workflow:*
- **Manager rates driver** after a trip: dispatcher or operations manager can open a driver's profile
  and submit a rating (1–5) with optional comment, linked to the trip
- **Manager rates dispatcher** periodically or per quarter: operations manager rates each dispatcher
  on communication speed, problem resolution, workload management
- **Customer rates driver** via customer portal (Phase 8): after delivery, customer sees "Rate your
  delivery" — driver professionalism, cargo condition, timeliness
- **Peer review (optional)**: drivers can rate store managers or fellow drivers

*4. Composite Score Calculation:*
- Overall score = weighted average of automated + human scores
- Default weights (configurable): automated 60%, human 40%
- Within automated: fuel 25%, on-time 25%, ticket resolution 20%, deviation 15%, expenses 15%
- Within human: manager ratings 50%, customer ratings 30%, peer 20%
- Recency weighting: last 3 months weighted more heavily than older data
- Minimum submissions threshold before a score is "valid" (e.g., a driver with only 1 trip doesn't
  get a displayed score until 5+ trips)

*5. Driver Profile View (for dispatchers/managers):*
- Header: name, photo, overall score (color-coded: green ≥ 4.5, yellow ≥ 3.5, red < 3.5)
- Score breakdown: fuel efficiency, on-time delivery, deviation frequency, expenses, manager ratings
- Trend: score over last 6 months (line chart — improving or declining?)
- Recent ratings: last 10 human ratings with comments and context (which trip, who rated)
- Comparison: "vs fleet average" for each metric
- Trip history filtered by driver with performance callouts

*6. Dispatcher Profile View (for operations managers):*
- Overall score + breakdown: ticket resolution time, escalation rate, trips managed, on-time
  performance of their assigned trips
- Workload balance: how many trips currently assigned vs team average

*7. Leaderboards & Reports:*
- Top 10 drivers this month (by overall score)
- Bottom 10 drivers (for coaching/retraining)
- Best dispatcher by resolution time
- Store manager ranking by workshop throughput
- Score distribution histogram (how many drivers at each level)
- Monthly trend report: "Average driver score is up 0.3 points vs last quarter"

*8. Use Cases (how ratings drive decisions):*
- **Trip assignment**: dispatchers see driver score when assigning; premium customers get top-rated
  drivers automatically
- **Retraining trigger**: driver below 3.0 for 2 consecutive months → auto-flag for retraining
- **Promotion/recognition**: top 10% drivers get recognition badge on mobile app
- **Customer-facing**: customer portal shows "Your driver has a 4.8/5 rating" for reassurance
- **Accountability**: dispatcher score reflects how well their assigned trips performed

**Dependencies:** Fuel Rating (3.3), Trip Ownership (4.1), Deviation Tickets (4.3),
Proof of Delivery (5.2), Expense Management (5.4), Customer Portal (8.1), Users/Roles (existing)

---

### 5.7 Wallet & Ledger

A generic financial wallet per user (driver today, others in the future) that tracks all money in
and out — earnings, fines, deductions, reimbursements, advances — with an always-current balance
that can be positive or negative. Every entry has a category, a source reference (polymorphic),
an audit trail, and a currency.

**The business problem:**
- Money movements for a user — fines imputed, allowances earned, advances taken, expenses
  reimbursed — are scattered across different modules with no unified view
- No one knows a user's current balance: do they owe the company or does the company owe them?
- Settlement is a manual spreadsheet exercise prone to disputes
- No audit trail connecting a deduction back to its source

**How it works (example with a driver):**

```
Traffic fine issued on vehicle XYZ-123 → review determines driver at fault
  ↓
Manager creates wallet debit: -45,000 RWF, category=fine, source=fine_check #F-2024-0891
  ↓
Driver sees in mobile app: "45,000 RWF deducted — traffic violation on June 12"
  ↓
End of period settlement: balance = Σ credits − Σ debits
  ├── If positive → pay user
  └── If negative → user owes company (carry forward or deduct)
```

**Requirements:**

*1. Wallet (generic, per user):*
- `wallets` table: `user_id` (FK users), `currency_id`, `current_balance` (decimal, can be negative),
  `last_settled_at`, `created_at`, `updated_at`
- Unique: one wallet per user per currency
- Balance is computed as: sum of all credits − sum of all debits
- Balance can be negative (user owes the company)

*2. Wallet Transactions (immutable history):*
- `wallet_transactions` table: `id`, `wallet_id`, `type` (credit, debit), `amount`,
  `balance_before`, `balance_after`, `currency_id`, `category` (trip_allowance, fine, expense_reimbursement,
  cash_advance, fuel_deduction, salary_payment, manual_adjustment, settlement_payment, other),
  `description`, `source_type` (polymorphic — links to any originating record: fine, trip, expense,
  expense_claim, support_ticket, etc.), `source_id`, `created_by` (FK users — who recorded it),
  `user_visible` (boolean), `user_acknowledged_at` (nullable), `created_at`
- Immutable: transactions are never deleted or edited — only reversed with an offsetting entry
- Indexed by: wallet_id, category, created_at, source_type+source_id

*3. Creating Transactions (generic — source doesn't matter):*
- Any module can create a wallet transaction by writing to `wallet_transactions` with:
  - The `wallet_id` (looked up by user_id + currency_id)
  - A `category` that describes what kind of money movement it is
  - The polymorphic `source_type`/`source_id` linking back to the originating record
- Examples of sources:
  - A `traffic_fine` record → create debit with category=fine, source_type=traffic_fine
  - A `trip` completion → create credit with category=trip_allowance, source_type=trip
  - An `expense` marked as reimbursed → create credit with category=expense_reimbursement, source_type=expense
  - A fuel analysis flag confirmed as driver negligence → create debit with category=fuel_deduction, source_type= any
  - A manual adjustment form → create credit/debit with category=manual_adjustment, source_type=null
- The polymorphic link keeps the wallet generic — no special-casing any source type

*4. Transaction UI (requires source integration per module):*
- Each source module (traffic fines, trips, expenses, etc.) gets a "Wallet" action:
  - On a traffic fine: "Deduct from User Wallet" button → opens form: select user, amount, description
  - On a trip completion: "Credit Trip Allowance" auto-triggered
  - On an expense reimbursement: "Credit to Wallet" auto-triggered
- The action creates a `wallet_transaction` with the correct polymorphic link back to the source

*5. User Mobile App View:*
- "My Wallet" screen (for any user type with mobile access):
  - Current balance (color-coded: green = positive, red = negative)
  - Recent transactions list (last 20, paginated)
  - Each transaction: date, type icon, amount, category label, description
  - Tap to expand: category, source reference (fine #, trip #, etc.), acknowledgment status
- "Acknowledge" button — user confirms they've seen the deduction/credit
- Push notification on new debit: "45,000 RWF has been deducted from your wallet (traffic violation)"
- Dispute button: opens a support ticket linked to the transaction

*6. Manager/Admin View:*
- User profile includes wallet section: balance, transactions, full history
- "Adjust Wallet" button on admin panel: select user, amount (+/-), category, description, source reference (optional)
- Wallet audit log: every entry shows who created it and the source link
- Activity log: all wallet changes for a user, filterable by category and date range

*7. Settlement:*
- Settlement is a reconciliation, not a transaction type:
  - End of period, system shows: user balance = Σ credits − Σ debits
  - If positive → company pays user; settlement payment recorded as debit (balance goes to 0)
  - If negative → user owes company; carry forward or deduct from next positive balance
  - `wallet_settlements` table: `wallet_id`, `period_start`, `period_end`, `balance_at_settlement`,
    `amount_settled`, `method` (cash, bank_transfer, mobile_money, salary_deduction), `settled_at`,
    `settled_by`, `notes`

*8. Reporting:*
- Balance report: all users with current balance (sort by most negative first)
- Transaction log: full audit trail per user
- Settlement history: what was paid/collected per user per period
- Negative balance aging: users with negative balance for more than N days
- Transaction volume by category: which types of money movement happen most

**Dependencies:** Users (existing), Currencies (existing), all source modules (traffic fines, trips,
expenses, etc.) integrate via polymorphic source link

---

### 5.8 Unified Reporting & Analytics

A centralized reporting layer that pulls data from all modules into accurate, real-time,
drill-down reports. Covers revenue, expenses, and operational metrics across every dimension:
truck, route, driver, dispatcher, client, cargo type, and more.

**The business problem:**
- Reports are scattered across modules (workshop costs in Phase 2, fuel in Phase 3, expenses in
  Phase 5, revenue in Phase 7) — no single view
- Managers export data to Excel and manually cross-reference
- No drill-down: "revenue is down" → "by which client?" → "on which route?" requires separate queries
- Real-time insight is limited to a few dashboards

**Requirements:**

*1. Unified Report Engine (service class + scheduled materialized views):*
- A `reporting` service that aggregates data from all modules into materialized views or a
  dedicated `report_snapshots` table, refreshed on a schedule (every 15 min for operational,
  daily for financial)
- Reports are generated server-side with optional CSV/PDF export
- Every report supports: date range filter, dimension filters (truck, driver, route, client,
  dispatcher, cargo type), and drill-down (click a row → see detail)

*2. Report Catalog (each is a route/endpoint + optional dashboard card):*

*Operational Reports:*
| Report | Dimensions | Source |
|--------|-----------|--------|
| Trip completion rate | By driver, truck, route | Trips |
| On-time delivery % | By driver, client, route | POD (5.2) |
| Average turnaround time | By truck, yard | Yard (5.3) |
| Deviation frequency | By driver, truck, route | Deviations (4.2) |
| Delay breakdown | By cause, driver, route | Delays (4.2) |
| Workshop throughput | By mechanic, repair type | Workshop (2.2) |
| Queue wait times | By service type (wash, fuel, dock) | Queues (5.3) |

*Financial Reports:*
| Report | Dimensions | Source |
|--------|-----------|--------|
| Revenue | By client, route, truck, cargo type, month | Invoices (7.1) |
| Total expenses | By category, truck, driver, trip | Expenses (5.4) |
| Expense breakdown | Fixed vs variable, by catalog type | Expenses (5.4) |
| Profit per trip | Revenue − (fuel + expenses + driver cost) | Combined |
| Profit per client | Total revenue − total costs per client | Combined |
| Demurrage/detention cost | By shipping line, container | Containers (5.5) |
| Fuel cost | By truck, route, driver | Fuel (3.1, 3.3) |
| Wallet balance report | All users, negative balances aged | Wallet (5.7) |

*Performance Reports:*
| Report | Dimensions | Source |
|--------|-----------|--------|
| Driver scorecard | All score dimensions + trend | Ratings (5.6) |
| Dispatcher scorecard | Resolution time, escalation rate | Ratings (5.6) |
| Fleet utilization | % of trucks in use vs idle | Trips + Vehicles |
| Container utilization | Private container days in use vs idle | Containers (5.5) |

*3. Interactive Dashboard (real-time):*
- Top-level KPIs: active trips, trucks in workshop, trucks fueling, pending expenses, revenue today
- Each KPI is clickable → opens the detail report
- Charts: revenue trend (line), expense breakdown (pie), top clients (bar), driver scores (histogram)
- Auto-refresh: KPIs refresh every 30 seconds; reports refresh on page load
- Role-based visibility: Super Admin sees everything; a Dispatcher sees only operational; a Finance
  Officer sees only financial; a Driver sees only their own data

*4. Drill-Down Example Flow:*
```
Operations Manager opens dashboard
  → Sees "Revenue this month: $450K (▼12% vs last month)"
  → Clicks revenue KPI
  → Report: Revenue by Client, sorted by biggest decline
  → Sees "Client ABC: $45K this month vs $78K last month (▼42%)"
  → Clicks Client ABC
  → Report: Trips for Client ABC this month
  → Sees only 12 trips vs 22 last month
  → Clicks a specific trip
  → Trip detail: driver, truck, route, expenses, POD status
  → Root cause: dispatcher reassignment caused delay
```

*5. Data Export:*
- Every report has an "Export" button → CSV or PDF
- Scheduled email reports: configure which reports go to which role (e.g., weekly financial report
  to Finance Director every Monday at 8 AM)

**Dependencies:** All preceding phases (data sources), User Roles (1.2) for role-based visibility

---

## Phase 6 — Commercial

*Builds on Phases 1–5. Includes the sales-to-operations handoff workflow.*

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

### 6.3 Order-to-Dispatch Workflow

The complete handoff from customer inquiry to truck on the road: customer submits order request
→ sales person reviews and negotiates → after agreement, sales person creates a truck request
for Logistics → Logistics Manager assigns a truck → system auto-assigns a dispatcher →
dispatcher prepares the trip (fuel, mileage, docs, instructions) → trip begins.

**The business problem:**
- No formal handoff between sales (who closes the deal) and operations (who executes)
- Sales person agrees terms with client but has no way to communicate tonnage, pickup, dropoff,
  and special requirements to Logistics
- Logistics Manager receives orders verbally or by email — no structured data
- Dispatcher has no pre-departure checklist — they react to issues instead of preparing in advance

**Workflow:**

```
Client submits inquiry via customer portal or sales person enters manually
  ↓
Sales person reviews: cargo type, tonnage, pickup/dropoff locations, dates
  ↓
Negotiation: sales person adjusts pricing (using rate engine), presents quote to client
  ↓
Client accepts + payment (if required) → order confirmed
  ↓
Sales person creates "Truck Request" with full context
  ├── Cargo: type, tonnage, special handling (reefer, hazardous, fragile)
  ├── Route: pickup location, dropoff location, expected pickup date, expected delivery date
  ├── Commercial: agreed rate, client reference, payment status
  └── Attachments: client PO, cargo manifest, special instructions
  ↓
Logistics Manager reviews Truck Request queue
  ├── Assigns a specific truck (considering: tonnage capacity, current location, maintenance due)
  └── If truck needs trailer — assigns trailer too
  ↓
System auto-creates trip + auto-assigns dispatcher (round-robin, per Phase 4.1)
  ↓
Dispatcher receives trip in "My Trips" with "Pre-Departure" status
  └── Pre-departure checklist (dispatcher must complete before trip starts):
        ├── Fuel: confirm fuel dispensed (or schedule at yard)
        ├── Mileage: confirm current odometer, expected trip distance
        ├── Documentation: upload trip documents (waybill, cargo manifest, permits, insurance copy)
        ├── Driver instructions: route notes, customer contact, special handling instructions, 
        │   emergency contacts — typed or attached as PDF
        └── Pre-trip inspection: confirm driver completed vehicle check (linked to mobile app)
  ↓
Dispatcher marks "Ready to Depart" → trip status changes to "assigned" → driver notified
  ↓
Departure: driver confirms departure in mobile app → trip status → "on_route"
```

**Requirements:**

*Truck Request (internal handoff document):*
- `truck_requests` table: `reference`, `order_id` (FK), `sales_person_id` (FK users with sales role),
  `cargo_type`, `tonnage`, `pickup_location`, `dropoff_location`, `expected_pickup_date`,
  `expected_delivery_date`, `special_requirements` (text), `agreed_rate`, `client_reference`,
  `payment_status` (pending, paid), `status` (draft, submitted, truck_assigned, in_progress, completed, cancelled),
  `assigned_vehicle_id` (FK, nullable — set by Logistics Manager), `notes`, `created_at`, `updated_at`
- Sales person creates from: a confirmed order (customer portal or manual entry)
- Fields: cargo type + tonnage drive truck selection (a 5-ton truck can't carry 20 tons)

*Logistics Manager Truck Assignment UI:*
- Queue of pending truck requests sorted by expected pickup date
- For each request: cargo details, tonnage, locations, dates, commercial info
- Truck selector: shows available trucks, filtered by:
  - Capacity >= required tonnage
  - Current location near pickup location
  - Not due for maintenance within trip distance
  - Current fuel level
- One-click assign → system creates the trip, links the truck request, auto-assigns dispatcher

*Dispatcher Pre-Departure Checklist:*
- `trip_preparations` table: `trip_id`, `fuel_confirmed` (boolean), `fuel_liters`, `odometer_start`,
  `documents_uploaded` (boolean), `instructions_provided` (boolean), `inspection_confirmed` (boolean),
  `prepared_by` (FK dispatcher), `ready_at` (timestamp), `notes`
- Dispatcher dashboard shows: trips needing preparation, trips ready to depart, trips in progress
- "Not Ready" warning: if any checklist item is incomplete, the dispatcher cannot mark the trip ready
- Driver sees preparation status in mobile app: "Your dispatcher is preparing your trip" →
  "Trip ready — please proceed to your truck"

*Driver Notification (when trip is ready):*
- Push notification: "Trip T-1042 is ready. Truck ABC-123, Kigali → Kampala. Pickup tomorrow 8:00 AM."
- Driver opens mobile app: sees trip details, documents, instructions, route info

**Dependencies:** Orders (existing or Phase 8), Rate Engine (6.1), Trip Ownership (4.1),
Fuel Dispensing (3.2), Document Management (1.1), Mobile Companion App (existing)

---

### Real-World Outcome After Phase 6

A client submits an inquiry through the customer portal: "Need to move 15 tons of cement from
Kigali to Kampala, pickup June 20." The sales person receives it, opens the rate engine, and
calculates: 430 km × $2.50/km + 15% fuel surcharge = $1,236. She sends a quote to the client.
The client asks for a discount at $2.20/km. The sales person adjusts, re-sends. Client accepts.

Once payment is confirmed, the sales person creates a Truck Request: cargo=cement, tonnage=15T,
pickup=Kigali warehouse, dropoff=Kampala depot, pickup date=June 20, special instructions=
"Cover with tarpaulin — cement must stay dry."

The Logistics Manager opens the Truck Request queue. Available trucks filtered by capacity
(≥15T) and proximity to Kigali. He picks Truck XYZ-789, assigns it. The system auto-creates
the trip and assigns it to Dispatcher Alice (round-robin).

Alice opens her "Pre-Departure" tab. She checks: fuel dispensed (120L per route calculation),
odometer confirmed (45,230 km), documents uploaded (waybill, cement handling instructions,
Rwanda customs permit), driver instructions written ("Contact John at Kampala depot
+256 700 XXX XXX — offload bay 3"). She marks the trip ready. Driver Bob gets a notification:
"Trip T-1100 is ready. Truck XYZ-789, Kigali → Kampala, pickup tomorrow 8 AM."

Bob departs. Trip status → on_route. Alice monitors. When a delay ticket auto-creates (Bob
is stuck at the border for 3 hours), Alice escalates to the Logistics Manager. The manager
converts it to an expense — "Border delay fee — 50,000 RWF" — which needs Director approval.
Director approves, Finance pays, Bob gets reimbursed. One story, one chain, end to end.

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
  1.1  Document Management (legal documents, file storage)
  1.2  Role Builder & Permission Manager

Phase 2  ─── Workshop & Maintenance
  2.1  Spare Parts Inventory
  2.2  Repair Request & Approval Workflow (2-level)
  2.3  Procurement for Spare Parts
  2.4  Workshop Dashboard (real-time)

Phase 3  ─── Fuel Management
  3.1  In-House Fuel Station Management
  3.2  Route-Linked Fuel Dispensing (ratio + 50L reserve)
  3.3  Post-Trip Fuel Consumption Analysis

Phase 4  ─── Route Intelligence & Trip Ownership
  4.1  Trip Ownership & Dispatcher Assignment
  4.2  Deviation Detection + Delay Detection
  4.3  Auto-Ticket Creation & Escalation Workflow (Dispatcher → Mgr → Dir Ops → MD)

Phase 5  ─── Operations
  5.1  Preventive Maintenance
  5.2  Proof of Delivery
  5.3  Yard, Dock & Queue Management
  5.4  Expense Management (catalog + fixed/variable + any-level conversion)
  5.5  Container & Demurrage Tracking
  5.6  Performance Rating & Scoring
  5.7  Wallet & Ledger
  5.8  Unified Reporting & Analytics

Phase 6  ─── Commercial
  6.1  Rate / Tariff Engine
  6.2  Contract Management
  6.3  Order-to-Dispatch Workflow (quote → truck request → assignment → prep)

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
