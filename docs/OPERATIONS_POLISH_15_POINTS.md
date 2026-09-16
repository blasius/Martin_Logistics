# Operations Polish — 15-Point Implementation Plan

Status: planning draft
Scope: integration plan against existing features. This is **not** a greenfield build —
each point maps to models/services/commands/APIs that already exist in the codebase and
specifies the delta required to close the gap.

Related docs: `docs/instructions.md`, `docs/auto-routing-plan.md`,
`docs/PRODUCT_SHOWCASE.md`, `docs/MOBILE_SUPPORT_API.md`, `docs/EXECUTIVE_PRESENTATION.md`,
`docs/ERP_IMPLEMENTATION_PLAN.md`, `docs/BACKEND_TRIP_TELEMETRY_DEBUG.md`,
`docs/BACKEND_PROFILE_IMPLEMENTATION.md`, `docs/ANDROID_TRIP_TELEMETRY_HANDLING.md`,
`docs/ANDROID_REPAIR_REQUESTS.md`, `docs/ANDROID_DRIVER_HOME.md`.

Reference code (re-verified this session):

| Area | Precise location |
|---|---|
| Telemetry | `app/Console/Commands/SyncWialonTelemetry.php` (`telemetry:sync`, ~60s schedule) |
| Deviation | `app/Services/RouteIntelligenceService.php` (`checkDeviation` line ~14, `createDeviationTicket` line ~98) |
| Dispatcher assignment | `app/Services/TruckRequestService.php` `assignDispatcher()` + `assignAndCreateTrip()` (lines 19–70) |
| Trip model | `app/Models/Trip.php` — `dispatcher_id`, `route_id`, `auto_ticket_id`, `is_deviated`, `deviation_*` fields |
| Direct trip creation | `app/Http/Controllers/Api/TripController.php` (`store` — note: does **not** set `dispatcher_id`) |
| Fuel | `app/Models/{FuelDelivery,FuelDispense,FuelTank,TripFuelAnalysis,VehicleRouteFuelRatio,DriverFuelRating,FuelCalibration}.php` |
| POD | `app/Models/ProofOfDelivery.php` + `app/Services/ProofOfDeliveryService.php` + `app/Http/Controllers/Api/Mobile/MobilePODController.php` |
| Support | `app/Models/{SupportTicket,SupportTicketMessage,SupportTicketEvent,TicketEscalation,EscalationRule,SupportCategory}.php`, `ProcessTicketEscalations` command |
| Yard / Workshop | `app/Models/{YardEntry,DockDoor,RepairRequest,RepairAssignment,RepairRelease,MechanicProfile,ServiceQueue}.php`, `app/Services/YardService.php`, `YardManagementController`, `MobileYardController`, `MobileWorkshopController` |
| Rating | `app/Models/{RatingSubmission,PerformanceScore}.php`, `app/Services/RatingService.php` |
| Regulatory docs | `app/Models/{VehicleInsurance,VehicleInspection,Document,DocumentTemplate}.php`, `CheckExpiringDocuments` command |
| Dispatch portal | `app/Http/Controllers/Api/Workshop/DispatchController.php`, `resources/js/portal/pages/Dispatch/Index.vue` |

Known gaps in the source of truth (must be resolved as part of these points):

1. **Dispatchers may be unresolvable** — `assignDispatcher()` returns `null` if the role is missing; `Trip.dispatcher_id` then stays null and round-robin state goes unset.
2. **Trip status vocabulary is inconsistent** — the truck-request flow uses `pre_departure`, the portal UI / dashboard counts use `pending`, and `TripController::store` accepts `pending|assigned|on_route`. There is no single state machine.
3. **`FuelCalibration::convertToLiters()` is an unimplemented stub** — raw Wialon fuel readings are never converted to liters, blocking tank-fill reconciliation.

---

## 1. Digital State Machine (trip lifecycle automation)

**Current state**
- `TruckRequestService::assignAndCreateTrip()` creates trips in `pre_departure` and a `TripPreparation` row.
- `TripController::store()` validates `status in:pending,assigned,on_route` directly.
- Dashboard counts are keyed on `pending/assigned/on_route/delivered/cancelled` (`TripController::index`).
- `TripHistory` already records actions; POD submit sets trip `delivered` and fires `DeliveryConfirmed`.

