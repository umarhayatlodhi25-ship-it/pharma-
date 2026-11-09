# Pakistan Medicine Master Database Implementation Plan

## 1. Scope and objective

Create a dedicated master dataset for Pakistan medicines while preserving the existing inventory and POS system. The master data must remain separate and should never contain inventory-specific fields such as:

- stock
- purchase price
- sale price
- batch number
- expiry date
- barcode used in current pharmacy stock records

These fields stay in the inventory layer and are tied to a pharmacy product.

## 2. Target domain model

Proposed Firestore collection:

- `medicine_master`

Recommended document schema:

```json
{
  "medicineId": "string",
  "medicineName": "string",
  "brandName": "string",
  "genericName": "string",
  "composition": "string",
  "strength": "string",
  "dosageForm": "string",
  "category": "string",
  "manufacturer": "string",
  "registrationNumber": "string",
  "packSize": "string",
  "stockUnit": "string",
  "source": "string",
  "sourceReference": "string",
  "verificationStatus": "string",
  "createdAt": "timestamp",
  "updatedAt": "timestamp"
}
```

## 3. Separation of concerns

### Master layer

Use the master records only for canonical metadata and verification metadata.

### Inventory layer

Keep inventory and product-specific fields in the existing Laravel tables:

- `medicines`
- `medicine_batches`
- `medicine_packaging`
- `inventory`
- `stock_movements`

### Linking pattern

Add a foreign reference from a product row to a master record when needed, for example:

- `medicine_master_id` on inventory-linked product records
- or a lookup table `product_master_links`

The master record should never be overwritten by stock changes.

## 4. Data integrity rules

- Only verified records are selectable by default
- Pending records may be enabled explicitly by admin
- Rejected records must remain read-only in search results unless admin overrides deliberately
- Every record must have a source and source reference
- Detect incomplete rows before import
- Use stable document IDs for idempotent import
- Enforce duplicate prevention by normalized key fields

## 5. Candidate implementation structure

### Laravel side

Add new service classes:

- `App\Services\MedicineMasterService`
- `App\Services\MedicineMasterSearchService`
- `App\Services\MedicineProviderRegistry`
- `App\Services\Providers\DrapProviderAdapter`
- `App\Services\Providers\DrugsInfoProviderAdapter`
- `App\Services\MedicineImportValidationService`

### Firestore side

Use a single collection:

- `medicine_master`

Recommended indexes:

- `medicineName` ascending
- `brandName` ascending
- `genericName` ascending
- `manufacturer` ascending
- `verificationStatus` ascending
- `sourceReference` ascending

## 6. Import strategy

- Accept CSV, JSON, and optional Excel files
- Use chunked processing for 50,000+ records
- Use batch limits of 400 writes per Firestore batch
- Normalize names and detect duplicates before writing
- Keep import history and failed-row exports
- Retry failed imports with the same normalized payload

## 7. Search strategy

For the default Laravel + Livewire stack:

- Use a dedicated search query on master records with case-insensitive matching
- Add ranking based on exact match, brand match, generic match, and manufacturer match
- Add pagination for large datasets
- Use a full-text service only when the dataset grows beyond the practical threshold for direct DB queries

For offline search:

- Cache a subset or compact dictionary in IndexedDB
- Use Fuse.js or a small local index for fast local search
- Do not download the full dataset on every search

## 8. Offline sync strategy

- Maintain a local medicine master cache in IndexedDB
- Synchronize incremental updates only
- Keep a `lastSyncedAt` state and `syncStatus`
- Use a conflict policy where local inventory values always win
- Never overwrite local stock or pricing data with master data updates

## 9. Provider integration plan

### Preferred providers

1. DRAP Pakistan (official and preferred source)
2. DrugsInfo Pakistan (licensed datasets if available)
3. Other verified Pakistani pharmaceutical dataset providers with a public data-sharing or API agreement

### Safety requirements

- Keep credentials in env or secure server settings
- Never expose keys in frontend code
- Do not bypass access restrictions
- Perform licensing checks before any data import
- No fabricated records or false claims of dataset size

## 10. Admin dashboard requirements

Display metrics:

- total medicines
- verified
- pending
- rejected
- total manufacturers
- total generic molecules
- last import date
- last synchronization
- failed imports
- duplicate count

Actions:

- import
- search
- view import history
- sync database
- export dataset
- manage category and manufacturer metadata

## 11. Validation and test plan

Target dataset checks:

- 10,000 rows
- 25,000 rows
- 50,000 rows

Verify:

- import correctness
- duplicate prevention
- search performance
- pagination
- offline cache search
- sync conflict handling
- POS integration
- inventory guardrails
- role permissions
- no regressions in existing workflows

## 12. Recommended next implementation step

Before code changes, add the master schema definitions and provider-interface layer in a separate, backward-compatible pattern. Keep all inventory logic in the current Laravel tables until the master dataset has passed validation and duplicate protection rules.

## 13. Known limitations in current repo

- Firebase is not yet a Firestore-backed medicine master store
- No provider adapter exists in the workspace
- No medicine master search service exists
- No offline medicine cache exists
- No verification or source audit engine exists yet

## 14. Critical rule

The medicine master must remain a metadata collection, not a stock ledger. Inventory, batch-level detail, and pricing are not part of the master dataset.
