<?php

namespace App\Service;

use App\Service\Data\LoanConstant;
use Psr\Log\LoggerInterface;

class LoanOfferService
{
    private string $projectDir;
    private LoggerInterface $logger;

    public function __construct(string $projectDir, LoggerInterface $logger)
    {
        $this->projectDir = rtrim($projectDir, '/\\'); // Normalize project directory path
        $this->logger = $logger;
    }

    /**
     * Retrieves and normalizes loan offers from various sources.
     *
     * @return array<string, mixed>[] Array of normalized loan offers
     */
    public function getNormalizedOffers(): array
    {
        $offers = [];

        // Ensure FILE_PATHS is a valid array
        if (!defined(LoanConstant::class . '::FILE_PATHS') || !is_array(LoanConstant::FILE_PATHS)) {
            $this->logger->error('LoanConstant::FILE_PATHS is not defined or not an array.');
            return [];
        }

        foreach (LoanConstant::FILE_PATHS as $bank => $relativePath) {
            // Sanitize relative path to prevent path traversal
            $relativePath = ltrim($relativePath, '/\\');
            $fullPath = $this->projectDir . DIRECTORY_SEPARATOR . $relativePath;
            
            if (!file_exists($fullPath)) {
                $this->logger->warning("Loan offer file not found: {$fullPath}");
                continue;
            }
            
            $jsonContent = @file_get_contents($fullPath);
            $jsonContent = $this->preprocessJson($jsonContent);
            if ($jsonContent === false) {
                $this->logger->error("Failed to read loan offer file: {$fullPath}");
                continue;
            }
            
            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                $this->logger->error("Invalid JSON in loan offer file: {$fullPath}, error: " . json_last_error_msg());
                continue;
            }

            foreach ($data as $offer) {
                // Skip offers with missing or invalid data
                $amount = $offer['montant'] ?? $offer['montant_pret'] ?? $offer['amount'] ?? null;
                $duration = $offer['duree'] ?? $offer['duree_pret'] ?? $offer['duration'] ?? null;
                $rate = $offer['taux'] ?? $offer['taux_pret'] ?? $offer['rate'] ?? null;

                if ($amount === null || $duration === null || $rate === null ||
                    !is_numeric($amount) || !is_numeric($duration) || !is_numeric($rate)) {
                    $this->logger->warning("Skipping invalid loan offer from {$bank}: missing or non-numeric amount, duration, or rate.");
                    continue;
                }

                $offers[] = [
                    'bank' => $bank,
                    'amount' => (int)$amount, // Cast to ensure consistent types
                    'duration' => (int)$duration,
                    'rate' => (float)$rate,
                ];
            }
        }

        return $offers;
    }

    /**
     * Filters loan offers based on the specified amount and duration, sorted by rate in ascending order.
     *
     * @param int $amount The loan amount to filter by
     * @param int $duration The loan duration to filter by
     * @return array<string, mixed>[] Array of filtered and sorted loan offers
     */
    public function getFilteredOffers(int $amount, int $duration): array
    {
        $offers = $this->getNormalizedOffers();

        $filtered = array_filter($offers, fn($o) => $o['amount'] == $amount && $o['duration'] == $duration);

        usort($filtered, fn($a, $b) => $a['rate'] <=> $b['rate']);

        return array_values($filtered);
    }

    /**
     * Preprocesses JSON string to remove literal escape sequences, excessive whitespace, and other issues.
     *
     * @param string $jsonContent The raw JSON string
     * @return string The cleaned JSON string
     */
    private function preprocessJson(string $jsonContent): string
    {
        // Remove BOM (UTF-8 Byte Order Mark)
        $jsonContent = preg_replace('/^\xEF\xBB\xBF/', '', $jsonContent);

        // Remove literal escape sequences (e.g., "\t", "\n") outside of string values
        // Be careful to preserve escape sequences within JSON strings
        $jsonContent = preg_replace_callback(
            '/(?<!\\\\)(\\\\[tnr])/m',
            fn($matches) => '',
            $jsonContent
        );

        // Remove trailing commas in arrays/objects
        $jsonContent = preg_replace('/,\s*([\]}])/m', '$1', $jsonContent);

        // Normalize excessive whitespace (tabs, newlines, multiple spaces) outside strings
        $jsonContent = preg_replace_callback(
            '/("(?:[^"\\\\]|\\\\.)*")|\s+/m',
            fn($matches) => isset($matches[1]) ? $matches[1] : ' ',
            $jsonContent
        );

        // Trim leading/trailing whitespace
        $jsonContent = trim($jsonContent);

        return $jsonContent;
    }
}