**Gap**
- Conflicting status vocabulary; transitions are implicit (not enforced); no event-driven chaining from one state to the next.

**Plan**
1. Pick one vocabulary: `pre_departure → pending → assigned → on_route → delivered` plus `cancelled` (keep `pre_departure` as the DB/computed default, map to `pending` for UI counting via accessor or mapping layer).
2. Implement a transition table (single source of truth, e.g. `app/Support/TripStates.php` or a `TripStateMachine` class) with allowed transitions and side effects per transition.
3. Route every status write through the state machine and record `TripHistory` entries; keep dashboard counts consistent by counting the mapped vocabulary.
4. Wire POD validation ✅ → `delivered` automatically through the machine (already partially done in `ProofOfDeliveryService`).

**Files touched**
- `app/Models/Trip.php`, new `app/Services/TripStateMachine.php`, `app/Http/Controllers/Api/TripController.php`, `app/Services/TruckRequestService.php`, `app/Services/ProofOfDeliveryService.php`, dashboard counts endpoint.

---

## 2. Fuel & Pre-Departure Reports

**Current state**
- `FuelDispense` (odometer, `calculated_amount`, `override_reason`), `TripFuelAnalysis` (variance vs expected), `VehicleRouteFuelRatio` (`km_per_liter`), `DriverFuelRating` exist.
- Telemetry sync runs every 60s and stores `TelemetryPoint`/`VehicleSnapshot` fuel data.

**Gap**
- No pre-departure fuel check surfaced on the dispatch screen; per-trip fuel reporting is not exposed in the reports module; raw-to-liters conversion (`FuelCalibration::convertToLiters()`) is a stub.

**Plan**
1. Add a pre-departure fuel gate to the dispatch screen: expected consumption (ratio × planned distance) vs current tank level from the latest telemetry; warn/block if insufficient.
2. Build a per-trip fuel report (dispenese entries + expected from ratio + variance) reusable by the reports module.
3. Implement `FuelCalibration::convertToLiters()` (or replace with a documented ratio-based fallback) so tank-fill numbers are comparable to liters dispensed.

**Files touched**
- `app/Models/FuelCalibration.php`, `app/Models/TripFuelAnalysis.php`, `app/Models/VehicleRouteFuelRatio.php`, `app/Http/Controllers/Api/Workshop/DispatchController.php`, `resources/js/portal/pages/Dispatch/Index.vue`, reports module.

---

## 3. Delivery Documents (Dispatchers validate PODs)

**Current state**
- `ProofOfDelivery` status ENUM `draft|submitted|confirmed`; `MobilePODController::submit()` requires `$user->driver` and 403s unless `trip->driver_id === user->driver->id`.
- `ProofOfDeliveryService` sets trip delivered, appends `TripHistory pod_submitted`, fires `DeliveryConfirmed`, generates PDF (DomPDF, `pdf.delivery-receipt` view).

**Gap**
- No dispatcher-facing review/validate/reject flow; no follow-up action when a POD looks wrong.

**Plan**
1. Dispatcher POD queue: list submitted PODs, view photo/signature/data, actions `validate` (→ `confirmed`, already supported) and `reject` (→ back to `draft`, driver re-submits).
2. On validation, advance the trip through the state machine (point 1) and record history.
3. Expose the delivery PDF for download/reprint from the portal.

**Files touched**
- `app/Http/Controllers/Api/Workshop/DispatchController.php` (or new `PODReviewController`), `resources/js/portal/pages/Dispatch/Index.vue`, `app/Services/ProofOfDeliveryService.php`.

---

## 4. Yard Parking & Dock Doors

**Current state**
- `YardEntry`, `DockDoor`, `YardService`, `YardManagementController`, `MobileYardController` exist.

**Gap**
- No automated parking / dock-door assignment tied to trip arrival; driver-app parking flow not defined in this scenario.

**Plan**
1. On trip arrival/delivery (POD validated), auto-create a `YardEntry` and allocate a free `DockDoor` (via `YardService`).
2. Driver app "park at dock" flow: show assigned door, record entry/exit.
3. Dispatcher yard map: door occupancy, expected arrivals.

