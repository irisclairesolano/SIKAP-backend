# SIKAP Backend — Location Tables & Dropdown API
> Instructions for the backend SWE agent.
> Add these tables to Supabase and expose the dropdown endpoints.

---

## WHAT YOU ARE ADDING

Two new tables in Supabase:
- `municipalities` — 15 municipalities of Sorsogon Province
- `barangays` — 541 barangays linked to their municipality

Two new API endpoints:
- `GET /api/v1/locations/municipalities` — returns all municipalities
- `GET /api/v1/locations/barangays?municipality=Sorsogon City` — returns barangays filtered by municipality

These power the registration and profile dropdowns on the frontend. No auth required — these are public endpoints.

---

## STEP 1 — Run the SQL in Supabase

1. Go to your Supabase project dashboard
2. Click **SQL Editor** in the left sidebar
3. Paste the contents of `location_seed.sql` (provided separately)
4. Click **Run**
5. Verify the output shows 15 municipalities and correct barangay counts per municipality

### Expected verification output
```
municipality     | barangay_count
-----------------|---------------
Barcelona        | 25
Bulan            | 63
Bulusan          | 24
Casiguran        | 25
Castilla         | 34
Donsol           | 51
Gubat            | 42
Irosin           | 28
Juban            | 25
Magallanes       | 34
Matnog           | 40
Pilar            | 49
Prieto Diaz      | 23
Sorsogon City    | 64
Sta. Magdalena   | 14
```
Total: 541 barangays

---

## STEP 2 — Create the Eloquent Models

### `app/Models/Municipality.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'municipalities';
    public $timestamps = true;

    protected $fillable = ['name'];

    public function barangays()
    {
        return $this->hasMany(Barangay::class);
    }
}
```

### `app/Models/Barangay.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'barangays';
    public $timestamps = true;

    protected $fillable = ['name', 'municipality_id'];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }
}
```

---

## STEP 3 — Create the Controller

### `app/Http/Controllers/LocationController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\Municipality;
use App\Models\Barangay;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * GET /api/v1/locations/municipalities
     * Returns all municipalities in Sorsogon — no auth required
     */
    public function municipalities()
    {
        $municipalities = Municipality::orderBy('name')->pluck('name');

        return response()->json([
            'data' => $municipalities,
            'total' => $municipalities->count(),
        ]);
    }

    /**
     * GET /api/v1/locations/barangays?municipality=Sorsogon City
     * Returns barangays for a given municipality — no auth required
     */
    public function barangays(Request $request)
    {
        $request->validate([
            'municipality' => 'required|string|exists:municipalities,name',
        ]);

        $barangays = Barangay::whereHas('municipality', function ($q) use ($request) {
            $q->where('name', $request->municipality);
        })
        ->orderBy('name')
        ->pluck('name');

        return response()->json([
            'data' => $barangays,
            'municipality' => $request->municipality,
            'total' => $barangays->count(),
        ]);
    }
}
```

---

## STEP 4 — Register Routes

In `routes/api.php`, add inside the `v1` prefix group — **outside** any auth middleware:

```php
// Location dropdowns — public, no auth required
Route::prefix('locations')->group(function () {
    Route::get('municipalities', [LocationController::class, 'municipalities']);
    Route::get('barangays', [LocationController::class, 'barangays']);
});
```

Add the import at the top of `api.php`:
```php
use App\Http\Controllers\LocationController;
```

---

## STEP 5 — Test the Endpoints

```bash
# Test municipalities
curl https://your-app.onrender.com/api/v1/locations/municipalities

# Expected response:
{
  "data": ["Barcelona", "Bulan", "Bulusan", ...],
  "total": 15
}

# Test barangays
curl "https://your-app.onrender.com/api/v1/locations/barangays?municipality=Sorsogon%20City"

# Expected response:
{
  "data": ["Abuyog", "Almendras-Cogon", ...],
  "municipality": "Sorsogon City",
  "total": 64
}

# Test validation error
curl "https://your-app.onrender.com/api/v1/locations/barangays?municipality=FakeCity"

# Expected: 422 with validation error
{
  "message": "The selected municipality is invalid.",
  "errors": { "municipality": ["The selected municipality is invalid."] }
}
```

---

## STEP 6 — Update Existing Validation

Anywhere in the backend where `barangay` or `municipality` are validated (registration, profile update, job posting), add exists validation:

```php
// In RegisterController, ProfileController, JobController
'municipality' => 'required|string|exists:municipalities,name',
'barangay'     => 'required|string|exists:barangays,name',
```

This ensures only valid Sorsogon locations can be submitted — no fake barangay names.

---

## IMPORTANT NOTES

- Both endpoints are **public** — no `auth:sanctum` middleware
- These tables are **read-only** from the API — no POST/PUT/DELETE endpoints needed
- The `municipalities` table uses `name` as the display value AND the stored value in `users`, `job_posts` — no ID foreign keys needed in those tables
- Do NOT run migrations for these tables — they are seeded directly via SQL in Supabase
- Add `municipalities` and `barangays` to the list of tables that already exist in Supabase (do not re-migrate)
