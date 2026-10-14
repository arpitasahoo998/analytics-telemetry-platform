# Enterprise Telemetry & Analytics Platform (SDK, Laravel Ingestion Engine & Dashboard)

A robust, production-grade telemetry and analytics pipeline designed to capture, ingest, store, and visualize application events across client web browsers and backend servers.

The system features a **reusable TypeScript Browser Analytics SDK**, a **Laravel Server Tracking Service**, an **Ingestion & Query API (Laravel 10 + MySQL)**, an **interactive Analytics Dashboard (React + TypeScript + Tailwind CSS)**, and a **Multi-Cuisine Restaurant Demo Application** (Indian, Mexican, Italian, and Chinese cuisines with INR ₹ pricing) demonstrating the end-to-end journey from visitor discovery to server-side order fulfillment.

---

## 🏛️ System Architecture

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                   CLIENT LAYER                                         │
│                                                                                        │
│   Browser Analytics SDK (TypeScript / Zero-Dependency)                                 │
│   ┌──────────────────────┐  ┌───────────────────────┐  ┌───────────────────────────┐   │
│   │   Identity Manager   │  │      Event Queue      │  │      Transport Layer      │   │
│   │ (anon_id, sess_id,   │─>│ (In-memory buffer,    │─>│ (Fetch w/ backoff retry & │   │
│   │  user_id stitching)  │  │  batch size & timer)  │  │  sendBeacon on unload)    │   │
│   └──────────────────────┘  └───────────────────────┘  └───────────────────────────┘   │
└───────────────────────────────────────────────┬────────────────────────────────────────┘
                                                │ POST /api/v1/events (Batches)
                                                ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                              LARAVEL 10 BACKEND API                                    │
│                                                                                        │
│   Ingestion & Analytics Services (Laravel 10 + Eloquent ORM + MySQL)                   │
│   ┌───────────────────────────────────────────────┐  ┌─────────────────────────────┐   │
│   │          EventIngestionController             │  │   ServerAnalytics Service   │   │
│   │  - JSON schema & batch/single payload parser  │  │  - ServerAnalytics::track() │   │
│   │  - UUID v4 validation & generation            │  │  - Context enrichment       │   │
│   │  - Idempotent insertOrIgnore() batch writes   │  │  - Server telemetry logs    │   │
│   └──────────────────────┬────────────────────────┘  └──────────────┬──────────────┘   │
│                          │                                          │                  │
│                          ▼                                          ▼                  │
│               ┌─────────────────────────────────────────────────────────────┐          │
│               │             MySQL Database (analytics_db)                   │          │
│               │  - `events` table (Indexed by epoch, name, source, anon_id) │          │
│               │  - `orders` table (E-commerce order fulfillment in INR ₹)  │          │
│               └──────────────────────────────┬──────────────────────────────┘          │
│                                              │                                         │
│                                              ▼ Query Aggregations                      │
│   ┌─────────────────────────────────────────────────────────────────────────┐          │
│   │  AnalyticsController Endpoints                                          │          │
│   │  - GET /api/v1/analytics/overview        - GET /api/v1/analytics/funnel │          │
│   │  - GET /api/v1/analytics/timeseries      - GET /api/v1/analytics/pages  │          │
│   │  - GET /api/v1/analytics/event-breakdown - GET /api/v1/analytics/recent │          │
│   └──────────────────────────────────────────┬──────────────────────────────┘          │
└──────────────────────────────────────────────┼─────────────────────────────────────────┘
                                               │ JSON Responses
                                               ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                VISUALIZATION LAYER                                     │
│                                                                                        │
│   React Analytics Dashboard & Multi-Cuisine Restaurant Demo                            │
│   - Time-Range Filter (Today, 7D, 30D, All Time, Custom)                               │
│   - Executive KPI Stat Cards (Page Views, Unique Visitors, Sessions, Orders, INR Rev)  │
│   - Interactive Time-Series Traffic Chart & Multi-Stage Conversion Funnel              │
│   - Top Pages Ranking & Browser vs Server Event Distribution Breakdown                 │
│   - Real-Time Live Event Inspector with Raw JSON Payload Viewer                        │
│   - Indian, Mexican, Italian, and Chinese Cuisine Ordering with Full Media Styling    │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 📦 Event Structure & Standard Schema

Every event emitted across the browser or server conforms to an immutable, normalized contract:

