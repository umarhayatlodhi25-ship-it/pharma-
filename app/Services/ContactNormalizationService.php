<?php

namespace App\Services;

class ContactNormalizationService
{
    /**
     * Normalize email string to lowercase and trimmed.
     */
    public static function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    /**
     * Normalize phone numbers consistently.
     * E.g. "+92-300-1234567", "00923001234567", "0300 1234567", "3001234567"
     * -> "03001234567"
     */
    public static function normalizePhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Keep only digits
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (empty($digits)) {
            return '';
        }

        // If begins with 0092, replace with 0
        if (str_starts_with($digits, '0092')) {
            $digits = '0' . substr($digits, 4);
        }
        // If begins with 92, replace with 0
        elseif (str_starts_with($digits, '92')) {
            $digits = '0' . substr($digits, 2);
        }
        // If 10 digits starting with 3 (e.g. 3001234567), prepend 0
        elseif (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /**
     * Compare two phone numbers for equality taking into account country code variations.
     */
    public static function phoneMatches(?string $phoneA, ?string $phoneB): bool
    {
        $normA = self::normalizePhone($phoneA);
        $normB = self::normalizePhone($phoneB);

        if (empty($normA) || empty($normB)) {
            return false;
        }

        if ($normA === $normB) {
            return true;
        }

        // Compare last 9 or 10 digits
        $suffixA = substr($normA, -9);
        $suffixB = substr($normB, -9);

        return !empty($suffixA) && $suffixA === $suffixB;
    }
}
