# Pakistan Medicine Master Database Audit

## 1. Executive Summary

This repository is not a React + Firebase application. It is a Laravel 12 + Livewire + Vite + Blade application with Firebase integration for user sync, and a Firestore config file present in the root. The current codebase is organized around a pharmacy inventory system, not a separate medicine master database.

Important findings:

- Frontend: Laravel Blade + Livewire, compiled through Vite
- Backend: Laravel 12 (PHP 8.2)
- Firebase: Realtime Database sync for user records; Firestore configuration exists but no active Firestore SDK or collection layer for a master medicine database
- Master medicine dataset: not present yet
- Inventory model: present and active; uses standard pharmacy fields such as `medicines`, `medicine_batches`, `medicine_packaging`, `inventory`, and `stock_movements`
- Stock-unit flexibility: already implemented via `units` and `medicine_packaging`
- Bulk inventory import exists, but it is pharmacy inventory-centric rather than a separate verified master database

The repository is therefore a strong base for a verified `medicine_master` module, but it requires a dedicated domain model, provider adapters, validation rules, and strict separation between master records and pharmacy inventory data.

## 2. Project Architecture

### 2.1 Frontend / UI

- `package.json`
- `vite.config.js`
- `resources/views/**`
- `resources/js/**`
- `app/Livewire/**`

Key aspects:

- The app uses Livewire components for interactive pages and admin workflows.
- Vite handles frontend asset compilation.
- UI is server-rendered Blade templates combined with Livewire component logic instead of a SPA.

### 2.2 Backend

- `composer.json` declares Laravel 12 and Livewire 4
- Main route definitions: `routes/web.php`
- Controllers: `app/Http/Controllers/**`
- Livewire modules: `app/Livewire/**`
- Domain models: `app/Models/**`
- Services: `app/Services/**`

### 2.3 Database architecture

The primary data model is pharmacy inventory centric:

- `categories` – medicine categories
- `medicines` – product master records for stockable inventory items
- `medicine_batches` – batch/expiry/stock entries
- `medicine_packaging` – flexible packaging/unit hierarchy
- `units` – system units such as Tablet, Strip, Pack, Bottle, Vial, ml, etc.
- `inventory` – aggregate stock cache
- `stock_movements` – contribution ledger for stock changes

Main migration files:

- `database/migrations/2026_08_21_192907_create_pharmacy_tables.php`
- `database/migrations/2026_08_29_000001_create_units_table.php`
- `database/migrations/2026_08_29_000002_create_medicine_packaging_table.php`
- `database/migrations/2026_08_29_000003_create_inventory_and_stock_movements_tables.php`
- `database/migrations/2026_08_29_000004_add_packaging_fields_to_pharmacy_tables.php`

## 3. Firebase Configuration Findings

### 3.1 Firebase files

- `firebase.json` config exists for Firestore rules and indexes
- `firestore.rules`
- `firestore.indexes.json`
- `config/services.php` includes Firebase config values
- `app/Services/FirebaseService.php` syncs users with Firebase Realtime Database

### 3.2 Actual implementation status

The app currently uses Firebase only for user record synchronization, not for medicine master records. The code is based on HTTP API calls to the Firebase Realtime Database and does not currently implement a Firestore medicine collection or Firestore CRUD layer.

This is a key project risk: the repository includes Firebase configuration, but the medicine domain has not been isolated from local pharmacy inventory tables.

## 4. Existing Product / Inventory / POS Modules

### 4.1 Products module

Primary file:

- `app/Models/Medicine.php`
- `app/Livewire/Admin/AddMedicine.php`
- `app/Livewire/Admin/MedicineList.php`
- `app/Livewire/Admin/BulkAddMedicine.php`

This module manages product catalog items and their inventory lifecycle, including:

- names, brands, generic names, strength, dosage form, manufacturer
- stock units and packaging hierarchies
- purchase/sale prices
- barcode
- batch and expiry tracking
- inventory cache and stock ledger updates

### 4.2 Inventory module

Primary files:

- `app/Models/Inventory.php`
- `app/Models/MedicineBatch.php`
- `app/Services/StockLedgerService.php`
- `app/Services/PackagingService.php`

The inventory system tracks stock in base units and supports conversions among packaging levels. The service layer enforces stock movement logic and inventory recalculations.

### 4.3 POS module

Primary file:

- `app/Livewire/Pos/PosCounter.php`

The POS search and cart flow are built around `Medicine` records and `MedicineBatch` data, with inventory stock checks before selling.

### 4.4 Bulk import module

Primary files:

- `app/Services/MedicineImportService.php`
- `app/Livewire/Admin/BulkAddMedicine.php`

The import module supports CSV, JSON-like raw rows, row validation, and stock/batch configurations. It is not yet separated from master-database concerns and currently assumes inventory product context.

### 4.5 Authentication and roles

Primary files:

- `app/Models/User.php`
- `app/Livewire/Admin/RoleManagement.php`
- `app/Http/Controllers/AuthController.php`
- `routes/web.php`

Role checks are implemented using `role` strings and route middleware such as `role:admin` and `role:admin,pharmacist,...`.

### 4.6 Offline storage and sync

Current state: not implemented as a complete application-level offline architecture.

The repo has no dedicated:

- IndexedDB medicine cache
- sync queue for master data
- offline search index
- background synchronization manager
- incremental sync strategy for master medicines

