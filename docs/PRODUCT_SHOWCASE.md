# Martin Logistics Platform

An all-in-one fleet and logistics management system for transportation companies of every scale — from single-depot carriers to multi-branch enterprises. The platform combines a powerful web portal with a mobile companion app to unify dispatch, fleet maintenance, fuel management, driver compliance, customer relations, HR/payroll, and financial accounting under a single pane of glass.

---

## Core Capabilities

### Fleet Operations Center

**Real-time vehicle & driver tracking** using Wialon GPS telemetry. Every vehicle's position, speed, ignition status, and fuel level is displayed on an interactive Leaflet map. The Control Tower dashboard provides a live operational status view with low-fuel alerts, active events, and daily distance rankings.

**Intelligent dispatch board** with vehicle-driver pairing, status toggles, maintenance holds, and CSV export. A secure print URL system enables paperless trip manifests.

**Trip lifecycle management** — create trips, track trip history, monitor pre-trip preparations, and analyze fuel consumption per trip. The Logistics Queue provides a consolidated view of all pending dispatches.

**Route intelligence** with real-time deviation detection using geospatial cross-track distance calculation. Automatic ticketing for route violations and excessive fuel usage, with configurable escalation rules.

**Yard management** — dock door assignment and release, check-in/check-out with queue management by service type, wait time estimation, and mobile check-in for drivers arriving on-site.

**Load optimization** — match orders to vehicles based on volume capacity (`volume_m3`) and max payload. Greedy consolidation algorithm suggests optimal load assignments and flags vehicle suitability.

**Advanced scheduling** — FullCalendar-based visual calendar showing trips, maintenance windows, pickups, and configurable time slots. Conflict detection prevents double-booking vehicles or drivers.

### Fleet Maintenance & Workshop

**Complete repair request workflow** — create, submit, approve/reject, assign mechanic, track work progress, log parts used, and release the vehicle. Supports multi-location repair instructions and approval chains.

**Preventive maintenance scheduling** — interval-based scheduling with automatic due-vehicle detection. Generate repair requests directly from the maintenance schedule. Vehicle-level and cost reporting.

**Parts inventory (catalog)** with barcode support and auto SKU generation. Track stock levels across multiple warehouses, record stock movements (in/out/adjust), and set low-stock alerts.

**Part requests** with approval workflow — request a part, route for approval, and auto-generate a purchase order upon approval.

**Purchase orders** — full PO lifecycle: create, send to vendor, confirm receipt, receive stock (updates inventory), cancel. Vendor management with contact details.

**Service queue** — vehicles waiting for service with drag-to-reorder, start/complete/skip actions, and queue statistics.

**Mechanic profiles** — link users as mechanics, assign them to repair tasks, track workload.

**Available vehicle pool** — see which vehicles are ready for dispatch from the workshop.

### Fuel Management

**Dashboard** with at-a-glance fuel metrics, vehicle-route fuel ratios, and consumption trends.

**Tank management** — track fuel tank inventory levels, record fuel deliveries to tanks, and log fuel dispenses to vehicles with automated cost calculations.

**Driver efficiency analytics** — rate drivers on fuel efficiency, compare consumption across trips, analyze pump-to-tank variance to detect discrepancies.

**Wialon integration** for telemetry-based fuel level monitoring and calibration data.

### Compliance & Safety

**Pre-trip clearance checks** — automated verification of vehicle status, open repairs, preventive maintenance due, insurance validity, inspection status, traffic fines, and driver license validity before a trip can commence. Bypass workflow with approval for exceptions.

**Insurance management** — record insurance policies per vehicle with MORH fields, expiry date tracking with dashboard alerts for impending expirations.

**Inspection management** — log vehicle inspections with MORH fields, overdue inspection alerts on the dashboard.

**Traffic fines** — lookup fines by license plate via external API integration. Fine analytics by day, violation type, and vehicle. CSV export. Fine check history.

**Compliance summary** — unified view of fleet compliance status across all regulatory dimensions.

**Driver document expiry tracking** — passport, license, and other document expiry alerts surfaced on the operations dashboard.

### Commercial & Customer Management

**Client management** — full CRM for client profiles with contact management. Rate cards with multi-tier pricing, fuel surcharge calculation, and charge preview.

**Contracts** — client contracts with SLA monitoring, expiry warnings, and automated reference generation.

**Order management** — create and track orders end-to-end. Commercial fields support variable pricing.

**Proof of delivery (POD)** — electronic POD submission with photo capture, signature, PDF generation, and downloadable records.

**Customer portal** — self-service web portal where clients can sign up, place orders, track order status, view delivery history, submit returns, and receive notifications.

**Returns & reverse logistics** — full return request lifecycle: submit, approve/reject, schedule pickup, receive returned goods, complete. Item-level tracking with stats.

### Financial & Accounting

**Invoicing** — generate invoices from orders, mark as sent/paid/overdue/cancelled, download PDF. Invoice items with line-level detail.

