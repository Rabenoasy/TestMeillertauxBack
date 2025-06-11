<?php

namespace App\Service\Data;

class LoanConstant
{
    /**
     * Paths to the JSON files containing loan offers from different banks.
     * These paths are relative to the project directory.
     */
    public const FILE_PATHS = [
        'BNP' => '/src/Data/BNP.json',
        'CARREFOURBANK' => '/src/Data/CARREFOURBANK.json',
        'SG' => '/src/Data/SG.json',
    ];

    /**
     * Allowed amounts for the loan offers.
     * These values should match the amounts available in the JSON files.
     */
    public const ALLOWED_AMOUNTS = [50000, 100000, 200000, 500000];

    /**
     * Allowed durations for the loan offers in months.
     * These values should match the durations available in the JSON files.
     */
    public const ALLOWED_DURATIONS = [15, 20, 25];

    /**
     * HTTP status codes used in the API responses.
     */
    public const STATUS_CODES = [
        'success' => 200,
        'bad_request' => 400,
        'not_found' => 404,
        'internal_error' => 500,
        'no_content' => 204,
    ];
}
