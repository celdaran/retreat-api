<?php namespace App\Service\Scenario;

use Exception;

use App\Service\Engine\Expense;
use App\Service\Engine\Money;
use App\Service\Engine\Period;
use App\Service\Engine\Util;

class ExpenseCollection extends Scenario
{
    private array $expenses = [];

    /**
     * Load a scenario
     *
     * @param string $scenarioName
     * @throws Exception
     */
    public function loadScenario(string $scenarioName)
    {
        // Set scenario name
        $this->scenarioId = parent::fetchScenarioId($scenarioName, 1);
        $this->scenarioName = $scenarioName;
        $this->scenarioTable = 'expense';

        // Fetch data and validate
        $rows = parent::getRowsForScenario($scenarioName, 'expense', $this->fetchQuery());
        if (count($rows) === 0) {
            $this->getLog()->error("No expenses found for scenario $scenarioName");
            throw new Exception("No expenses found for scenario $scenarioName");
        }

        // Assign data to expenses
        $this->expenses = $this->transform($rows);
    }

    /**
     * Return the number of loaded expenses
     * @return int
     */
    public function count(): int
    {
        return count($this->expenses);
    }

    /**
     * Return array of expenses
     * @return array
     */
    public function getExpenses(): array
    {
        return $this->expenses;
    }

    public function auditExpenses(Period $period): array
    {
        $audit = [];

        /** @var Expense $expense */
        foreach ($this->expenses as $expense) {
            $audit[] = [
                'period' => $period->getCurrentPeriod(),
                'year' => $period->getYear(),
                'month' => $period->getMonth(),
                'name' => $expense->name(),
                'amount' => $expense->amount()->value(),
                'status' => $expense->status(),
            ];
        }

        return $audit;
    }

    public function activateExpenses(Period $period)
    {
        // Activate expenses based on period
        /** @var Expense $expense */
        foreach ($this->expenses as $expense) {
            // If we hit a planned expense, see if it's time to activate it
            if ($expense->timeToActivate($period)) {
                $msg = sprintf('Activating expense "%s" in year %4d-%02d, as planned from the start',
                    $expense->name(),
                    $period->getYear(),
                    $period->getMonth(),
                );
                $this->getLog()->debug($msg);
                $expense->markActive();
            }
        }
    }

    public function tallyExpenses(Period $period): Money
    {
        // First, get amounts, drawing from every participating expense
        $total = new Money();
        foreach ($this->expenses as $expense) {
            if ($expense->isActive()) {
                $msg = sprintf('Adding expense "%s", amount %s to period %s tally',
                    $expense->name(),
                    $expense->amount()->formatted(),
                    $period->getCurrentPeriod(),
                );
                $this->getLog()->debug($msg);
                $total->add($expense->amount()->value());
            }
        }

        // Second, has it ended?
        /** @var Expense $expense */
        foreach ($this->expenses as $expense) {
            $action = $expense->timeToEnd($period);
            switch ($action) {
                case 'yep':
                    $msg = sprintf('Ending expense "%s" in %4d-%02d, as planned from the start',
                        $expense->name(),
                        $period->getYear(),
                        $period->getMonth(),
                    );
                    $this->getLog()->debug($msg);
                    $expense->markEnded();
                    break;
                case 'nope':
                    break;
                case 'reschedule':
                    $msg = sprintf('Ending expense "%s" in %4d-%02d, but rescheduling %s months out',
                        $expense->name(),
                        $period->getYear(),
                        $period->getMonth(),
                        $expense->repeatEvery(),
                    );
                    $this->getLog()->debug($msg);
                    $nextPeriod = $period->addMonths(
                        $expense->beginYear(), $expense->beginMonth(),
                        $expense->repeatEvery());
                    $expense->markPlanned();
                    $expense->setBeginYear($nextPeriod->getYear());
                    $expense->setBeginMonth($nextPeriod->getMonth());
                    break;
            }
        }

        return $total;
    }

    public function getAmounts(bool $formatted = false): array
    {
        $amounts = [];
        /** @var Expense $expense */
        foreach ($this->expenses as $expense) {
            if ($expense->isActive()) {
                $amounts[$expense->name()] = $formatted ?
                    $expense->amount()->formatted() :
                    $expense->amount()->value();
            } else {
                $amounts[$expense->name()] = $formatted ?
                    'Inactive' :
                    null;
            }
        }
        return $amounts;
    }

    public function applyInflation()
    {
        /** @var Expense $expense */
        foreach ($this->expenses as $expense) {
            $interest = Util::calculateInterest($expense->amount()->value(), $expense->inflationRate());
            $expense->increaseAmount($interest->value());
        }
    }

    /**
     * Fetch initial period from database based on year and month
     *
     * @param int|null $startYear
     * @param int|null $startMonth
     * @return Period
     */
    public function getStart(?int $startYear, ?int $startMonth): Period
    {
        if ($startYear === null) {
            $sql = "
                SELECT min(e.begin_year) AS startYear 
                FROM expense e
                JOIN scenario s ON s.scenario_id = e.scenario_id
                WHERE s.scenario_name = :scenario_name";
            $rows = $this->getData()->select($sql, ['scenario_name' => $this->scenarioName]);
            $startYear = $rows[0]['startYear'];
        }

        if ($startMonth === null) {
            $sql = "
                SELECT min(e.begin_month) AS startMonth
                FROM expense e
                JOIN scenario s ON s.scenario_id = e.scenario_id
                WHERE s.scenario_name = :scenario_name
                  AND e.begin_year = :begin_year
            ";
            $rows = $this->getData()->select($sql,
                ['scenario_name' => $this->scenarioName, 'begin_year' => $startYear]);
            $startMonth = $rows[0]['startMonth'];
        }

        return new Period($startYear, $startMonth);
    }


    /**
     * Return SQL required to fetch a scenario from the database
     *
     * @return string
     */
    private function fetchQuery(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/Query/expense-query.sql');
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
            $expense = new Expense();
            $expense
                ->setName($row['expense_name'])
                ->setAmount(new Money((float)$row['amount']))
                ->setInflationRate($row['inflation_rate'])
                ->setBeginYear($row['begin_year'])
                ->setBeginMonth($row['begin_month'])
                ->setEndYear($row['end_year'])
                ->setEndMonth($row['end_month'])
                ->setRepeatEvery($row['repeat_every'])
                ->markPlanned();
            $collection[] = $expense;
        }

        return $collection;
    }

}
