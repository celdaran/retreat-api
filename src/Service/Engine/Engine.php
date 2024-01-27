<?php namespace App\Service\Engine;

use App\Service\Data\ExpenseCollection;
use App\Service\Data\AssetCollection;
use App\Service\Data\IncomeCollection;
use App\Service\Log;

class Engine
{
    private string $expenseScenarioName;
    private string $assetScenarioName;
    private string $incomeScenarioName;
    private float $taxRate;

    private ExpenseCollection $expenseCollection;
    private AssetCollection $assetCollection;
    private IncomeCollection $incomeCollection;

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
        string $incomeScenarioName = null,
        float $taxRate = null)
    {
        // Get scenario names
        $this->expenseScenarioName = $expenseScenarioName;
        $this->assetScenarioName = ($assetScenarioName === null) ? $expenseScenarioName : $assetScenarioName;
        $this->incomeScenarioName = ($incomeScenarioName === null) ? $expenseScenarioName : $incomeScenarioName;
        $this->taxRate = ($taxRate === null) ? $_ENV['TAX_RATE'] : $taxRate;

        // Instantiate main classes
        $this->expenseCollection = new ExpenseCollection();
        $this->assetCollection = new AssetCollection();
        $this->incomeCollection = new IncomeCollection();

        $this->plan = [];
        $this->audit = [];

        $this->log = new Log();
        $this->log->setLevel($_ENV['LOG_LEVEL']);

        $this->annualIncome = new Money();

        $this->audit = [
            'expense' => [],
            'asset' => [],
            'income' => [],
        ];
    }

    /**
     * Core function of the engine: to take all inputs and generate a plan
     */
    public function run(int $periods, ?int $startYear = null, ?int $startMonth = null): bool
    {
        // Load scenarios
        // A "scenario" is an array of like items (an array of expenses, array of assets)
        $this->expenseCollection->loadScenario($this->expenseScenarioName);
        $this->assetCollection->loadScenario($this->assetScenarioName);
        $this->incomeCollection->loadScenario($this->incomeScenarioName);

        // Track period (year and month)
        $this->currentPeriod = $this->expenseCollection->getStart($startYear, $startMonth);

        $this->log->debug("Simulation parameters:");
        $this->log->debug(sprintf("  Expense scenario: %s", $this->expenseScenarioName));
        $this->log->debug(sprintf("  Asset scenario: %s", $this->assetScenarioName));
        $this->log->debug(sprintf("  Income scenario: %s", $this->incomeScenarioName));
        $this->log->debug(sprintf("  Income Tax Rate: %.3f", $this->taxRate));
        $this->log->debug(sprintf("  Duration: %d", $periods));
        $this->log->debug(sprintf("  Start Year: %d", $this->currentPeriod->getYear()));
        $this->log->debug(sprintf("  Start Month: %d", $this->currentPeriod->getMonth()));

        // Loop until the requested number of months have passed.
        while ($periods > 0) {

            $this->appendToAudit();

            // Start by tallying all expenses for period
            $expense = $this->getExpensesForPeriod();

            // Deal with income taxes
            $this->handleIncomeTax($expense);

            // Find income to cover expenses
            $income = $this->getIncomeForPeriod($expense);

            // Then pull from assets to cover any remaining expenses
            $remainingExpense = $this->adjustAssetForPeriod($expense, $income);

            // Lastly record the plan
            $planEntry = [
                'period' => $this->currentPeriod->getCurrentPeriod(),
                'year' => $this->currentPeriod->getYear(),
                'month' => $this->currentPeriod->getMonth(),
                'expense' => $expense,
                'income' => $this->incomeCollection->getAmounts(),
                'net_expense' => $remainingExpense,
                'assets' => $this->assetCollection->getBalances(),
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

    public function render($format = 'csv') : array
    {
        $payload = [];
        switch ($format) {
            case 'csv':
                $payload[] = sprintf('%s,%s,%s,,', 'period', 'month', 'expense');
                $i = 0;
                foreach ($this->plan as $p) {
                    // Header
                    if ($i === 0) {
                        $payload[] = $this->renderHeader($p);
                    }
        
                    // Body
                    $this->renderLine($p);
                    $i++;
                }
            return $payload;

            case 'json':
                $payload = [];
            return $payload;

        }
    }

    public function renderHeader(array $p): string
    {
        $payload = '';
        if (count($p['income']) > 0) {
            foreach (array_keys($p['income']) as $incomeName) {
                $payload .= sprintf('"%s",', addslashes($incomeName));
            }
        }
        $payload .= sprintf('"total income",');
        $paylaod .= sprintf('"net expense",,');
        if (count($p['assets']) > 0) {
            foreach (array_keys($p['assets']) as $assetName) {
                $payload .= sprintf('"%s",', addslashes($assetName));
            }
        }
        $payload .= sprintf('"total assets"' . "\n");
        return $payload;
    }

    public function renderLine(array $p): string
    {
        $payload = '';
        $totalIncome = 0.00;
        $totalAssets = 0.00;
        $payload .= sprintf('%03d,%4d-%02d,%.2f,,', $p['period'], $p['year'], $p['month'], $p['expense']->value());
        foreach ($p['income'] as $income) {
            $payload .= sprintf('%.2f,', $income);
            $totalIncome += $income;
        }
        $payload .= sprintf('%.2f,', $totalIncome);
        $payload .= sprintf('%.2f,,', $p['net_expense']->value());
        foreach ($p['assets'] as $asset) {
            $payload .= sprintf('%.2f,', $asset);
            $totalAssets += $asset;
        }
        $payload .= sprintf($totalAssets . "\n");
    }

    public function report(): string
    {
        $payload = '';
        $total = new Money();

        /** @var Asset $asset */
        foreach ($this->assetCollection->getAssets() as $asset) {
            $total->add($asset->currentBalance()->value());
            $payload .= sprintf("Asset: %s\n", $asset->name());
            $payload .= sprintf("  Current balance: %s\n", $asset->currentBalance()->formatted());
            $payload .= sprintf("\n");
        }

        $payload .= printf("Total assets: %s\n", $total->formatted());
        return $payload;
    }

    public function audit(): string
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

        foreach ($this->audit['income'] as $thing) {
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

//    private function adjustScenario(array &$scenario, int $startYear, int $startMonth)
//    {
//        for ($i = 0; $i < count($scenario); $i++) {
//            if ($scenario[$i]['fixed_period'] !== 1) {
//                // If it's not fixed, then it's subject to adjustment
//                $scenario[$i]['begin_year'] = $startYear;
//                $scenario[$i]['begin_month'] = $startMonth;
//            }
//        }
//    }

    private function appendToAudit()
    {
        $this->audit['expense'][] = $this->expenseCollection->auditExpenses($this->currentPeriod);
        $this->audit['asset'][]   = $this->assetCollection->auditAssets($this->currentPeriod);
        $this->audit['income'][]  = $this->incomeCollection->auditIncome($this->currentPeriod);
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
            $taxAmount = $this->annualIncome->value() * $this->taxRate;
            $expense->add($taxAmount);
            $this->log->debug("Paying income tax of $taxAmount in period {$this->currentPeriod->getCurrentPeriod()}");
            $this->annualIncome->assign(0.00);
        }

    }

    private function getIncomeForPeriod(Money $expense): Money
    {
        return $this->incomeCollection->tallyIncome($this->currentPeriod);
    }

    /**
     * Adjust assets per period
     * This is two passes:
     * 1) reducing one or more balances per the $expense per period
     * 2) increasing all balances to account for interest earned
     */
    private function adjustAssetForPeriod(Money $expense, Money $income): Money
    {
        $remainingExpense = new Money();
        $remainingExpense->assign($expense->value());
        $remainingExpense->subtract($income->value());

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
