<?php

namespace App\Tests\Service;

use App\Service\LoanOfferService;
use PHPUnit\Framework\TestCase;

class LoanOfferServiceTest extends TestCase
{
    private LoanOfferService $service;

    protected function setUp(): void
    {
        $projectDir = realpath(__DIR__ . '/../../');
        $this->service = new LoanOfferService($projectDir);
    }

    // This test checks if the service can retrieve and normalize offers from the JSON files.
    public function testItReturnsOffers()
    {
        $offers = $this->service->getNormalizedOffers();

        $this->assertNotEmpty($offers);
        $this->assertArrayHasKey('amount', $offers[0]);
        $this->assertArrayHasKey('duration', $offers[0]);
        $this->assertArrayHasKey('rate', $offers[0]);
        $this->assertArrayHasKey('bank', $offers[0]);
    }

    // This test checks if the service can filter offers based on amount and duration,
    public function testItFiltersAndSortsOffers()
    {
        $filtered = $this->service->getFilteredOffers(50000, 15);

        $this->assertNotEmpty($filtered);
        $this->assertEquals(50000, $filtered[0]['amount']);
        $this->assertEquals(15, $filtered[0]['duration']);

        for ($i = 0; $i < count($filtered) - 1; $i++) {
            $this->assertLessThanOrEqual($filtered[$i + 1]['rate'], $filtered[$i]['rate']);
        }
    }
}
