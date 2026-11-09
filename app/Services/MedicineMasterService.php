<?php

namespace App\Services;

use App\Models\MedicineMaster;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MedicineMasterService
{
    public const VALID_VERIFICATION_STATUSES = ['verified', 'pending', 'rejected'];

    public function normalizeRecord(array $record): array
    {
        $normalized = [
            'medicine_id' => trim((string) ($record['medicine_id'] ?? $record['medicineId'] ?? '')),
            'medicine_name' => trim((string) ($record['medicine_name'] ?? $record['medicineName'] ?? '')),
            'brand_name' => trim((string) ($record['brand_name'] ?? $record['brandName'] ?? '')),
            'generic_name' => trim((string) ($record['generic_name'] ?? $record['genericName'] ?? '')),
            'composition' => trim((string) ($record['composition'] ?? '')),
            'strength' => trim((string) ($record['strength'] ?? '')),
            'dosage_form' => trim((string) ($record['dosage_form'] ?? $record['dosageForm'] ?? '')),
            'category' => trim((string) ($record['category'] ?? '')),
            'manufacturer' => trim((string) ($record['manufacturer'] ?? '')),
            'registration_number' => trim((string) ($record['registration_number'] ?? $record['registrationNumber'] ?? '')),
            'pack_size' => trim((string) ($record['pack_size'] ?? $record['packSize'] ?? '')),
            'stock_unit' => trim((string) ($record['stock_unit'] ?? $record['stockUnit'] ?? '')),
            'source' => trim((string) ($record['source'] ?? '')),
            'source_reference' => trim((string) ($record['source_reference'] ?? $record['sourceReference'] ?? '')),
            'verification_status' => strtolower(trim((string) ($record['verification_status'] ?? $record['verificationStatus'] ?? 'pending'))),
        ];

        if ($normalized['medicine_id'] === '') {
            $normalized['medicine_id'] = $this->generateMedicineId($normalized);
        }

        if (! in_array($normalized['verification_status'], self::VALID_VERIFICATION_STATUSES, true)) {
            $normalized['verification_status'] = 'pending';
        }

        return $normalized;
    }

    public function generateMedicineId(array $record): string
    {
        $source = strtolower(trim((string) ($record['medicine_name'] ?? '')));
        $brand = strtolower(trim((string) ($record['brand_name'] ?? '')));
        $generic = strtolower(trim((string) ($record['generic_name'] ?? '')));
        $strength = strtolower(trim((string) ($record['strength'] ?? '')));
        $manufacturer = strtolower(trim((string) ($record['manufacturer'] ?? '')));

        $seed = implode('|', [$source, $brand, $generic, $strength, $manufacturer]);

        return 'med_' . substr(md5($seed), 0, 20);
    }

    public function buildDuplicateHash(array $record): string
    {
        $normalized = $this->normalizeRecord($record);

        $parts = [
            strtolower($normalized['medicine_name'] ?? ''),
            strtolower($normalized['brand_name'] ?? ''),
            strtolower($normalized['generic_name'] ?? ''),
            strtolower($normalized['strength'] ?? ''),
            strtolower($normalized['dosage_form'] ?? ''),
            strtolower($normalized['manufacturer'] ?? ''),
        ];

        return md5(implode('|', $parts));
    }

    public function validateRecord(array $record): void
    {
        $normalized = $this->normalizeRecord($record);

        if (trim((string) $normalized['medicine_name']) === '') {
            throw new InvalidArgumentException('Medicine name is required.');
        }

        if (trim((string) $normalized['source']) === '') {
            throw new InvalidArgumentException('Source is required.');
        }

        if (trim((string) $normalized['source_reference']) === '') {
            throw new InvalidArgumentException('Source reference is required.');
        }

        if (! in_array($normalized['verification_status'], self::VALID_VERIFICATION_STATUSES, true)) {
            throw new InvalidArgumentException('Verification status is invalid.');
        }
    }

    public function upsert(array $record): MedicineMaster
    {
        $normalized = $this->normalizeRecord($record);
        $this->validateRecord($normalized);

        $existing = MedicineMaster::query()
            ->where('medicine_id', $normalized['medicine_id'])
            ->orWhere('source_reference', $normalized['source_reference'])
            ->first();

        if ($existing) {
            $existing->fill($normalized);
            $existing->save();

            return $existing;
        }

        $duplicateCandidate = MedicineMaster::query()
            ->where('medicine_name', $normalized['medicine_name'])
            ->where('brand_name', $normalized['brand_name'])
            ->where('generic_name', $normalized['generic_name'])
            ->where('strength', $normalized['strength'])
            ->where('manufacturer', $normalized['manufacturer'])
            ->first();

        if ($duplicateCandidate) {
            $duplicateCandidate->fill($normalized);
            $duplicateCandidate->save();

            return $duplicateCandidate;
        }

        return MedicineMaster::query()->create($normalized);
    }

    public function importChunk(array $records): array
    {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($records as $index => $record) {
            if (! is_array($record)) {
                $results['failed']++;
                $results['errors'][] = ['index' => $index, 'error' => 'Record is not an array.'];
                continue;
            }

            try {
                $normalized = $this->normalizeRecord($record);
                $this->validateRecord($normalized);

                $existing = MedicineMaster::query()->where('medicine_id', $normalized['medicine_id'])->first();
                if ($existing) {
                    $existing->fill($normalized);
                    $existing->save();
                    $results['updated']++;
                    continue;
                }

                $duplicateHash = $this->buildDuplicateHash($normalized);
                $dup = MedicineMaster::query()
                    ->where('medicine_name', $normalized['medicine_name'])
                    ->where('brand_name', $normalized['brand_name'])
                    ->where('generic_name', $normalized['generic_name'])
                    ->where('strength', $normalized['strength'])
                    ->where('manufacturer', $normalized['manufacturer'])
                    ->first();

                if ($dup) {
                    $results['skipped']++;
                    continue;
                }

                MedicineMaster::query()->create($normalized);
                $results['imported']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = ['index' => $index, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function search(string $query, int $page = 1, int $perPage = 20): Collection
    {
        $term = trim($query);

        if ($term === '') {
            return MedicineMaster::query()->orderByDesc('updated_at')->limit($perPage)->get();
        }

        $search = '%'.$term.'%';

        return MedicineMaster::query()
            ->where(function ($q) use ($search, $term) {
                $q->whereRaw('LOWER(medicine_name) LIKE ?', [strtolower($search)])
                    ->orWhereRaw('LOWER(brand_name) LIKE ?', [strtolower($search)])
                    ->orWhereRaw('LOWER(generic_name) LIKE ?', [strtolower($search)])
                    ->orWhereRaw('LOWER(composition) LIKE ?', [strtolower($search)])
                    ->orWhereRaw('LOWER(strength) LIKE ?', [strtolower($search)])
                    ->orWhereRaw('LOWER(dosage_form) LIKE ?', [strtolower($search)])
                    ->orWhereRaw('LOWER(manufacturer) LIKE ?', [strtolower($search)])
                    ->orWhereRaw('LOWER(registration_number) LIKE ?', [strtolower($search)]);
            })
            ->orderByRaw('CASE WHEN LOWER(medicine_name) = ? THEN 0 WHEN LOWER(brand_name) = ? THEN 1 ELSE 2 END', [strtolower($term), strtolower($term)])
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();
    }
}
