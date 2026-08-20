# Auto-Routing for Portal Routes

## Problem
Creating routes on `portal/routes` requires manually drawing a polyline point-by-point using Geoman's drawing tools. This is tedious and inaccurate — the user must trace roads by hand.

## Solution
Replace the manual drawing flow with an OSRM-powered auto-routing system. The user clicks to place start/end markers, the route follows roads automatically, and draggable handles allow route correction — like Google Maps.

**No schema changes needed.** The existing `path: [{lat, lng}, ...]` format stays the same; the controller's `store()`/`update()` methods receive identical payloads.

---

## Backend Changes

### 1. `config/services.php` — Add OSRM config
```php
'osrm' => [
    'url' => env('OSRM_URL', 'https://router.project-osrm.org'),
],
```

### 2. `.env` / `.env.example` — Add `OSRM_URL`
Default to the public demo server for development.

### 3. `app/Http/Controllers/Api/RoutesController.php` — New `routeFromOsrm()` method
- **Input**: `from` (required, `"lat,lng"`), `to` (required, `"lat,lng"`), `waypoints` (optional, array of `"lat,lng"`)
- **Logic**: Builds OSRM coordinate string (`lon,lat;lon,lat;...`), calls `GET {osrm_url}/route/v1/driving/{coords}?overview=full&geometries=geojson&alternatives=true`
- **Output**: `{ routes: [{ distance_km, duration_min, path: [{lat, lng}, ...] }] }` — OSRM returns GeoJSON `[[lon, lat], ...]`, converted to the app's `[{lat, lng}]` format
- **Error handling**: Returns 502 with message if OSRM is unreachable; validates coordinates before calling

### 4. `routes/api.php` — Add route
```
GET /portal/routes/route → RoutesController::routeFromOsrm
```
Placed before the `{route}` wildcard route to avoid parameter capture.

---

## Frontend Changes

### 5. `resources/js/portal/api/routes.js` — Add API method
```js
getRouteFromOsrm(params) {
    return api.get('/portal/routes/route', { params });
}
```

### 6. `resources/js/portal/pages/Routes/Index.vue` — Major rework of map interaction

**Remove:**
- Geoman polyline drawing tool (`drawPolyline: true` → removed)
- Geoman `pm:create` / `pm:remove` event listeners

**Add — Click-to-place markers:**
- First click on map → place **start marker** (green, labeled "A")
- Second click → place **end marker** (red, labeled "B")
- Both markers are draggable — dragging either re-routes immediately
- Clicking the map after both are placed does nothing (must use "Add Waypoint" button for intermediates)

**Add — Auto-routing on marker change:**
- After start+end are placed (or either is dragged), call `getRouteFromOsrm({ from, to, waypoints })`
- Render the returned polyline on the map
- Fit map bounds to the route

**Add — Intermediate waypoints:**
- "Add Waypoint" button in the form sidebar
- When active, next map click places an intermediate marker (blue, labeled "1", "2", etc.)
- Intermediate markers are draggable
- Route re-fetches through all waypoints in order
- Waypoints can be removed by clicking an X on their popup

**Add — Draggable route handles:**
- After the polyline renders, place ~6 invisible draggable circle markers at evenly-spaced points along the route
- These are the "drag to correct" handles (like Google Maps)
- On drag → the handle becomes a new intermediate waypoint → re-route through it
- Handles reposition along the new polyline after each re-route
- Visually subtle: small, semi-transparent blue circles that highlight on hover

**Add — Alternative routes:**
- If OSRM returns >1 route, render alternatives as faded dashed polylines
- User clicks an alternative to select it → it becomes the active route
- Selected route is solid blue; unselected are gray dashed

**Update — Stats panel:**
- Replace Haversine `totalDistance` with OSRM's `distance_km` (road distance)
- Add `duration_min` display from OSRM response

**Keep unchanged:**
- Form fields (name, allowed_deviation_meters)
- Save logic (`saveRoute()` sends `path: pathData.value` — same format)
- List view, edit flow, delete flow
- `loadPathOnMap()` for viewing existing routes (still renders polyline from stored path)

---

## UX Flow

```
1. User clicks "Create New Route"
2. Sidebar shows form (name, deviation) + instruction: "Click the map to place your start point"
3. User clicks map → green "A" marker appears
4. Instruction updates: "Now click to place your end point"
5. User clicks map → red "B" marker appears → OSRM fetches route → blue polyline appears
6. Stats panel shows road distance + duration
7. User sees ~6 small blue handles along the polyline
8. User drags a handle → handle becomes waypoint → route re-routes through it
9. User can click "Add Waypoint" → click map → blue marker placed → re-route
10. If alternatives exist, faded dashed lines appear → user clicks to select
11. User fills in name, clicks "Save & Publish Route"
```

---

## What stays the same
- Route model, migration, all columns
- Controller `store()`/`update()` — receives identical `path` format
- `path_geometry` WKT conversion and `estimated_distance_km` computation
- `RouteIntelligenceService` deviation detection
- All existing routes remain viewable/editable

---

## Files to modify
| File | Change |
|------|--------|
| `config/services.php` | Add `osrm` config block |
| `.env.example` | Add `OSRM_URL` |
| `app/Http/Controllers/Api/RoutesController.php` | Add `routeFromOsrm()` method |
| `routes/api.php` | Add `GET /portal/routes/route` |
| `resources/js/portal/api/routes.js` | Add `getRouteFromOsrm()` |
| `resources/js/portal/pages/Routes/Index.vue` | Rework map interaction (remove Geoman draw, add auto-routing + draggable handles) |
