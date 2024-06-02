<?php

use PHPUnit\Framework\TestCase;

use App\Service\Engine\Asset;
use App\Service\Engine\Period;

final class assetClassTest extends TestCase
{
    public function testCanEarnInterest1(): void
    {
        $asset = new Asset();
        $asset->setCurrentBalance(100);
        $asset->markActive();
        $canEarnInterest = $asset->canEarnInterest();
        $this->assertEquals(true, $canEarnInterest);

        $asset->decreaseCurrentBalance(10);
        $canEarnInterest = $asset->canEarnInterest();
        $this->assertEquals(true, $canEarnInterest);

        $asset->decreaseCurrentBalance(90);
        $canEarnInterest = $asset->canEarnInterest();
        $this->assertEquals(false, $canEarnInterest);
    }

    public function testCanEarnInterest2(): void
    {
        $asset = new Asset();
        $asset->setCurrentBalance(100);
        $asset->markActive();
        $canEarnInterest = $asset->canEarnInterest();
        $this->assertEquals(true, $canEarnInterest);

        $asset->markDepleted();
        $canEarnInterest = $asset->canEarnInterest();
        $this->assertEquals(false, $canEarnInterest);
    }

    public function testTimeToActivate(): void
    {
        $asset = new Asset();
        $asset->setBeginYear(2026);
        $asset->setBeginMonth(7);
        $asset->markActive();
        $period = new Period(2025, 1);
        $timeToActivate = $asset->timeToActivate($period);
        $this->assertEquals(false, $timeToActivate);

        $period = new Period(2027, 1);
        $timeToActivate = $asset->timeToActivate($period);
        $this->assertEquals(false, $timeToActivate);

        $asset->markUntapped();
        $timeToActivate = $asset->timeToActivate($period);
        $this->assertEquals(true, $timeToActivate);
    }


}
