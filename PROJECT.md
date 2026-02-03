# PROJECT SPECIFICATION: World Vision Distribution Manager (Laravel 12)

**Role:** You are a Senior Laravel Architect and Livewire Specialist.
**Goal:** Build a production-ready logistics application for an NGO to manage educational kit distribution in Romania.

## 1. TECHNOLOGY STACK (NON-NEGOTIABLE)
* **Framework:** Laravel 12 (latest).
* **Frontend:** Livewire 3.
* **UI Library:** **Flux UI** (You must use `<flux:card>`, `<flux:input>`, `<flux:table>`, `<flux:modal>`, etc. Do not write raw Tailwind unless a Flux component doesn't exist).
* **Database:** SQLite (local dev), MySQL (production).
* **Storage:** Cloudflare R2 (configured via Laravel S3 driver) for storing generated PDFs.
* **Maps:** Google Maps Places API (for address autocomplete components).
* **PDF Engine:** `setasign/fpdi` combined with `tfpdf` (or a library capable of importing templates AND supporting **Romanian UTF-8 diacritics** like ș, ț, ă, î, â).
* **Excel:** `maatwebsite/excel`.
* **Permissions:** `spatie/laravel-permission`.

---

## 2. DATABASE SCHEMA DESIGN
Create the migrations based on this exact relationship structure:

1.  **`users`**: Standard Laravel auth.
    * `name`: string
    * `email`: string
    * `password`: string (nullable for magic link users)
    * `role`: enum('admin', 'school_manager', 'educator')
    * `school_id`: nullable foreign key (for school managers).

2.  **`campaigns`**:
    * `id`: bigIncrements
    * `name`: string (e.g., "Brasov - March 2026")
    * `facilitator_name`: string (Default: "Moraru Alin Eduard")
    * `month_year_suffix`: string (e.g., ".03.2026")
    * `target_kits`: integer (e.g., 600)
    * `is_active`: boolean

3.  **`schools`** (Legal Entities - PJ):
    * `id`: uuid
    * `campaign_id`: foreign key
    * `official_name`: string (e.g., "Scoala Gimnaziala Nr 1")
    * `cui`: string (Tax ID)
    * `address`: string (Google Maps formatted address)
    * `director_name`: string
    * `access_token`: string (unique token for Magic Link login)

4.  **`structures`** (Physical Locations/Satellites - Arondate):
    * `id`: uuid
    * `school_id`: foreign key
    * `name`: string (e.g., "Gradinita cu Program Prelungit Nr. 4")
    * `address`: string

5.  **`groups`** (Classes):
    * `id`: uuid
    * `structure_id`: foreign key
    * `name`: string (**Free text input**, e.g., "Grupa Mare", "Grupa Germana", "Grupa Albinutelor")
    * `educator_name`: string

6.  **`children`**:
    * `id`: uuid
    * `group_id`: foreign key
    * `child_full_name`: string (Must be stored UPPERCASE)
    * `parent_full_name`: string (Title Case)
    * `gdpr_status`: boolean (default true when added)

---

## 3. CORE FEATURES & LOGIC

### A. Authentication: "Magic Links" (No Passwords for Schools)
* **Admin:** Uses standard email/password login.
* **Schools:** Do NOT force them to register with passwords.
    * **Logic:** Admin generates a "Magic Link" for a School (e.g., `app.com/access/{school_uuid}?token={access_token}`).
    * **Middleware:** Create `EnsureValidSchoolToken` middleware that logs them in as a generic "School Manager" user restricted to that School ID scope.

### B. Address Autocomplete (Google Maps)
* Create a reusable Livewire component: `AddressAutocomplete.php`.
* It should use `<flux:input>` paired with Alpine.js to hook into the Google Places API.
* On selection, it must emit the formatted address back to the parent form.

### C. The PDF Generation Engine (COMPLEX & CRITICAL)
Create a service class `PdfGeneratorService`.
* **Input:** Receives a `Group` model or `School` model.
* **Templates:** The app will have 3 base PDF templates (Protocol, Tabel, GDPR) stored in `storage/app/templates`.
* **Font Requirement:** You must use a font like **DejaVu Sans** to ensure Romanian characters (ș, ț) are rendered correctly. Standard PDF fonts will fail.
* **Logic:**
    1.  **Protocol (Contract):** Open template -> Fill School Name, Director Name, Address -> Save.
    2.  **GDPR Form:**
        * Loop through all children in a selection.
        * For each child, import the GDPR Template Page.
        * **Overlay Text:** `child.full_name`, `parent.full_name`, `school.name`, `campaign.facilitator_name`, `campaign.month_year_suffix`.
        * **CRITICAL:** Leave the "Yes/No" checkboxes, Signature, and Day of Month **BLANK** (for wet ink).
        * Concatenate all pages into one large PDF.
    3.  **Distribution Table:**
        * Import Table Template.
        * Fill Header (School, Structure, Group).
        * Generate rows dynamically: No. | Child Name | Parent Name | [Blank Signature].
* **Output:** Save the resulting PDF to Cloudflare R2 and return a temporary signed download URL.

### D. UI/UX Rules (Flux UI)
* **Language:** The interface must be in **Romanian**.
* **Admin Dashboard:** Sidebar layout. Map view of schools (Markers colored by status: Red=Empty, Green=Ready).
* **School Portal:** Simple, centered layout (Distraction-free).
    * **Hierarchy:** Show School Details -> List of Structures -> List of Groups inside structures.
    * **Data Entry:** Inside a Group, use a `<flux:table>` with an inline "Add Child" form.
    * **Validation:** Prevent saving a child if Parent Name is missing.

---

## 4. STEP-BY-STEP IMPLEMENTATION PLAN
*Please execute these steps sequentially. Do not jump ahead.*

**Step 1:** Setup
* Install Livewire, Flux, Spatie Permission, and Excel.
* Create the Migration files for the Schema defined above.
* Run migrations.

**Step 2:** Models
* Create Models with correct relationships (`hasMany`, `belongsTo`).
* Add a trait `HasUuid` for the UUID models.

**Step 3:** Admin Dashboard (Livewire)
* Create `CampaignManager` component.
* Create `SchoolManager` component (CRUD).

**Step 4:** The School Portal (Public facing)
* Create the Magic Link logic and Middleware.
* Create the nested forms for Structure -> Group -> Child using Flux modals.

**Step 5:** PDF Service
* Install `setasign/fpdi` and `tfpdf` (or equivalent for UTF-8).
* Write the logic to overlay text on existing PDF templates.

**Step 6:** Deployment Config
* Ensure `fly.toml` or `Dockerfile` is ready for production.

**START NOW:** Begin with **Step 1** (Setup & Migrations). Show me the migration code before running it.