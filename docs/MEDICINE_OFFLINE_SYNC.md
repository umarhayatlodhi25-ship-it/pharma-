# Offline Medicine Master Synchronization

## 1. Why offline support is required

The application must remain operational during internet outages. Offline support must not rely only on Firestore client persistence because the app also includes local pharmacy inventory and batch stock logic.

## 2. Requirements

The offline medicine master design must support:

- local cache in IndexedDB
- local search for medicine master data
- search results without requiring a continuous internet connection
- background synchronization
- incremental updates
- conflict resolution rules
- weekly and monthly backup retention
- sync status indicators in admin screens

## 3. Local cache model

Use IndexedDB with a key-value store or object stores such as:

- `medicine_master_cache`
- `medicine_master_sync_state`
- `medicine_master_import_history`
- `medicine_master_backups`

Suggested record shape:

```json
{
  "medicineId": "abc123",
  "medicineName": "Panadol Extra",
  "brandName": "Panadol Extra",
  "genericName": "Paracetamol",
  "status": "verified",
  "updatedAt": "2026-11-01T12:00:00Z",
  "syncedAt": "2026-11-01T12:00:00Z",
  "source": "DRAP",
  "sourceReference": "DRAP-REF-001"
}
```

## 4. Search strategy offline

Use a lightweight local index with Fuse.js (or an equivalent) for:

- medicine name
- brand name
- generic name
- composition
- strength
- manufacturer
- registration number

Search must be:

- case-insensitive
- partial-match friendly
- debounced at the UI layer
- paginated when the local cache becomes large

## 5. Synchronization behavior

- Sync only changed records since `lastSyncAt`
- Use a delta API when provider access is available
- Store `lastSyncAt`, `syncStatus`, and `lastSyncError`
- Run a background queue when connectivity returns
- Keep an explicit `pending` state for failed syncs

## 6. Conflict handling

Local inventory data must always win over master data for these fields:

- stock
- purchase price
- sale price
- barcode
- batch number
- expiry date

The master database must never overwrite these values.

## 7. Backups

- weekly backup: retain local cache snapshot and summary metadata
- monthly backup: archive one stabilized dataset snapshot
- include export metadata and count summary

## 8. UI indicators

Add a sync state badge to admin screens:

- online
- offline
- syncing
- sync failed
- stale data

## 9. Implementation note

This app currently has no dedicated client-side offline medicine cache or sync queue. This is a required future implementation before production deployment for a 50,000+ medicine master dataset.
