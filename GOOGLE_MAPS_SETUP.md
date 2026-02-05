# Google Maps API Setup

To use address autocomplete and the map pin picker, you need to enable the required APIs in Google Cloud Console, create an API key, and **allow your site’s URL** in the key restrictions.

## 1. Create or Select a Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Make sure billing is enabled (APIs require a billing account; the free tier is generous)

## 2. Enable Required APIs

Go to **APIs & Services → Library** and enable:

| API | Purpose |
|-----|---------|
| **Maps JavaScript API** | Renders maps and provides Geocoder |
| **Places API** | Legacy; keep enabled if you use older integrations |
| **Places API (New)** | **Required** for address autocomplete (PlaceAutocompleteElement) |
| **Geocoding API** | Reverse geocoding when dragging the pin (structures) |

Search for each by name and click **Enable**.

## 3. Create an API Key and Set HTTP Referrer Restrictions

1. Go to **APIs & Services → Credentials**
2. Click **Create Credentials → API Key**
3. Copy the generated key
4. Click **Edit API key** (recommended) and under **Application restrictions** choose **HTTP referrers**
5. Under **Website restrictions**, add **each** of these referrers that you use:

   **Important:** The pattern `*.wwrportal.test/*` matches only **subdomains** (e.g. `app.wwrportal.test`). It does **not** match the bare domain `wwrportal.test`. If your site is `https://wwrportal.test`, you must add:

   - `https://wwrportal.test/*`
   - `http://wwrportal.test/*`

   Add one entry per line. Examples:

   - `https://wwrportal.test/*`
   - `http://wwrportal.test/*`
   - `https://localhost/*`
   - `http://localhost/*`
   - `https://yourdomain.com/*` (for production)

   Without these, you will see **RefererNotAllowedMapError** in the browser console and the map/autocomplete will not load.

6. Under **API restrictions**, restrict the key to the APIs above (Maps JavaScript API, Places API, Places API (New), Geocoding API).

## 4. Add Key to `.env`

Add to your `.env` file:

```
GOOGLE_PLACES_API_KEY=your_api_key_here
```

Then run `php artisan config:clear` if needed.

## 5. Loading and Autocomplete Behavior

- The app loads the Maps JavaScript API with the **dynamic loader** (`importLibrary`), which satisfies the **loading=async** requirement and avoids console warnings.
- Address autocomplete uses **PlaceAutocompleteElement** (Places API New), not the legacy `Autocomplete` class, so it works for new API keys and stays within Google’s recommended approach.

## Summary of What to Enable

- ✅ **Maps JavaScript API**
- ✅ **Places API** (optional, for legacy use)
- ✅ **Places API (New)** — required for autocomplete
- ✅ **Geocoding API**

And in **HTTP referrers**, include the exact origins you use (e.g. `https://wwrportal.test/*` and `http://wwrportal.test/*`).

Without these, the forms will fall back to basic text inputs (no autocomplete, no map).
