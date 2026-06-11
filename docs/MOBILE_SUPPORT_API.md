# Mobile Support Ticket API — Android Client Guide

## Authentication

All mobile support endpoints require a **Sanctum token** sent as a `Bearer` token in the `Authorization` header.

### Obtain a token

```http
POST /api/login
Content-Type: application/json

{
  "identifier": "phone@domain.com",  // matches a Contact record
  "password": "user_password"
}
```

**Response:**

```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Driver",
    "email": "john@example.com",
    ...
  }
}
```

### Use the token

```http
Authorization: Bearer 1|abc123...
```

### Logout

```http
POST /api/logout
Authorization: Bearer 1|abc123...
```

## Base URL

All mobile support routes live under:

```
https://martinlogistics.co.za/api/mobile/support/tickets
```

## Endpoints

### 1. List my tickets

```
GET /api/mobile/support/tickets
Authorization: Bearer <token>
```

**Query parameters** (all optional):

| Param         | Type   | Description                                |
|---------------|--------|--------------------------------------------|
| `category_id` | int    | Filter by support category ID              |
| `status`      | string | Filter: `open`, `in_progress`, `waiting`, `resolved`, `closed` |
| `priority`    | string | Filter: `low`, `normal`, `high`, `urgent` |
| `search`      | string | Search reference, title, or description     |
| `per_page`    | int    | Page size (default 20)                     |

**Response (200):**

