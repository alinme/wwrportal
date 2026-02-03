# Cloudflare R2 Setup (for PDF storage)

The app can store generated PDFs (contract, annex) in **Cloudflare R2** and return temporary signed download URLs. R2 is S3-compatible, so Laravel’s S3 driver is used with an R2 endpoint.

## 1. Create an R2 bucket

1. Log in to [Cloudflare Dashboard](https://dash.cloudflare.com/) → **R2**.
2. Click **Create bucket** and choose a name (e.g. `wwrportal-pdfs`).
3. Optionally enable **Public access** if you want public URLs; for private downloads (signed URLs) leave it off.

## 2. Create R2 API tokens

1. In R2, go to **Manage R2 API Tokens** (or **Overview** → **R2 API Tokens**).
2. **Create API token**:
   - Name: e.g. `wwrportal`
   - Permissions: **Object Read & Write**
   - Specify the bucket or “Apply to all buckets”.
3. Copy the **Access Key ID** and **Secret Access Key** (the secret is shown only once).

## 3. Get your account ID and endpoint

- **Account ID:** In the R2 overview, the URL looks like `https://dash.cloudflare.com/<account_id>/r2`. Use that `<account_id>`.
- **Endpoint:**  
  `https://<account_id>.r2.cloudflarestorage.com`

## 4. Set environment variables

Add to your `.env` (see `.env.example` for the list):

```env
# Cloudflare R2 (S3-compatible)
R2_ACCESS_KEY_ID=your_access_key_id
R2_SECRET_ACCESS_KEY=your_secret_access_key
R2_BUCKET=wwrportal-pdfs
R2_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
R2_REGION=auto
# Optional: public URL for the bucket (if you enabled public access). Leave empty for private + signed URLs.
R2_URL=
```

Replace:

- `your_access_key_id` / `your_secret_access_key` with the token from step 2.
- `wwrportal-pdfs` with your bucket name.
- `YOUR_ACCOUNT_ID` with your Cloudflare account ID.

`config/filesystems.php` already defines the `r2` disk using these variables. When they are set, `PdfGeneratorService::saveToR2AndGetUrl()` will store PDFs in R2 and return a temporary signed URL (or a public URL if you set `R2_URL`).

## 5. Verify

- Generate a contract or annex from the school portal. If R2 is configured, the app will redirect to a temporary download URL instead of streaming the file directly.
- If R2 is not configured (missing or empty R2 vars), the app falls back to streaming the PDF in the response.
