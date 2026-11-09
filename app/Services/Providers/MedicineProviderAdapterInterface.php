<?php

namespace App\Services\Providers;

interface MedicineProviderAdapterInterface
{
    public function fetchPage(int $page, int $pageSize): array;

    public function normalize(array $rawRecord): array;

    public function validate(array $record): bool;
}
