<?php namespace App\Service\Scenario;

use App\Service\Engine\IncomeCollection;
use Exception;

use App\Service\Engine\Earnings;
use App\Service\Engine\Period;
use App\Service\Engine\Util;
use App\Service\Engine\Income;

class EarningsCollection extends Scenario
{
    private array $earnings = [];

    /**
     * Load a scenario
     *
     * @param string $scenarioName
     * @throws Exception
     */
    public function loadScenario(string $scenarioName)
    {
        // Set scenario name
        $this->scenarioId = parent::fetchScenarioId($scenarioName, 3);
        $this->scenarioName = $scenarioName;
        $this->scenarioTable = 'earnings';

        // Fetch data and validate
        $rows = parent::getRowsForScenario($scenarioName, 'earnings', $this->fetchQuery());

        // Assign data to expenses
        $this->earnings = $this->transform($rows);
    }

    /**
     * Return the number of loaded expenses
     * @return int
     */
    public function count(): int
    {
        return count($this->earnings);
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
                'amount' => $earnings->amount(),
                'status' => $earnings->status(),
            ];
        }

        return $audit;
    }

    public function activateEarnings(Period $period)
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

    }

    public function tallyEarnings(Period $period, IncomeCollection $incomeCollection): int
    {
        // Initialize total earnings
        $total = 0;

        // First, get amounts, drawing from every participating earnings
        /** @var Earnings $earnings */
        foreach ($this->earnings as $earnings) {

            // If it's active, then process
            if ($earnings->isActive()) {

                // Add to the running total
                $total += $earnings->amount();

                // Log it
                $msg = sprintf('Adding earnings "%s", amount %s to period %s tally',
                    $earnings->name(),
                    Util::usd($earnings->amount()),
                    $period->getCurrentPeriod(),
                );
                $this->getLog()->debug($msg);

                // Add to income collection
                $income = new Income($earnings->name(), $earnings->amount(), $earnings->incomeType());

                // $this->log->debug("Increasing annualIncome by amount: " . $earnings->formatted());
                $incomeCollection->add($income);

            }
        }

        // Second, has it ended?
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
                    $nextPeriod = $period->addMonths($earnings->beginYear(), $earnings->beginMonth(),
                        $earnings->repeatEvery());
                    $earnings->markPlanned();
                    $earnings->setBeginYear($nextPeriod->getYear());
                    $earnings->setBeginMonth($nextPeriod->getMonth());
                    break;
            }
        }

        return $total;
    }

    public function getAmounts(bool $formatted = false): array
    {
        $amounts = [];
        /** @var Earnings $earnings */
        foreach ($this->earnings as $earnings) {
            if ($earnings->isActive()) {
                $amounts[$earnings->name()] = $formatted ?
                    Util::usd($earnings->amount()) :
                    $earnings->amount();
            } else {
                $amounts[$earnings->name()] = $formatted ?
                    'Inactive' :
                    null;
            }
        }
        return $amounts;
    }

    public function applyInflation()
    {
        /** @var Earnings $earnings */
        foreach ($this->earnings as $earnings) {
            $interest = Util::calculateInterest($earnings->amount(), $earnings->inflationRate());
            $earnings->increaseAmount($interest);
        }
    }

    /**
     * Return SQL required to fetch a scenario from the database
     *
     * @return string
     */
    private function fetchQuery(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/Query/earnings-query.sql');
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
                ->setAmount($row['amount'])
                ->setInflationRate($row['inflation_rate'])
                ->setIncomeType($row['income_type_id'])
                ->setBeginYear($row['begin_year'])
                ->setBeginMonth($row['begin_month'])
                ->setEndYear($row['end_year'])
                ->setEndMonth($row['end_month'])
                ->setRepeatEvery($row['repeat_every'])
                ->markPlanned();
            $collection[] = $earnings;
        }

        return $collection;
    }

}
