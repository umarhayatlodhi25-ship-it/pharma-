# Medicine API Setup and Data Provider Configuration

## 1. Operational guidance

This application currently does not contain any verified production dataset credentials for Pakistan medicine providers. The implementation should therefore be designed to support official providers without hardcoding keys or fabricating records.

## 2. Preferred provider order

1. DRAP Pakistan (preferred official source)
2. DrugsInfo Pakistan (if licensing and API access are authorized)
3. Other licensed Pakistani pharmaceutical data providers with formal permission

## 3. Security rule

Never place API credentials in frontend code or public client bundles. All authentication must happen server-side in Laravel configuration or environment variables only.

## 4. Required environment variables

Add these values to the application environment:

```env
# Medicine master provider configuration
MEDICINE_PROVIDER=DRAP
DRAP_BASE_URL=https://example.gov.pk/api
DRAP_API_KEY=replace_with_secure_server_key
DRAP_API_SECRET=replace_with_secure_server_secret
DRAP_TIMEOUT_SECONDS=20

DRUGS_INFO_BASE_URL=https://api.example.com
DRUGS_INFO_API_KEY=replace_with_secure_server_key
DRUGS_INFO_TIMEOUT_SECONDS=20

# Firestore / master DB configuration
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CLIENT_EMAIL=your-service-account-email
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIRESTORE_MEDICINE_MASTER_COLLECTION=medicine_master

# Offline / sync configuration
MEDICINE_SYNC_INTERVAL_HOURS=24
MEDICINE_BACKUP_DAYS=7
MEDICINE_BACKUP_MONTHLY_DAY=1
```

## 5. Laravel config pattern

Use `config/services.php` to centralize the config for server-side access:

```php
'medicine_providers' => [
    'drap' => [
        'base_url' => env('DRAP_BASE_URL'),
        'api_key' => env('DRAP_API_KEY'),
        'api_secret' => env('DRAP_API_SECRET'),
    ],
    'drugs_info' => [
        'base_url' => env('DRUGS_INFO_BASE_URL'),
        'api_key' => env('DRUGS_INFO_API_KEY'),
    ],
],
```

## 6. Provider adapter design

Build provider adapters that implement the same interface:

```php
interface MedicineProviderAdapterInterface
{
    public function fetchPage(int $page, int $pageSize): array;
    public function normalize(array $rawRecord): array;
    public function validate(array $record): bool;
}
```

This keeps the import layer independent from any one provider.

## 7. Licensing and legal compliance

Before importing provider data:

- confirm the license
- verify redistribution rights
- confirm source attribution
- verify restrictions on commercial reuse
- confirm whether public use is allowed for the specific dataset

Do not scrape a site or API that denies access or requires private authorization.

## 8. Current repository status

No verified DRAP or DrugsInfo credentials were found in this codebase. This is not a blocker for implementing infrastructure, but it is a blocker for production imports until valid credentials or approved sources are configured.

## 9. Safe implementation path

- implement provider interface layer
- accept authorized CSV/JSON imports
- keep source metadata in each imported record
- do not mark records as verified without proper source validation

## 10. Deployment notes

- keep provider keys in server environment or managed secret store
- do not include them in commits, `.env.example`, or frontend bundles
- rotate keys when personnel or vendors change
- log API failures without exposing secrets
