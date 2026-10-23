<?php

use App\Models\AccountProfile;

if (!function_exists('account_profile')) {
    /**
     * Get the centralized AccountProfile instance.
     */
    function account_profile(): AccountProfile
    {
        return AccountProfile::current();
    }
}

if (!function_exists('app_display_timezone')) {
    /**
     * Get the application display timezone (e.g. 'Asia/Karachi').
     * Respects the configured timezone in Settings (AccountProfile),
     * falling back to config('app.display_timezone') or 'Asia/Karachi'.
     */
    function app_display_timezone(): string
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('account_profiles')) {
                $profile = AccountProfile::current();
                if (!empty($profile->timezone)) {
                    return $profile->timezone;
                }
            }
        } catch (\Throwable $e) {
            // Fallback during early boot, migrations, or testing
        }

        return config('app.display_timezone', 'Asia/Karachi');
    }
}

if (!function_exists('app_datetime')) {
    /**
     * Get a Carbon instance converted to the application's display timezone.
     * Prevents double-conversion by inspecting the existing timezone.
     */
    function app_datetime($datetime = null): ?\Carbon\Carbon
    {
        if ($datetime === null) {
            return \Carbon\Carbon::now(app_display_timezone());
        }

        $targetTz = app_display_timezone();

        if ($datetime instanceof \DateTimeInterface) {
            $carbon = \Carbon\Carbon::instance($datetime)->copy();
        } else {
            $carbon = \Carbon\Carbon::parse($datetime, config('app.timezone', 'UTC'));
        }

        return $carbon->setTimezone($targetTz);
    }
}

if (!function_exists('format_app_datetime')) {
    /**
     * Format a date/datetime into the application's display timezone.
     * Avoids double conversion between UTC and local time.
     */
    function format_app_datetime($datetime, string $format = 'd M, Y h:i A', ?string $default = '—'): string
    {
        if (empty($datetime)) {
            return $default ?? '';
        }

        try {
            $carbon = app_datetime($datetime);
            return $carbon ? $carbon->format($format) : ($default ?? '');
        } catch (\Throwable $e) {
            return (string) $datetime;
        }
    }
}