**Files touched**
- `app/Services/YardService.php`, `YardManagementController`, `MobileYardController`, trip arrival listener, new yard UI in portal, mobile driver-app screens.

---

## 5. Fuel Purchase Lifecycle (bulk purchase → dispense → trip consumption)

**Current state**
- `FuelDelivery` **is** the bulk-purchase record (tank, supplier `Vendor`, quantity, `unit_price`, `total_amount`, `invoice_reference`, received-by).
- `FuelDispense` tracks per-vehicle liters; `TripFuelAnalysis` computes expected-vs-actual variance.

**Gap**
- No full-lifecycle view linking purchase → tank inventory → dispense → per-trip consumption; no Wialon fill-vs-dispense theft reconciliation; no escalation on anomalies; `FuelCalibration::convertToLiters()` stub blocks raw-fill comparison.

**Plan**
1. Fuel overview page: purchases, tank levels (from telemetry), dispensed totals, expected vs actual per trip.
2. Reconciliation job comparing Wialon fills vs recorded dispenses per vehicle/tank; `convertToLiters()` real implementation required for accuracy.
3. On material variance (or override-heavy periods), auto-open a `SupportTicket` via the support stack (point 7) and notify owners.

**Files touched**
- `app/Models/{FuelDelivery,FuelDispense,FuelTank,FuelCalibration,TripFuelAnalysis}.php`, new reconciliation command (schedule in `routes/console.php`), fuel overview page in portal, `app/Models/SupportTicket.php` bridge.

---

## 6. Dispatcher Equal Distribution & Ownership

**Current state**
- `TruckRequestService::assignDispatcher()` does round-robin (last-assigned by `created_at`) at trip creation inside `assignAndCreateTrip()`.
- `Trip.dispatcher_id` exists; `DispatchPreparationController` already scopes to `auth()->id()`.
- `RatingService` derives dispatcher metrics (`tripsManaged`, `onTimeTrips`) by `dispatcher_id`.

**Gap**
- Round-robin only applies to truck-request trips; direct trips via `TripController::store` never set `dispatcher_id`; no per-truck single-dispatcher ownership; no manager view of who manages what.

**Plan**
1. Reuse `assignDispatcher()` in `TripController::store` so all trip creation paths get a balanced dispatcher.
2. Add per-vehicle single-dispatcher ownership (last dispatcher per vehicle, or an explicit ownership table) so one driver/vehicle isn't split across dispatchers.
3. Manager page: load per dispatcher, active trip counts, on-time performance from `RatingService`.

**Files touched**
- `app/Services/TruckRequestService.php`, `app/Http/Controllers/Api/TripController.php`, possibly new `VehicleDispatcherAssignment` model, new manager portal page.

---

## 7. Support Escalation Automation

**Current state**
- `SupportTicket`, `SupportTicketMessage`, `SupportTicketEvent`, `TicketEscalation`, `EscalationRule`, `SupportCategory` models and the `ProcessTicketEscalations` command exist.

**Gap**
- Rule completeness not validated; no dispatcher-scoped notification; no auto-ticket entry points from anomaly detection (fuel, deviation, fines).

**Plan**
1. Ensure `ProcessTicketEscalations` is scheduled (`routes/console.php`) and rules defined per category/priority/SLA.
2. Dispatcher-scoped notifications: tickets touching dispatcher-owned vehicles go to that dispatcher's assigned owner.
3. Provide a shared "open auto-ticket" service so fuel reconciliation (point 5), deviation auto-ticketing (point 12) and fines (point 11) all route through the same workflow.

**Files touched**
- `routes/console.php`, `ProcessTicketEscalations`, new `app/Services/SupportAutoTicketService.php`, notification wiring.

---

## 8. Costs vs Revenue Reporting

**Current state**
- Orders carry `price` + tonnage; fuel costs available from `FuelDelivery.total_amount` / `FuelDispense.calculated_amount`; repair costs implied in workshop models; `docs/ERP_IMPLEMENTATION_PLAN.md` exists.

