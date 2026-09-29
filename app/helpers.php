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
