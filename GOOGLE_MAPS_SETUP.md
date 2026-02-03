# Google Maps API Setup

To use address autocomplete and the map pin picker, you need to enable the following APIs in Google Cloud Console and add your API key.

## 1. Create or Select a Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Make sure billing is enabled (APIs require a billing account, but the free tier is generous)

## 2. Enable Required APIs

Go to **APIs & Services → Library** and enable:

| API | Purpose |
|-----|---------|
| **Maps JavaScript API** | Renders maps and provides Geocoder |
| **Places API** | Address autocomplete suggestions |
| **Geocoding API** | Reverse geocoding when dragging the pin (structures) |

Search for each by name and click **Enable**.

## 3. Create an API Key

1. Go to **APIs & Services → Credentials**
2. Click **Create Credentials → API Key**
3. Copy the generated key
4. (Recommended) Click **Edit API key** and restrict it:
   - **Application restrictions**: HTTP referrers
   - **Website restrictions**: Add your domain(s), e.g. `https://wvrdist.test/*`, `http://localhost/*`, `https://yourdomain.com/*`
   - **API restrictions**: Restrict to the 3 APIs above (Maps JavaScript API, Places API, Geocoding API)

## 4. Add Key to `.env`

Add to your `.env` file:

```
GOOGLE_PLACES_API_KEY=your_api_key_here
```

Then run `php artisan config:clear` if needed.

## Summary of What to Enable

- ✅ **Maps JavaScript API**
- ✅ **Places API**
- ✅ **Geocoding API**

Without these, the forms will fall back to basic text inputs (no autocomplete, no map).