**Gap**
- No per-trip/period P&L view reconciling revenue vs fuel + maintenance + other costs.

**Plan**
1. Define a cost aggregation service: per trip → fuel liters/amount (dispenses), repairs (`RepairRequest` totals), allowances; per period → fleet totals.
2. Reports: cost per trip, cost per km, margin by client/route; dashboard KPI.
3. Tie into the ERP plan document rather than duplicating a finance stack.

**Files touched**
- New `app/Services/TripCostingService.php` (aggregating `FuelDispense`, `RepairRequest`, `Order`), reports module, dashboard KPI endpoint.

---

## 9. Driver Rest & Stop Monitoring

**Current state**
- Telemetry sync runs every 60s (likely the only live signal source).
- No dwelling/rest detection exists.

**Gap**
- No automated detection of unplanned stops, rest durations, or schedule breaches during trips.

**Plan**
1. In the telemetry pass, mark stationary periods per in-flight trip (position deltas / ignition-off with constant location).
2. Classify stop quality: expected (delivery, yard) vs unexpected; alert the trip's dispatcher when rest exceeds thresholds or occurs off-corridor.
3. Log to `TripHistory`/`RouteDeviationLog`-style records for later reports.

**Files touched**
- `app/Console/Commands/SyncWialonTelemetry.php`, new `app/Services/StopDetectionService.php`, `app/Models/Trip.php` (rest fields), dispatcher notification.

---

## 10. Access Review & Permission Hygiene

**Current state**
- Spatie roles/permissions; user base with Dispatcher/Driver/Mechanic/Manager roles.

**Gap**
- No scheduled review of stale accounts, inactive dispatchers, or over-privileged roles.

**Plan**
1. Scheduled review job: inactive accounts, dispatchers no longer active, drivers/mechanics without vehicle/assignment integrity.
2. Report to managers + optional auto-deactivation policy (config-gated).
3. Permission audit listing users × roles × perimeters (portal/mobile/customer).

**Files touched**
- New `app/Console/Commands/ReviewAccess.php` (+ schedule in `routes/console.php`), admin review UI, `ReportController`/reports module.

---

## 11. Trip Bypass & Fines Automation

**Current state**
- `ClearanceBypassRequest` approval already gates `TripController::store` (approved bypass check-name skips block).
- `DispatchFinesChecks` (weekly) and `CheckPlateJob` exist but are **unscheduled**.
- `resources/js/portal/pages/Clearance/BypassRequests.vue` modernized (approve/reject modals, toast).

**Gap**
- Bypass approval only unblocks a **future** `store()`; it does not transition an already-created trip/vehicle to dispatchable. Fines/plate jobs never run.

**Plan**
1. On bypass approval: record `TripHistory` and transition the current trip/vehicle to dispatchable if one is awaiting clearance (`PENDING` → ready).
2. Schedule `DispatchFinesChecks` weekly and `CheckPlateJob` daily (`routes/console.php`).
3. Any fine/plate failure should open a support ticket through the point 7 auto-ticket service.

**Files touched**
- `TripController::store` (already uses bypasses), `BypassRequests` controller, `routes/console.php`, `DispatchFinesChecks`, `CheckPlateJob`, `SupportAutoTicketService`.

---

## 12. Route Deviation Auto-Ticketing

**Current state**
- `RouteIntelligenceService::checkDeviation(Trip)` (line ~14) compares travelled distance/geometry vs `route.allowed_deviation_meters` (default 500), creates `RouteDeviationLog`, sets `is_deviated`, captures `deviation_detected_at`/`duration`/`max_distance`.
- `RouteIntelligenceService::createDeviationTicket(RouteDeviationLog)` (line ~98) sets `Trip.auto_ticket_id` — exists but has **no caller/scheduler**.
- `RouteIntelligenceController` exposes manual `checkDeviation` endpoints; telemetry sync runs every 60s.

**Gap**
- Auto-ticket function is dormant. No scheduled association with the live telemetry pass; no dispatcher dispatch-scoped handling of auto-tickets.

