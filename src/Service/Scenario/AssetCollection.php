<?php namespace App\Service\Scenario;

use Exception;

use App\Service\Engine\Asset;
use App\Service\Engine\Period;
use App\Service\Engine\Util;
use App\Service\Engine\Income;
use App\Service\Engine\IncomeCollection;

class AssetCollection extends Scenario
{
    private array $assets = [];

    /**
     * Load a scenario
     *
     * @param string $scenarioName
     * @throws Exception
     */
    public function loadScenario(string $scenarioName)
    {
        // Set scenario name
        $this->scenarioId = parent::fetchScenarioId($scenarioName, 2);
        $this->scenarioName = $scenarioName;
        $this->scenarioTable = 'asset';

        // Fetch data and validate
        $rows = parent::getRowsForScenario($scenarioName, 'asset', $this->fetchQuery());

        // Assign data to expenses
        $this->assets = $this->transform($rows);
    }

    /**
     * Return the number of loaded assets
     * @return int
     */
    public function count(): int
    {
        return count($this->assets);
    }

    /**
     * Return array of assets
     * @param Period $period
     * @return array
     */
    public function getAssets(Period $period): array
    {
        $filteredAssets = [];
        /** @var Asset $asset */
        foreach ($this->assets as $asset) {
            if ($asset->isIgnored($period)) {
                $ignoredAsset = new Asset();
                $ignoredAsset->setId($asset->id());
                $ignoredAsset->setName($asset->name());
                $ignoredAsset->setOpeningBalance(0);
                $ignoredAsset->setCurrentBalance(0);
                $ignoredAsset->setMaxWithdrawal(0);
                $ignoredAsset->setApr(0);
                $ignoredAsset->setIncomeType(0);
                $ignoredAsset->setBeginAfter(null);
                $ignoredAsset->setBeginYear(null);
                $ignoredAsset->setBeginMonth(null);
                $ignoredAsset->setIgnoreUntilYear($asset->ignoreUntilYear());
                $ignoredAsset->setIgnoreUntilMonth($asset->ignoreUntilMonth());
                $filteredAssets[] = $ignoredAsset;
            } else {
                $filteredAssets[] = $asset;
            }
        }
        return $filteredAssets;
    }

    public function auditAssets(Period $period): array
    {
        $audit = [];

        /** @var Asset $asset */
        foreach ($this->getAssets($period) as $asset) {
            $audit[] = [
                'period' => $period->getCurrentPeriod(),
                'year' => $period->getYear(),
                'month' => $period->getMonth(),
                'name' => $asset->name(),
                'opening_balance' => $asset->openingBalance(),
                'current_balance' => $asset->currentBalance(),
                'max_withdrawal' => $asset->maxWithdrawal(),
                'status' => $asset->status(),
            ];
        }

        return $audit;
    }

    /**
     * Withdraw money from fund(s) until expense is matched
     * @param Period $period
     * @param int $expense
     * @param IncomeCollection $incomeCollection
     * @return int
     */
    public function makeWithdrawals(Period $period, int $expense, IncomeCollection $incomeCollection): int
    {
        $total = 0;

        /** @var Asset $asset */
        foreach ($this->getAssets($period) as $asset) {

            if ($this->activateAsset($asset, $period)) {

                // Set withdrawal amount
                /** @var int $amount */
                $amount =
                    min(// The smallest of:
                    // The full expense pulled from the source (e.g., drawing $5,000 from a $50,000 source)
                    $expense,
                    // Unless a maximum withdrawal amount caps the above
                    $asset->maxWithdrawal(),
                    // Or the remaining balance in the asset covers it
                    $asset->currentBalance(),
                    // Lastly, if we just need enough to top off the expense
                    ($expense - $total),
                );

                if ($amount <= 0) {
                    $topOff = $expense - $total;
                    $this->getLog()->debug('Got a withdrawal amount of zero while pulling from asset "' . $asset->name() . '"');
                    $this->getLog()->debug('  Amount          = ' . Util::usd($amount));
                    $this->getLog()->debug('  Target Expense  = ' . Util::usd($expense));
                    $this->getLog()->debug('  Max Withdrawal  = ' . Util::usd($asset->maxWithdrawal()));
                    $this->getLog()->debug('  Current Balance = ' . Util::usd($asset->currentBalance()));
                    $this->getLog()->debug('  Top-Off amount  = ' . Util::usd($topOff));
                    $asset->markDepleted();
                } else {
                    $msg = sprintf('Pulling %s to meet %s from asset "%s" in %4d-%02d',
                        Util::usd($amount),
                        Util::usd($expense),
                        $asset->name(),
                        $period->getYear(),
                        $period->getMonth(),
                    );
                    $this->getLog()->debug($msg);
                    $total += $amount;

                    // Reduce balance by withdrawal amount
                    $asset->decreaseCurrentBalance($amount);
                }

                $income = new Income($asset->name(), $amount, $asset->incomeType());
                $incomeCollection->add($income);
                /*
                if ($asset->taxable()) {
                    $annualIncome->add($amount->value());
                    $this->getLog()->debug("Increasing annualIncome by amount: " . $amount->formatted());
                } else {
                    $this->getLog()->debug("annualIncome not increased due to asset being non-taxable");
                }
                */

                $msg = sprintf('Current balance of asset "%s" is %s',
                    $asset->name(),
                    Util::usd($asset->currentBalance()),
                );
                $this->getLog()->debug($msg);

                if ($total === $expense) {
                    // Just hack our way out of this
                    break;
                }
            }
        }

        if ($total < $expense) {
            $msg = sprintf('Insufficient funds in period %d (%4d-%02d); needed: %s vs found: %s',
                $period->getCurrentPeriod(),
                $period->getYear(),
                $period->getMonth(),
                Util::usd($expense),
                Util::usd($total),
            );
            $this->getLog()->warn($msg);
        }

        return $total;
    }