```json
{
  "event_id": "c89b21f3-4d6f-4bc2-9642-f3f227eb18d5",
  "event_name": "order_created",
  "timestamp": "2026-10-13T08:34:53.000Z",
  "timestamp_epoch": 1791889493000,
  "source": "server",
  "anonymous_id": "anon_live_test_001",
  "session_id": "sess_live_test_001",
  "user_id": "usr_9481",
  "properties": {
    "order_id": "ORD-D3CE8D",
    "customer_name": "Rohan Verma",
    "customer_email": "rohan.verma@example.com",
    "table_or_delivery": "Table 4",
    "total_amount": 740.00,
    "currency": "INR",
    "items_count": 2,
    "items": [
      { "id": "ind-1", "name": "Butter Chicken", "price": 380.00, "quantity": 1 },
      { "id": "ita-1", "name": "Margherita Pizza DOC", "price": 360.00, "quantity": 1 }
    ]
  },
  "context": {
    "sdk_name": "analytics-laravel-sdk",
    "sdk_version": "1.0.0",
    "runtime": "PHP 8.1.12",
    "server_hostname": "app-worker-1",
    "ip_address": "127.0.0.1",
    "user_agent": "Mozilla/5.0 ...",
    "checkout_flow": "web_checkout",
    "fulfillment_type": "dine_in"
  }
}
```

---

## 🆔 Identity Model & Session Stitching

1. **Anonymous Visitor Tracking (`anonymous_id`)**:
   - Generated via RFC4122 UUID v4 upon the visitor's first page load.
   - Stored in browser `localStorage` (with graceful fallback to in-memory memory storage during private browsing, SSR, or disabled storage environments).
   - Persists across multiple sessions and visits until explicitly reset.

2. **Sliding Session Model (`session_id`)**:
   - Generated alongside a timestamp key (`_analytics_sess_ts`).
   - Implements a **30-minute sliding inactivity window**: on every interaction, the SDK verifies whether elapsed inactivity exceeds 30 minutes. If expired, a new `session_id` is automatically minted.
   - Accurately segments unique visitor sessions for funnel and retention analysis.

3. **User Identification (`identify`)**:
   - Calling `analytics.identify(userId, traits)` associates prior anonymous activity with the authenticated user ID.

4. **Client-to-Server Session Stitching**:
   - When placing an order in the restaurant demo, the browser SDK attaches `anonymous_id` and `session_id` to the order submission body and HTTP headers (`X-Anonymous-Id`, `X-Session-Id`).
   - The backend records the financial transaction in the `orders` table and invokes `ServerAnalytics::track()` to log an `order_created` event carrying the user's session identifier.
   - Result: In the dashboard conversion funnel, **the server-verified order is correctly attributed to the visitor's browsing journey.**

---

## 🚀 Delivery Approach & Reliability

- **In-Memory Event Queue**: Events are buffered client-side to minimize connection overhead.
- **Flushing Triggers**:
  1. Buffer count reaches `batchSize` (default: 10).
  2. Timer interval expires (`batchIntervalMs`, default: 2,000 ms).
  3. Explicit manual flush call: `analytics.flush()`.
  4. Tab lifecycle events: `visibilitychange: hidden`, `pagehide`, and `beforeunload`.
- **Zero Data Loss on Tab Close**: During window unloading, the SDK dispatches pending events via `navigator.sendBeacon()`, which browsers guarantee to transmit in the background even if the page terminates immediately.
- **Exponential Backoff**: If network requests encounter transient errors (HTTP 5xx, 429), the SDK retries with exponential backoff (500ms, 1000ms, 2000ms).
- **Idempotency Protection**: Every event contains a client-generated UUID `event_id`. The database enforces `event_id VARCHAR(64) UNIQUE` and inserts with `insertOrIgnore()`, ensuring network retries never duplicate metrics.

---

## 💻 Integration Examples

### 1. Browser SDK Integration (TypeScript / React)

```typescript
import { init } from '@analytics/browser-sdk';

// 1. Initialize the SDK
const analytics = init({
  endpoint: 'http://localhost:8000/api/v1/events',
  appId: 'restaurant-web',
  batchSize: 5,
  batchIntervalMs: 2000,
  debug: false,
});

// 2. Track Pageviews
analytics.page('Indian Specialties Menu', {
  path: '/menu/indian',
  cuisine: 'indian',
});

// 3. Track Custom User Interactions
analytics.track('add_to_cart', {
  item_id: 'ind-1',
  name: 'Old Delhi Butter Chicken',
  price: 380,
  quantity: 1,
  cuisine: 'indian',
});

// 4. Identify Authenticated Users
analytics.identify('usr_42', {
  name: 'Aarav Sharma',
  email: 'aarav@example.com',
  membership: 'gold',
});
```

