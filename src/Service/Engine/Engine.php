<?php namespace App\Service\Engine;

use Exception;

use App\System\Log;
use App\System\LogFactory;
use App\Service\Scenario\ExpenseCollection;
use App\Service\Scenario\AssetCollection;
use App\Service\Scenario\EarningsCollection;

class Engine
{
    private string $expenseScenarioName;
    private string $assetScenarioName;
    private string $earningsScenarioName;

    private ExpenseCollection $expenseCollection;
    private AssetCollection $assetCollection;
    private EarningsCollection $earningsCollection;

    private array $plan;
    private array $audit;

    private Log $log;

    private Period $currentPeriod;
    private Money $annualIncome;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
    /**
     * Constructor
     * Optionally pass in asset and/or expense scenario names and this
     * method preps the engine for running a simulation and rendering
     * the results.
     */
    public function __construct(
        string $expenseScenarioName = 'base',
        string $assetScenarioName = null,
        string $earningsScenarioName = null)
    {
        // Instantiate global logger
        $this->log = LogFactory::getLogger();

        // Get scenario names
        $this->expenseScenarioName = $expenseScenarioName;
        $this->assetScenarioName = ($assetScenarioName === null) ? $expenseScenarioName : $assetScenarioName;
        $this->earningsScenarioName = ($earningsScenarioName === null) ? $expenseScenarioName : $earningsScenarioName;

        // Instantiate main classes
        $this->expenseCollection = new ExpenseCollection($this->log);
        $this->assetCollection = new AssetCollection($this->log);
        $this->earningsCollection = new EarningsCollection($this->log);

        $this->plan = [];
        $this->audit = [];

        $this->annualIncome = new Money();

        $this->audit = [
            'expense' => [],
            'asset' => [],
            'earnings' => [],
        ];
    }

    /**
     * Core function of the engine: to take all inputs and generate a plan
     * @throws Exception
     */
    public function run(int $periods, ?int $startYear = null, ?int $startMonth = null): bool
    {
        // Load scenarios
        // A "scenario" is an array of like items (an array of expenses, array of assets)
        $this->expenseCollection->loadScenario($this->expenseScenarioName);
        $this->assetCollection->loadScenario($this->assetScenarioName);
        $this->earningsCollection->loadScenario($this->earningsScenarioName);

        // Track period (year and month)
        $this->currentPeriod = $this->expenseCollection->getStart($startYear, $startMonth);

        $this->log->debug("Simulation parameters:");
        $this->log->debug(sprintf("  Expense scenario: %s", $this->expenseScenarioName));
        $this->log->debug(sprintf("  Asset scenario: %s", $this->assetScenarioName));
        $this->log->debug(sprintf("  Earnings scenario: %s", $this->earningsScenarioName));
        $this->log->debug(sprintf("  Duration: %d", $periods));
        $this->log->debug(sprintf("  Start Year: %d", $this->currentPeriod->getYear()));
        $this->log->debug(sprintf("  Start Month: %d", $this->currentPeriod->getMonth()));

        // Loop until the requested number of months have passed.
        while ($periods > 0) {

            $this->log->debug(sprintf("-- PERIOD: %d (%04d-%02d) --------------------------------------------- ",
                $this->currentPeriod->getCurrentPeriod(),
                $this->currentPeriod->getYear(),
                $this->currentPeriod->getMonth(),
            ));

            $this->appendToAudit();

            // Start by tallying all expenses for period
            // e.g., I have $2000 in expenses this month
            $expense = $this->getExpensesForPeriod();

            // Find earnings
            // e.g., I have a side job which brings in $250 per month
            $earnings = $this->getEarningsForPeriod();

            // If earnings doesn't cover it, pull from assets
            // e.g., I now need to pull $1750 from assets to cover expenses
            $shortfall = $this->adjustAssetForPeriod($expense, $earnings);

            // Deal with income taxes
            $this->payIncomeTax($expense);

            // Lastly record the plan
            $planEntry = [
                'period' => $this->currentPeriod->getCurrentPeriod(),
                'year' => $this->currentPeriod->getYear(),
                'month' => $this->currentPeriod->getMonth(),
                'expense' => $expense->value(),
                'expenses' => $this->expenseCollection->getAmounts(),
                'earnings' => $this->earningsCollection->getAmounts(),
                'assets' => $this->assetCollection->getBalances(),
                'shortfall' => $shortfall->value(),
            ];
            $this->plan[] = $planEntry;

            // Next period
            $this->currentPeriod->advance();
            $periods--;
        }

        return true;
    }

    public function getPlan(): array
    {
        return $this->plan;
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

    //------------------------------------------------------------------
    // Private functions
    //------------------------------------------------------------------

    private function appendToAudit()
    {
        $this->audit['expense'][] = $this->expenseCollection->auditExpenses($this->currentPeriod);
        $this->audit['asset'][]   = $this->assetCollection->auditAssets($this->currentPeriod);
        $this->audit['earnings'][]  = $this->earningsCollection->auditEarnings($this->currentPeriod);
    }

    /**
     * Get expenses for a given period
     * This is two passes:
     * 1) figure out the total expenses in the given period
     * 2) increasing balances to account for inflation
     */
    private function getExpensesForPeriod(): Money
    {
        $expenses = $this->expenseCollection->tallyExpenses($this->currentPeriod);
        $this->expenseCollection->applyInflation();
        return $expenses;
    }

    private function handleIncomeTax(Money $expense)
    {
        // Keep a running total for tax purposes
        $this->annualIncome->add($expense->value());
        $this->log->debug("Annual income in period {$this->currentPeriod->getCurrentPeriod()} is {$this->annualIncome->formatted()}");

        // If we're in the fourth period, calculate taxes
        // Note: this has issues. But it's good enough
        if ($this->currentPeriod->getCurrentPeriod() % 12 === 4) {
            if ($this->annualIncome->value() > 0.00) {
                $taxAmount = Util::calculateIncomeTax($this->annualIncome->value(), $this->currentPeriod->getYear());
                $expense->add($taxAmount);
                $effectiveTaxRate = ($taxAmount / $this->annualIncome->value()) * 100;
                $msg = sprintf("Paying income tax of %0.2f in period %d (effective tax rate: %0.1f%%)",
                    $taxAmount,
                    $this->currentPeriod->getCurrentPeriod(),
                    $effectiveTaxRate
                );
                $this->log->debug($msg);
                $this->annualIncome->assign(0.00);
            } else {
                $this->log->warn("Annual income was 0.00");
            }
        }

    }

    private function getEarningsForPeriod(Money $expense): Money
    {
        return $this->earningsCollection->tallyEarnings($this->currentPeriod);
    }

    /**
     * Adjust assets per period
     * This is two passes:
     * 1) reducing one or more balances per the $expense per period
     * 2) increasing all balances to account for interest earned
     */
    private function adjustAssetForPeriod(Money $expense, Money $earnings): Money
    {
        $remainingExpense = new Money();
        $remainingExpense->assign($expense->value());
        $remainingExpense->subtract($earnings->value());

        $total = $this->assetCollection->makeWithdrawals($this->currentPeriod, $remainingExpense);
        $this->assetCollection->earnInterest();

        if ($total->value() < $expense->value()) {
            // If we couldn't get enough assets to meet expenses, then
            // reduce expenses to meet the available assets
            $expense->assign($total->value());
        }

        return $remainingExpense;
    }

}
