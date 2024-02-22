<?php namespace App\Service\Engine;

use Exception;

use App\Service\Scenario\ExpenseCollection;
use App\Service\Scenario\AssetCollection;
use App\Service\Scenario\EarningsCollection;
use App\System\Log;

class Engine
{
    private ExpenseCollection $expenseCollection;
    private AssetCollection $assetCollection;
    private EarningsCollection $earningsCollection;
    private IncomeCollection $incomeCollection;

    private Log $log;

    private Period $currentPeriod;
    private array $simulation;
    private array $audit;
    private array $summary;

    public function __construct(
        ExpenseCollection  $expenseCollection,
        AssetCollection    $assetCollection,
        EarningsCollection $earningsCollection,
        IncomeCollection   $incomeCollection,
        Log                $log,
    )
    {
        $this->expenseCollection = $expenseCollection;
        $this->assetCollection = $assetCollection;
        $this->earningsCollection = $earningsCollection;
        $this->incomeCollection = $incomeCollection;
        $this->log = $log;
        $this->summary = [
            'loop' => 0,
            'totalExpenses' => 0.00,
            'totalEarnings' => 0.00,
            'totalWithdrawals' => 0.00,
            'totalIncome' => 0.00,
            'totalIncomeTax' => 0.00,
        ];
    }

    /**
     * Core function of the engine: to take all inputs and generate a simulation
     * @throws Exception
     */
    public function run(SimulationParameters $simulationParameters): bool
    {
        // Initialize simulation
        $this->initialize();

        // Load scenarios
        $this->expenseCollection->loadScenario($simulationParameters->getExpense());
        $this->assetCollection->loadScenario($simulationParameters->getAsset());
        $this->earningsCollection->loadScenario($simulationParameters->getEarnings());

        if ($this->assetCollection->count() === 0 && $this->earningsCollection->count() === 0) {
            $msg = "No income sources found. Must specify assets, earnings or both.";
            $this->log->error($msg);
            throw new Exception($msg);
        }

        // Track period (year and month)
        $this->currentPeriod = $this->expenseCollection->getStart(
            $simulationParameters->getStartYear(),
            $simulationParameters->getStartMonth()
        );

        $this->log->debug("Simulation parameters:");
        $this->log->debug(sprintf("  Expense scenario: %s", $simulationParameters->getExpense()));
        $this->log->debug(sprintf("  Asset scenario: %s", $simulationParameters->getAsset()));
        $this->log->debug(sprintf("  Earnings scenario: %s", $simulationParameters->getEarnings()));
        $this->log->debug(sprintf("  Until: %s", $simulationParameters->getUntil()->toString()));
        $this->log->debug(sprintf("  Start Year: %d", $this->currentPeriod->getYear()));
        $this->log->debug(sprintf("  Start Month: %d", $this->currentPeriod->getMonth()));

        // Loop until we've satisfied our run time
        $shortfall = new Money();
        while ($simulationParameters->getUntil()->unsatisfied($this->currentPeriod, $shortfall)) {

            $this->log->debug(sprintf("-- PERIOD: %d (%04d-%02d) --------------------------------------------- ",
                $this->currentPeriod->getCurrentPeriod(),
                $this->currentPeriod->getYear(),
                $this->currentPeriod->getMonth(),
            ));

            // Activate expenses and earnings
            $this->expenseCollection->activateExpenses($this->currentPeriod);
            $this->earningsCollection->activateEarnings($this->currentPeriod);

            // Record start of month figures
            $step = [
                'period' => $this->currentPeriod->getCurrentPeriod(),
                'year' => $this->currentPeriod->getYear(),
                'month' => $this->currentPeriod->getMonth(),
                'expenses' => $this->expenseCollection->getAmounts(),
                'earnings' => $this->earningsCollection->getAmounts(),
                'assets' => $this->assetCollection->getBalances(),
            ];

            $this->appendToAudit();

            // Start by tallying all expenses for period
            // e.g., I have $2000 in expenses this month
            $expense = $this->getExpensesForPeriod();

            // Tally up earnings, if any
            // e.g., I have a side job which brings in $250 per month
            $earnings = $this->getEarningsForPeriod();

            // If earnings don't cover it, pull balance from assets
            // e.g., I now need to pull $1750 from assets to cover expenses
            $shortfall = $this->getShortfall($expense, $earnings);
            $withdrawals = $this->getAssetsForPeriod($shortfall);

            // Log income before (potentially) zeroed out
            $step['income'] = $this->incomeCollection->value();

            // Pay tax burden
            $incomeTax = $this->incomeCollection->payIncomeTax($this->currentPeriod, $simulationParameters->getTaxEngine());
            $step['incomeTax'] = $incomeTax;

            // Pull 100% of tax payments from assets (this was the BIG BUG discovered the weekend of 2/16/2024)
            $this->getAssetsForPeriod(new Money($incomeTax));

            // Calculate interest and inflation for everything
            $this->applyPeriodAdjustments();

            // Lastly amend the simulation with current step info
            $step['expense'] = $expense->value();
            $step['withdrawals'] = $withdrawals->value();
            $step['shortfall'] = $shortfall->value();
            $this->simulation[] = $step;

            // Update interesting information
            $this->summary['loop']++;
            $this->summary['totalExpenses'] += $expense->value();
            $this->summary['totalEarnings'] += $earnings->value();
            $this->summary['totalWithdrawals'] += $withdrawals->value();
            $this->summary['totalIncome'] += $step['income'];
            $this->summary['totalIncomeTax'] += $step['incomeTax'];
            $this->summary['hitShortfall'] = $shortfall->gt(0.00) ? 'true' : 'false';

            // Next period
            $this->currentPeriod->advance();
        }

        $this->summary['totalExpenses'] = round($this->summary['totalExpenses'], 2);
        $this->summary['totalEarnings'] = round($this->summary['totalEarnings'], 2);
        $this->summary['totalWithdrawals'] = round($this->summary['totalWithdrawals'], 2);
        $this->summary['totalIncome'] = round($this->summary['totalIncome'], 2);
        $this->summary['totalIncomeTax'] = round($this->summary['totalIncomeTax'], 2);
        $this->summary['lastYear'] = $this->currentPeriod->getYear();
        $this->summary['lastMonth'] = $this->currentPeriod->getMonth();
        $assetBalances = $this->assetCollection->getBalances(true);
        foreach ($assetBalances as $k => $v) {
            $this->summary['asset:'.$k] = $v;
        }

        return true;
    }