Firebase offline persistence is not enough for complete offline support in a distributed inventory app.

## 5. Existing Medicine Fields and Flexible Stock Unit Functionality

### 5.1 Medicine model

`app/Models/Medicine.php` includes fields and relationships such as:

- `name`
- `generic_name`
- `brand`
- `strength`
- `dosage_unit`
- `dosage_form`
- `base_unit_id`
- `primary_unit`
- `secondary_unit`
- `base_unit`
- `primary_unit_to_secondary`
- `secondary_unit_to_base`
- `manufacturer`
- `barcode`
- `sku`
- `purchase_price`
- `unit_price`
- `primary_unit_selling_price`
- `secondary_unit_selling_price`
- `base_unit_selling_price`
- `alert_quantity`
- `reorder_level`
- `product_type`

### 5.2 Flexible unit support

The repo already supports flexible stock units via:

- `database/migrations/2026_08_29_000001_create_units_table.php`
- `database/migrations/2026_08_29_000002_create_medicine_packaging_table.php`
- `app/Models/Unit.php`
- `app/Models/MedicinePackaging.php`
- `app/Services/PackagingService.php`

Supported unit categories include Tablet, Capsule, Strip, Pack, Box, Bottle, Vial, Ampoule, Tube, Sachet, Piece, Milliliter, Liter, etc.

This is important because the master medicine design must not break or override the pharmacy inventory unit system. The new master database should only store master metadata, while inventory continues to carry conversion logic and unit-specific stock data.

## 6. Reusable Components and Services

These are reusable assets worth preserving:

- `App\Models\Medicine`
- `App\Models\Inventory`
- `App\Models\MedicineBatch`
- `App\Models\MedicinePackaging`
- `App\Models\Unit`
- `App\Services\PackagingService`
- `App\Services\StockLedgerService`
- `App\Services\MedicineImportService`
- `App\Services\FirebaseService`
- `App\Http\Controllers\Api\MedicineSearchController`
- `App\Livewire\Pos\PosCounter`
- `App\Livewire\Admin\BulkAddMedicine`

## 7. Risks Identified

1. Architecture mismatch
   - Current repo is Laravel/Livewire, not React
   - Firebase config is primarily for users, not medicine master data

2. No separate master database
   - `medicine_master` collection does not exist
   - `medicines` table is still used as a live inventory/product table

3. Data model mixing
   - Inventory fields and master fields are currently combined in one record set
   - Sale prices, stock, batch, expiry, barcode are mixed with product metadata

4. Search performance
   - The API search is a basic `%like%` query over `Medicine` and has no pagination, no relevance weighting, and no 50,000+ optimization strategy

5. Offline support gap
   - There is no proper local cache or sync strategy for medicine master records

6. Provider and licensing risk
   - No provider adapter or credential strategy is present
   - No documentation on DRAP or DrugsInfo API permissions or licensing conditions

7. Data integrity risk
   - Without a dedicated verification layer, imported medicine records could be inserted without source reference or verification status

## 8. Recommended Implementation Plan

### Phase 1 – Data foundation

- Create `medicine_master` dataset abstraction separate from inventory and product tables.
- Add a strict master schema with source, verification status, manufacturer, and source reference.
- Keep pharmacy-specific stock and batch fields outside the master data model.

### Phase 2 – Provider integration layer

- Add provider adapters for DRAP, DrugsInfo, and licensed CSV/JSON intake.
- Use env-driven configuration and server-side-only credentials.
- Do not add credentials to frontend code.

### Phase 3 – Import and validation engine

- Reuse the existing import service architecture.
- Add chunked processing, preview, duplicate checks, and idempotent insert logic.
- Keep inventory values untouched when linking master data to products.

### Phase 4 – Search and relevance

- Build a dedicated search service for `medicine_master`.
- Add case-insensitive search, relevance scoring, pagination, and optional external indexer if dataset size grows beyond normal DB limits.
- Add IndexedDB support for offline local cache and search.

### Phase 5 – POS and inventory linking

- Add a `medicineId` reference from inventory product records to master data.
- Only create inventory entries when a selected master medicine is linked to a valid saleable product.
- Preserve all existing batch, stock, expiry, and pricing functionality.

### Phase 6 – Offline / sync / backup

- Add client-side cache for master records.
- Synchronize only delta updates.
- Protect local product stock and pricing from being overwritten by source updates.

### Phase 7 – Admin dashboard and verification

- Add admin metrics for verified, pending, rejected, import failures, and duplicates.
- Use verification logic defined by a source reference and import audit trail.

## 9. Conclusion

The current repository already contains the necessary ingredients for a medicine master architecture: modular services, import tooling, pharmacy inventory design, unit configuration, and admin authentication. However, the current application is inventory-first and not yet master-data-first.

The correct path is to add a separate `medicine_master` layer, keep inventory values isolated, verify provider source legitimacy, and build search/import/offline sync infrastructure around that separation.

## 10. Blockers and Safe Next Step

A true DRAP master dataset is not available in this repository, and no approved API credentials or licensed dataset files were found in the workspace. The safe continuation is therefore:

- implement the infrastructure,
- add provider interfaces,
- allow CSV/JSON import with validation,
- reserve production import for verified data only,
- avoid fabricating medicine records or claiming 50,000+ verified records exist without a verified source.

That approach preserves integrity while still enabling a production-ready architecture.