**Accounts receivable** — payment tracking with aging reports (30/60/90+ days), client statements with PDF download.

**Expense management** — categorize expenses (fuel, toll, maintenance, etc.), submit for approval, approve/reject workflow, pay expenses. Drill-down reports by vehicle and category.

**Wallet & ledger** — digital wallet with credit/debit transactions, balance tracking, and settlement management. Multi-currency support.

**Currencies & exchange rates** — manage supported currencies and daily exchange rates for multi-currency operations.

**Performance scoring** — automated driver and dispatcher scoring across fuel efficiency, on-time delivery, route compliance, expense management, and safety. Leaderboards and individual profile views.

**Full double-entry accounting (GL)** — chart of accounts, fiscal years, journal entries with posting and reversal. Trial balance, profit & loss, and balance sheet reports. Automatic posting from invoices, payments, and expenses. Multi-branch financial reporting.

### Human Resources & Payroll

**Employee management** — employee records linked to users, department and position assignment, auto-generated employee numbers, document storage, bank details.

**Departments & positions** — organizational hierarchy setup.

**Attendance tracking** — clock-in/clock-out with same-day record management. Attendance history filtered by employee and date range.

**Leave management** — leave types with annual allocation, leave requests with approve/reject/cancel workflow, leave balance tracking with remaining days calculation.

**Payroll** — pay periods (open/close), payslip generation (auto-calculated from employee salary prorated by days), payslip approval with allowances/deductions JSON input, mark as paid.

### Multi-Branch Support

Full multi-branch architecture with `branch_id` on core entities (vehicles, drivers, orders, invoices, warehouses, employees, fuel tanks, users). Branch CRUD with user assignment. Branch-filtered financial reports.

### Developer & Integration

**Webhooks** — outbound webhook subscriptions with HMAC signing. Automatic dispatch on `InvoiceStatusChanged`, `OrderStatusChanged`, `DeliveryConfirmed` events. Delivery log with retry capability. Event catalog browser.

**API keys** — generate and revoke API keys for third-party integrations. API key authentication middleware for the public API (`/api/v1/`).

**RESTful API** — comprehensive API surface covering all platform features.

---

## Mobile Companion App

The mobile companion app (available via API) extends the platform to drivers, mechanics, and yard staff in the field:

| Role | Capabilities |
|------|-------------|
| **Driver** | View current trip, update trip status, submit proof of delivery (photos + signature), check in to yard |
| **Mechanic** | View assigned repair tasks, task details, start work, complete work |
| **All Users** | Create and view support tickets, send ticket messages, receive push notifications via Firebase Cloud Messaging (FCM) |

**Authentication** via WhatsApp OTP (Twilio) or Firebase phone verification — no passwords needed. Sanctum token-based sessions.

**Real-time push notifications** through Firebase Cloud Messaging for trip assignments, status changes, and support ticket updates.

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2, Laravel 12 |
| Database | MySQL / MariaDB |
| Frontend (Portal) | Vue 3 (Composition API, `<script setup>`), Pinia, Vue Router 4 |
| Frontend (Customer) | Vue 3 standalone SPA |
| Mobile API | RESTful JSON API (Sanctum tokens) |
| Styling | Tailwind CSS 4 |
| Maps | Leaflet + GeoMan drawing tools |
| Calendar | FullCalendar 6 |
| Charts | Chart.js 4 |
| Real-time | Pusher + Laravel Echo |
| Push Notifications | Firebase Cloud Messaging |
| Authentication | Fortify (web), Sanctum (API), 2FA (Google Authenticator) |
| RBAC | Spatie laravel-permission |
| GPS Telemetry | Wialon integration |
| PDF | DomPDF |
| WhatsApp OTP | Twilio |

---

## Security & Access Control

- **Two-factor authentication** via Google Authenticator with recovery codes
- **Email verification** via signed URLs
- **Role-based access control** (RBAC) with granular permissions grouped by module
- **Role change audit trail** — every role assignment is logged
- **API key authentication** for external integrations
- **Sanctum token authentication** for the mobile app
- **Signed URLs** for secure print manifests and email verification links

---

## Scalability & Deployment

The platform is built for transportation operations of any size:

- **Single depot / small fleet** — use the core dispatch, tracking, and maintenance features out of the box
- **Multi-branch enterprise** — enable branch management, assign users and assets to branches, run branch-filtered financial reports
- **Large-scale operations** — warehouse-level inventory tracking, full GL accounting, performance scoring across hundreds of drivers

All features are modular — enable only what you need as your operation grows.

---

## Ideal For

- **Trucking & freight companies** — full dispatch-to-delivery lifecycle with real-time tracking
- **Distribution & logistics providers** — warehouse, yard, and route management
- **Last-mile delivery services** — customer portal, POD, returns management
- **Fleet leasing & maintenance providers** — workshop management, parts inventory, vendor procurement
- **Cross-border transporters** — compliance checks, fine management, multi-currency financials
- **Corporate fleets** — driver management, fuel analytics, HR/payroll integration