**Plan**
1. During each `telemetry:sync` pass, run `checkDeviation` for in-flight trips and call `createDeviationTicket` when a deviation exceeds tolerance → populate `auto_ticket_id`.
2. Auto-ticket routes through the point 7 workflow (category "route deviation"), assigned to the trip's dispatcher.
3. Add scheduling guard/cadence to avoid ticket spam (cooldown, escalation on escalation).

**Files touched**
- `app/Console/Commands/SyncWialonTelemetry.php`, `app/Services/RouteIntelligenceService.php` (invoke in sync, no new logic), `routes/console.php`, `SupportAutoTicketService`.

---

## 13. Driver / Dispatcher Rating

**Current state**
- `RatingSubmission`, `PerformanceScore`, `RatingService` compute dispatcher metrics (`tripsManaged`, `onTimeTrips`) by `dispatcher_id` and per-driver metrics exist.

**Gap**
- No in-app submission surface for drivers rating dispatchers and vice versa.

**Plan**
1. Mobile API: driver rates dispatcher (post-trip, one per trip); dispatcher rates driver (post-delivery).
2. Persist via `RatingSubmission`, recompute `PerformanceScore`, expose leaderboards/metrics in portal.
3. Protect against re-scoring: unique per trip per subject.

**Files touched**
- New `app/Http/Controllers/Api/Mobile/MobileRatingController.php`, `routes/api.php` mobile group, `RatingService`, portal performance page.

---

## 14. Regulatory Documents on the Driver App

**Current state**
- `VehicleInsurance`, `VehicleInspection`, `Document`, `DocumentTemplate` models exist; `CheckExpiringDocuments` command exists.

**Gap**
- No driver-app visibility of the vehicle's own regulatory docs; no expiry alert to drivers.

**Plan**
1. Mobile endpoint returning the assigned vehicle's docs (insurance, inspection, plate, license) by maturity window.
2. Driver-app screen "My vehicle docs" + expiry alerts.
3. Expiring docs push a support ticket / notification to operations (reuse point 7 auto-ticket or `CheckExpiringDocuments`).

**Files touched**
- New `app/Http/Controllers/Api/Mobile/MobileVehicleDocsController.php`, `routes/api.php`, `CheckExpiringDocuments`, mobile screens.

---

## 15. Mechanic / Driver Rating After Repairs

**Current state**
- Workshop stack: `RepairRequest` → `RepairAssignment` → `RepairRelease` with `MechanicProfile`, `ServiceQueue`; `MobileWorkshopController` + `MobileRepairRequestController` exist.
- `RatingSubmission`/`PerformanceScore` infrastructure exists.

**Gap**
- No rating of the mechanic after repair release or of the driver experience with the workshop.

**Plan**
1. After `RepairRelease`, driver rates mechanic (timeliness, quality); dispatcher/manager can rate workshop response.
2. Extend `PerformanceScore`/`RatingService` to mechanics; leaderboard in portal.
3. Guard uniqueness per repair request.

**Files touched**
- `RepairRelease` flow, new rating endpoint (reuse `MobileRatingController`), `RatingService`/`PerformanceScore` extension, portal workshop page.

---

## Cross-cutting implementation notes

- **Scheduling**: today several commands exist but are not scheduled (`DispatchFinesChecks`, `CheckPlateJob`, `ProcessTicketEscalations`). Consolidate scheduling in `routes/console.php` with clear cadence comments.
- **Single connectivity**: anomaly detection (fuel theft, deviation, fines, stops) should funnel through one auto-ticket service (point 7) so Support is the single inbox.
- **Reporting**: keep the reports module as the single UI; per-point dashboards add widgets, not new stacks.
- **Mobile**: all driver-app features go through `routes/api.php` mobile groups and existing `Mobile*Controller` conventions.

## Verification checklist per point (definition of done)

- [ ] All new state/status writes go through the trip state machine.
- [ ] Scheduling entries exist in `routes/console.php` (no orphaned commands).
- [ ] Auto-tickets are created through `SupportAutoTicketService`.
- [ ] Mobile endpoints follow existing mobile controller conventions and auth scopes.
- [ ] Every portal change uses the established Dispatch-style UI pattern (full-screen, dark column headers, card rows, modal/toast conventions from the recent modernizations).
- [ ] Build green: `node_modules\.bin\vite build` passes.