```json
{
  "data": [
    {
      "id": 1,
      "reference": "SUP-2026-000001",
      "user_id": 1,
      "support_category_id": 2,
      "assigned_to": null,
      "subject_type": null,
      "subject_id": null,
      "title": "Engine warning light",
      "description": "The check engine light came on during route 42.",
      "priority": "high",
      "status": "open",
      "created_at": "2026-06-10T14:30:00.000000Z",
      "updated_at": "2026-06-10T14:30:00.000000Z",
      "user": {
        "id": 1,
        "name": "John Driver",
        "email": "john@example.com"
      },
      "category": {
        "id": 2,
        "name": "Mechanical Issue"
      },
      "subject": null
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### 2. Create a ticket

```
POST /api/mobile/support/tickets
Authorization: Bearer <token>
Content-Type: application/json
```

**Request body:**

```json
{
  "support_category_id": 2,
  "title": "Engine warning light",
  "description": "The check engine light came on during route 42.",
  "priority": "high",
  "subject_type": null,
  "subject_id": null
}
```

| Field                | Type   | Required | Description                                      |
|----------------------|--------|----------|--------------------------------------------------|
| `support_category_id` | int    | yes      | ID from the categories list (see below)          |
| `title`              | string | yes      | Max 255 chars                                    |
| `description`        | string | yes      | Full description of the issue                    |
| `priority`           | string | yes      | One of: `low`, `normal`, `high`, `urgent`       |
| `subject_type`       | string | no       | Polymorphic relation type (e.g. `App\Models\Trip`) |
| `subject_id`         | int    | no       | Polymorphic relation ID                          |

**Note:** `user_id` is automatically set to the authenticated user — do not send it.

**Response (201):**

```json
{
  "id": 1,
  "reference": "SUP-2026-000001",
  "title": "Engine warning light",
  "priority": "high",
  "status": "open",
  ...
}
```

### 3. Show a single ticket

```
GET /api/mobile/support/tickets/{id}
Authorization: Bearer <token>
```

Returns full ticket with messages and events. Returns **403** if the ticket does not belong to the authenticated user.

**Response (200):**

```json
{
  "id": 1,
  "reference": "SUP-2026-000001",
  "title": "Engine warning light",
  "description": "The check engine light came on during route 42.",
  "priority": "high",
  "status": "open",
  "created_at": "2026-06-10T14:30:00.000000Z",
  "updated_at": "2026-06-10T14:30:00.000000Z",
  "resolved_at": null,
  "closed_at": null,
  "user": {
    "id": 1,
    "name": "John Driver",
    "email": "john@example.com"
  },
  "category": {
    "id": 2,
    "name": "Mechanical Issue"
  },
  "assignee": null,
  "messages": [
    {
      "id": 1,
      "message": "We've dispatched a mechanic to your location.",
      "created_at": "2026-06-10T15:00:00.000000Z",
      "author": {
        "id": 2,
        "name": "Support Agent"
      }
    }
  ],
  "events": [
    {
      "id": 1,
      "type": "created",
      "payload": {"priority": "high", "source": "mobile"},
      "created_at": "2026-06-10T14:30:00.000000Z",
      "actor": {
        "id": 1,
        "name": "John Driver"
      }
    }
  ],
  "subject": null
}
```

### 4. Add a message to a ticket

```
POST /api/mobile/support/tickets/{id}/messages
Authorization: Bearer <token>
Content-Type: application/json
```

**Request body:**

```json
{
  "message": "The mechanic has arrived, thank you!"
}
```

Returns **403** if the ticket does not belong to the authenticated user.

**Response (201):**

```json
{
  "id": 2,
  "support_ticket_id": 1,
  "message": "The mechanic has arrived, thank you!",
  "created_at": "2026-06-10T16:00:00.000000Z",
  "author": {
    "id": 1,
    "name": "John Driver"
  }
}
```

## Support Categories

If your app needs to show a list of available categories for ticket creation, fetch them from:

```
GET /api/portal/support/categories
Authorization: Bearer <token>
```

Actually, that route uses session auth (web guard), not Sanctum. For a mobile client, the categories endpoint is not yet exposed under Sanctum. You can either:

1. Hardcode a static list in the Android app (ask the team for the current categories).
2. Request a new Sanctum-protected categories endpoint.

## Common Error Responses

| Status | Body                                  | Meaning                                |
|--------|---------------------------------------|----------------------------------------|
| 401    | `{"message": "Unauthenticated."}`     | Missing or invalid Bearer token        |
| 403    | `{"message": "Forbidden"}`            | Ticket doesn't belong to this user     |
| 422    | `{"message": "...", "errors": {...}}` | Validation failure                     |
| 404    | `{"message": "No query results..."}`  | Ticket ID not found                    |

## Data Model

```
SupportTicket
├── id (int)
├── reference (string)          # e.g. "SUP-2026-000001"
├── user_id (int)               # Creator — always the authenticated user
├── support_category_id (int)   # FK to support_categories
├── assigned_to (int|null)      # FK to users (staff assignee)
├── title (string)
├── description (string)
├── priority (string)           # low | normal | high | urgent
├── status (string)             # open | in_progress | waiting | resolved | closed
├── created_at (datetime)
├── updated_at (datetime)
├── resolved_at (datetime|null)
├── closed_at (datetime|null)
│
├── user (object)               # Creator info
├── category (object)           # Category info
├── assignee (object|null)      # Staff assignee info
├── messages (array)            # SupportTicketMessage[]
│   ├── id (int)
│   ├── message (string)
│   ├── created_at (datetime)
│   └── author (object)         # {id, name}
│
├── events (array)              # SupportTicketEvent[]
│   ├── id (int)
│   ├── type (string)           # created | status_changed | assigned | message_added
│   ├── payload (object)
│   ├── created_at (datetime)
│   └── actor (object)          # {id, name}
│
└── subject (object|null)       # Polymorphic relation (e.g. related Trip)
```

## Test Flow

1. `POST /api/login` — get a Sanctum token
2. `POST /api/mobile/support/tickets` — create a ticket
3. `GET /api/mobile/support/tickets` — confirm it appears in the list
4. `GET /api/mobile/support/tickets/{id}` — view the ticket details
5. `POST /api/mobile/support/tickets/{id}/messages` — add a message
6. Try accessing another user's ticket ID — expect 403