    /**
     * Special case
     */
    public function stashSurplus(Period $period, int $amount)
    {
        /** @var Asset $asset */
        foreach ($this->getAssets($period) as $asset) {
            if ($asset->name() === 'Checking Account') {
                $asset->increaseCurrentBalance($amount);
                return;
            }
        }
    }

    /**
     * Activate all activate-able assets for a period
     * @param Period $period
     * @return Asset[]
     */
    public function activateAssets(Period $period): array
    {
        /** @var Asset[] $assets */
        $assets = $this->getAssets($period);
        foreach ($assets as $asset) {
            $this->activateAsset($asset, $period);
        }
        return $assets;
    }

    /**
     * Activate an asset
     * @param Asset $asset
     * @param Period $period
     * @return bool
     */
    public function activateAsset(Asset $asset, Period $period): bool
    {
        $beginAfterAsset = $this->getBeginAfter($period, $asset->beginAfter());
        $asset->activate($period, $beginAfterAsset);
        return $asset->isActive();
    }

    /**
     * @param int|null $beginAfter
     * @return Asset|null
     */
    private function getBeginAfter(Period $period, ?int $beginAfter): ?Asset
    {
        foreach ($this->getAssets($period) as $asset) {
            if ($asset->id() === $beginAfter) {
                return $asset;
            }
        }
        return null;
    }

    /**
     * Loop through each asset and add interest
     */
    public function earnInterest(Period $period)
    {
        /** @var Asset $asset */
        foreach ($this->getAssets($period) as $asset) {
            if ($asset->canEarnInterest()) {
                $interest = Util::calculateInterest($asset->currentBalance(), $asset->apr());
                $asset->increaseCurrentBalance($interest);
            }
        }
    }

    public function getBalances(Period $period, bool $formatted = false): array
    {
        $assets = [];
        /** @var Asset $asset */
        foreach ($this->getAssets($period) as $asset) {
            $assets[$asset->name()] = $formatted ?
                Util::usd($asset->currentBalance()) :
                $asset->currentBalance();
        }
        return $assets;
    }

    private function fetchQuery(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/Query/asset-query.sql');
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
            $asset = new Asset();
            $asset
                ->setId($row['asset_id'])
                ->setName($row['asset_name'])
                ->setOpeningBalance($row['opening_balance'])
                ->setCurrentBalance($row['opening_balance'])
                ->setMaxWithdrawal($row['max_withdrawal'])
                ->setApr($row['apr'])
                ->setIncomeType($row['income_type_id'])
                ->setBeginAfter($row['begin_after'])
                ->setBeginYear($row['begin_year'])
                ->setBeginMonth($row['begin_month'])
                ->setIgnoreUntilYear($row['ignore_until_year'])
                ->setIgnoreUntilMonth($row['ignore_until_month'])
                ->markUntapped();
            $collection[] = $asset;
        }

        return $collection;
    }

}