### 2. Laravel Server Tracking Service (`App\Services\ServerAnalytics`)

```php
use App\Services\ServerAnalytics;

// Record server-side business truth (e.g. inside OrderController@store)
$tracked = ServerAnalytics::track(
    eventName: 'order_created',
    properties: [
        'order_id' => $order->order_id,
        'customer_name' => $order->customer_name,
        'total_amount' => $order->total_amount,
        'currency' => 'INR',
        'items_count' => count($order->items),
        'items' => $order->items,
    ],
    userId: $order->user_id,
    anonymousId: $request->header('X-Anonymous-Id') ?? $order->anonymous_id,
    sessionId: $request->header('X-Session-Id') ?? $order->session_id,
    context: [
        'fulfillment_type' => 'dine_in',
        'table_number' => 'Table 4',
    ]
);
```

---

## 🛠️ Local Setup & Quickstart

### Prerequisites
- **PHP 8.1+** with `pdo_mysql` and `mysqli` extensions enabled.
- **MySQL 5.7+ / MariaDB 10.4+** running locally on port `3306`.
- **Composer 2.x**
- **Bun 1.0+** or **Node.js 18+**

### Step-by-Step Setup

#### 1. Database Configuration
Ensure MySQL is running with database `analytics_db`:
```sql
CREATE DATABASE IF NOT EXISTS analytics_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 2. Backend Setup (Laravel 10)
```bash
cd backend-laravel
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --force
```

#### 3. Frontend & Demo Setup (React + Tailwind CSS)
```bash
cd ../frontend
bun install # or npm install
```

#### 4. Seed Realistic Multi-Cuisine Historical Data
```bash
cd ..
php scripts/seed-events.php
```

#### 5. Start Servers
```bash
# Windows
scripts\start.bat

# Linux / macOS
chmod +x scripts/*.sh
./scripts/start.sh
```

---

## 🧪 Running Automated Tests

The repository includes comprehensive automated test suites covering both the Laravel API backend and the Browser SDK:

```bash
# Run all tests using automated script
# Windows:
scripts\test.bat

# Linux / macOS:
./scripts/test.sh

# Or run test suites individually:

# 1. Laravel PHPUnit Feature Tests (11 tests, 153 assertions)
cd backend-laravel && php artisan test

# 2. Browser SDK TypeScript Tests (9 tests, 24 assertions)
cd sdk/browser && bun test
```

### Test Coverage Summary:
- **EventIngestionTest**: Single event ingest, batch ingestion, UUID idempotency deduplication, input validation errors.
- **AnalyticsQueryTest**: Overview metrics, timeseries interval bucketing, popular page rankings, funnel conversion stages.
- **OrderCreationTest**: Order persistence, server-side `order_created` telemetry triggering, session stitching.
- **Browser SDK Client & Queue Tests**: Sliding session timeout renewal, FIFO queue buffering, eviction on limit, `sendBeacon` fallback on unload.

---

## ⚖️ Key Decisions, Tradeoffs & Limitations

| Architectural Decision | Rationale | Tradeoff / Limitation |
| :--- | :--- | :--- |
| **Laravel 10 Backend** | Expressive Eloquent models, declarative database migrations, built-in validation, clean dependency injection, robust HTTP testing foundation. | Slightly larger runtime memory footprint compared to minimal procedural scripts; mitigated via optimized queries and routing. |
| **Client-Side Queue Batching** | Bundles high-frequency telemetry events into periodic batches (2s / 5 events), drastically decreasing HTTP connection thrashing and preserving device battery. | In the rare event of a sudden OS/browser crash before `sendBeacon` runs, pending un-flushed micro-events could be lost. |
| **`insertOrIgnore()` with UUID `event_id`** | Guarantees at-most-once processing semantics without needing external distributed cache infrastructure (Redis). | Index checks on MySQL unique constraints incur small write latency under massive write bursts. |
| **Sliding 30-Minute Session Window** | Matches industry standard analytics protocols (Google Analytics, Mixpanel) to measure true continuous visitor engagement. | Inactive tabs kept open overnight will generate a new session ID upon the user's next click. |
| **Dual-Track (Client + Server) Architecture** | Ad-blockers or privacy plugins frequently block browser telemetry. Emitting `order_created` server-side guarantees financial and transaction truth is 100% recorded. | Requires client code to propagate `anonymous_id` and `session_id` through API headers/payload to stitch client interactions to server transactions. |