    public function getSimulation(): array
    {
        return $this->simulation;
    }

    public function getLogs(): array
    {
        return $this->log->getLogs();
    }

    public function getAudit(): string
    {
        $payload = '';

        foreach ($this->audit['expense'] as $thing) {
            foreach ($thing as $e) {
                $payload .= sprintf('%03d,%4d-%02d,"%s",%0.2f,%s' . "\n",
                    $e['period'], $e['year'], $e['month'],
                    $e['name'], $e['amount'], $e['status'],
                );
            }
        }

        foreach ($this->audit['asset'] as $thing) {
            foreach ($thing as $a) {
                $payload .= sprintf('%03d,%4d-%02d,"%s",%0.2f,%0.2f,%0.2f,%s' . "\n",
                    $a['period'], $a['year'], $a['month'],
                    $a['name'], $a['opening_balance'], $a['current_balance'], $a['max_withdrawal'], $a['status']);
            }
        }

        foreach ($this->audit['earnings'] as $thing) {
            foreach ($thing as $i) {
                $payload .= sprintf('%03d,%4d-%02d,"%s",%0.2f,%s' . "\n",
                    $i['period'], $i['year'], $i['month'],
                    $i['name'], $i['amount'], $i['status']);
            }
        }

        return $payload;
    }

    public function getSummary(): array
    {
        return $this->summary;
    }

    //------------------------------------------------------------------
    // Private functions
    //------------------------------------------------------------------

    private function initialize()
    {
        // Initialize simulation
        $this->simulation = [];

        // Initialize audit
        $this->audit = [
            'expense' => [],
            'asset' => [],
            'earnings' => [],
        ];
    }

    private function getShortfall(Money $expense, Money $earnings): Money
    {
        $shortfall = new Money();
        $shortfall->assign($expense->value());
        $shortfall->subtract($earnings->value());
        return $shortfall;
    }

    private function appendToAudit()
    {
        $this->audit['expense'][] = $this->expenseCollection->auditExpenses($this->currentPeriod);
        $this->audit['asset'][] = $this->assetCollection->auditAssets($this->currentPeriod);
        $this->audit['earnings'][] = $this->earningsCollection->auditEarnings($this->currentPeriod);
    }

    /**
     * Get expenses for a given period
     * This is two passes:
     * 1) figure out the total expenses in the given period
     * 2) increasing balances to account for inflation
     */
    private function getExpensesForPeriod(): Money
    {
        return $this->expenseCollection->tallyExpenses($this->currentPeriod);
    }

    private function getEarningsForPeriod(): Money
    {
        return $this->earningsCollection->tallyEarnings($this->currentPeriod, $this->incomeCollection);
    }

    /**
     * Adjust assets per period
     * This is two passes:
     * 1) reducing one or more balances per the $expense per period
     * 2) increasing all balances to account for interest earned
     *
     * @throws Exception
     */
    private function getAssetsForPeriod(Money $amount): Money
    {
        if ($amount->eq(0.00)) {
            // Stop here and return: we hit breakeven
            return new Money();
        }

        if ($amount->lt(0.00)) {
            // But if it's less, then we need to make a deposit
            $this->assetCollection->stashSurplus($amount);
            return new Money();
        }

        // Make withdrawals
        $withdrawals = $this->assetCollection->makeWithdrawals(
            $this->currentPeriod,
            $amount,
            $this->incomeCollection,
        );

        // If we covered our amount, zero it out
        if ($withdrawals->ge($amount->value())) {
            $amount->assign(0.00);
        }

        return $withdrawals;
    }

    private function applyPeriodAdjustments()
    {
        // Expenses go up (or, rarely, down)
        $this->expenseCollection->applyInflation();

        // Earnings also go up (hopefully!)
        $this->earningsCollection->applyInflation();

        // Assets gain value at the end of each period
        $this->assetCollection->earnInterest();
    }


}
