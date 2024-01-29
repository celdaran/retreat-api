<?php namespace App\Service\Scenario;

use App\Service\Engine\Earnings;
use App\Service\Engine\Money;
use App\Service\Engine\Period;
use App\Service\Engine\Util;

class EarningsCollection extends Scenario
{
    private string $scenarioName;
    private array $earnings = [];

    /**
     * Load a scenario
     *
     * @param string $scenarioName
     */
    public function loadScenario(string $scenarioName)
    {
        $this->scenarioName = $scenarioName;
         $rows = parent::getRowsForScenario($scenarioName, 'earnings', $this->fetchQuery());
         $this->earnings = $this->transform($rows);
    }

    /**
     * Primarily for unit testing
     * @param string $scenarioName
     * @param array $scenarios
     */
    public function loadScenarioFromMemory(string $scenarioName, array $scenarios)
    {
        $this->scenarioName = $scenarioName;
        $rows = $scenarios[$scenarioName];
        $this->earnings = $this->transform($rows);
    }

    public function getEarnings(): array
    {
        return $this->earnings;
    }

    public function auditEarnings(Period $period): array
    {
        $audit = [];

        /** @var Earnings $earnings */
        foreach ($this->earnings as $earnings) {
            $audit[] = [
                'period' => $period->getCurrentPeriod(),
                'year' => $period->getYear(),
                'month' => $period->getMonth(),
                'name' => $earnings->name(),
                'amount' => $earnings->amount()->value(),
                'status' => $earnings->status(),
            ];
        }

        return $audit;
    }

    public function tallyEarnings(Period $period): Money
    {
        // Activate earnings based on period
        /** @var Earnings $earnings */
        foreach ($this->earnings as $earnings) {
            // If we hit a planned earnings, see if it's time to activate it
            if ($earnings->timeToActivate($period)) {
                $msg = sprintf('Activating earnings "%s" in year %4d-%02d, as planned from the start',
                    $earnings->name(),
                    $period->getYear(),
                    $period->getMonth(),
                );
                $this->getLog()->debug($msg);
                $earnings->markActive();
            }
        }

        // Now get amounts, drawing from every participating earnings
        $total = new Money();
        foreach ($this->earnings as $earnings) {
            if ($earnings->isActive()) {
                $msg = sprintf('Adding earnings "%s", amount %s to period %s tally',
                    $earnings->name(),
                    $earnings->amount()->formatted(),
                    $period->getCurrentPeriod(),
                );
                $this->getLog()->debug($msg);
                $total->add($earnings->amount()->value());
            }
        }

        // Lastly, has it ended?
        foreach ($this->earnings as $earnings) {
            $action = $earnings->timeToEnd($period);
            switch ($action) {
                case 'yep':
                    $msg = sprintf('Ending earnings "%s" in %4d-02%d, as planned from the start',
                        $earnings->name(),
                        $period->getYear(),
                        $period->getMonth(),
                    );
                    $this->getLog()->debug($msg);
                    $earnings->markEnded();
                    break;
                case 'nope':
                    break;
                    case 'reschedule';
                        $msg = sprintf('Ending earnings "%s" in %4d-%02d, but rescheduling %s months out',
                            $earnings->name(),
                            $period->getYear(),
                            $period->getMonth(),
                            $earnings->repeatEvery(),
                        );
                        $this->getLog()->debug($msg);
                        $nextPeriod = $period->addMonths($earnings->beginYear(), $earnings->beginMonth(), $earnings->repeatEvery());
                        $earnings->markPlanned();
                        $earnings->setBeginYear($nextPeriod->getYear());
                        $earnings->setBeginMonth($nextPeriod->getMonth());
                    break;
            }
        }

        return $total;
    }

    public function applyInflation()
    {
        /** @var Earnings $earnings */
        foreach ($this->earnings as $earnings) {
            $interest = Util::calculateInterest($earnings->amount()->value(), $earnings->inflationRate());
            $earnings->increaseAmount($interest);
        }
    }

    public function getAmounts(bool $formatted = false): array
    {
        $amounts = [];
        /** @var Earnings $earnings */
        foreach ($this->earnings as $earnings) {
            if ($earnings->isActive()) {
                $amounts[$earnings->name()] = $formatted ?
                    $earnings->amount()->formatted() :
                    $earnings->amount()->value();
            } else {
                $amounts[$earnings->name()] = $formatted ?
                    'Inactive' :
                    null;
            }
        }
        return $amounts;
    }

    /**
     * Return SQL required to fetch a scenario from the database
     *
     * @return string
     */
    private function fetchQuery(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/SQL/earnings-query.sql');
    }

    /**
     * Transform fetched-rows into an array of objects
     *
     * @param array $rows
     * @return array
     */
    private function transform(array $rows): array
    {
        $collection = [];

        foreach ($rows as $row) {
            $earnings = new Earnings();
            $earnings
                ->setName($row['earnings_name'])
                ->setAmount(new Money((float)$row['amount']))
                ->setInflationRate($row['inflation_rate'])
                ->setBeginYear($row['begin_year'])
                ->setBeginMonth($row['begin_month'])
                ->setEndYear($row['end_year'])
                ->setEndMonth($row['end_month'])
                ->setRepeatEvery($row['repeat_every'])
                ->markPlanned()
            ;
            $collection[] = $earnings;
        }

        return $collection;
    }

}
