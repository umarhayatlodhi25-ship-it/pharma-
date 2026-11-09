# Medicine Import Guide

## 1. Purpose

This guide describes the current import pattern and the required master-database-safe approach for importing Pakistan medicine metadata into a dedicated `medicine_master` dataset.

## 2. Current repository state

The repository already contains a bulk medicine import system in:

- `app/Services/MedicineImportService.php`
- `app/Livewire/Admin/BulkAddMedicine.php`

That import flow is useful as a base, but it is oriented around existing pharmacy inventory data and not yet a strict medicine master import pipeline.

## 3. Import requirements for the master database

For any import into `medicine_master`, require:

- `medicineName`
- `source`
- `sourceReference`
- `verificationStatus`

Required validation rules:

- record must not be blank or incomplete
- duplicate detection must be done before write
- records without source reference must fail
- records without verification status must be marked `pending` or rejected

## 4. CSV import template

Create a file such as `docs/medicine_master_template.csv` with the following structure:

```csv
medicineName,brandName,genericName,composition,strength,dosageForm,category,manufacturer,registrationNumber,packSize,stockUnit,source,sourceReference,verificationStatus
Panadol Extra,Panadol Extra,Paracetamol,Paracetamol 500 mg,500 mg,Tablet,Analgesic,GlaxoSmithKline,NA,10 Tablets,Tablet,DRAP,DRAP-REF-001,verified
```

A full template file is also provided as:

- `docs/medicine_master_template.csv`

## 5. JSON import example

```json
[
  {
    "medicineName": "Panadol Extra",
    "brandName": "Panadol Extra",
    "genericName": "Paracetamol",
    "composition": "Paracetamol 500 mg",
    "strength": "500 mg",
    "dosageForm": "Tablet",
    "category": "Analgesic",
    "manufacturer": "GlaxoSmithKline",
    "registrationNumber": "NA",
    "packSize": "10 Tablets",
    "stockUnit": "Tablet",
    "source": "DRAP",
    "sourceReference": "DRAP-REF-001",
    "verificationStatus": "verified"
  }
]
```

## 6. Import pipeline design

1. Parse file to rows
2. Normalize field names
3. Detect duplicates
4. Validate source and status
5. Preview results to admin
6. Import in chunks
7. Track import history
8. Export failed rows

## 7. Duplicate prevention

Use stable IDs based on normalized keys such as:

- `medicineName + brandName + genericName + strength + dosageForm + manufacturer`

If the normalized pair already exists in the collection, skip or flag as duplicate.

## 8. Batch import guidance

- Use a chunk size of 500 to 1,000 records for each write cycle when required
- For Firestore, respect the 400-write batch limit
- Keep imports idempotent so reruns do not create duplicate master records
- Keep failed records separate from successful records

## 9. Retry and audit rules

- Store import history metadata
- Save source and timestamp
- Keep failed rows downloadable as CSV
- Allow admin retry after fixing source/field issues

## 10. Critical safety rule

Never import unverified sample data, vendor-provided placeholders, or fabricated entries into production. All imported records must point to a real source and proper verification status.
