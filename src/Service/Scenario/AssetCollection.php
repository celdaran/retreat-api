<?php namespace App\Service\Scenario;

use Exception;

use App\Service\Engine\Asset;
use App\Service\Engine\Money;
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
     * @return array
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    public function auditAssets(Period $period): array
    {
        $audit = [];

        /** @var Asset $asset */
        foreach ($this->assets as $asset) {
            $audit[] = [
                'period' => $period->getCurrentPeriod(),
                'year' => $period->getYear(),
                'month' => $period->getMonth(),
                'name' => $asset->name(),
                'opening_balance' => $asset->openingBalance()->value(),
                'current_balance' => $asset->currentBalance()->value(),
                'max_withdrawal' => $asset->maxWithdrawal()->value(),
                'status' => $asset->status(),
            ];
        }

        return $audit;
    }

    /**
     * Withdraw money from fund(s) until expense is matched
     * @param Period $period
     * @param Money $expense
     * @param IncomeCollection $incomeCollection
     * @return Money
     */
    public function makeWithdrawals(Period $period, Money $expense, IncomeCollection $incomeCollection): Money
    {
        $total = new Money();

        /** @var Asset $asset */
        foreach ($this->assets as $asset) {

            if ($this->activateAsset($asset, $period)) {

                // Set withdrawal amount
                $amount = new Money();
                $amount->assign(
                    min(// The smallest of:
                    // The full expense pulled from the source (e.g., drawing $5,000 from a $50,000 source)
                        $expense->value(),
                        // Unless a maximum withdrawal amount caps the above
                        $asset->maxWithdrawal()->value(),
                        // Or the remaining balance in the asset covers it
                        $asset->currentBalance()->value(),
                        // Lastly, if we just need enough to top off the expense
                        ($expense->value() - $total->value()),
                    )
                );

                if ($amount->le(0.00)) {
                    $topOff = new Money();
                    $topOff->assign($expense->value() - $total->value());
                    $this->getLog()->debug('Got a withdrawal amount of zero while pulling from asset "' . $asset->name() . '"');
                    $this->getLog()->debug('  Amount          = ' . $amount->formatted());
                    $this->getLog()->debug('  Target Expense  = ' . $expense->formatted());
                    $this->getLog()->debug('  Max Withdrawal  = ' . $asset->maxWithdrawal()->formatted());
                    $this->getLog()->debug('  Current Balance = ' . $asset->currentBalance()->formatted());
                    $this->getLog()->debug('  Top-Off amount  = ' . $topOff->formatted());
                    $asset->markDepleted();
                } else {
                    $msg = sprintf('Pulling %s to meet %s from asset "%s" in %4d-%02d',
                        $amount->formatted(),
                        $expense->formatted(),
                        $asset->name(),
                        $period->getYear(),
                        $period->getMonth(),
                    );
                    $this->getLog()->debug($msg);
                    $total->add($amount->value());

                    // Reduce balance by withdrawal amount
                    $asset->decreaseCurrentBalance($amount->value());
                }

                $income = new Income($asset->name(), $amount->value(), $asset->incomeType());
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
                    $asset->currentBalance()->formatted(),
                );
                $this->getLog()->debug($msg);

                if ($total->value() === $expense->value()) {
                    // Just hack our way out of this
                    break;
                }
            }
        }

        if ($total->value() < $expense->value()) {
            $msg = sprintf('Insufficient funds in period %d (%4d-%02d); needed: %s vs found: %s',
                $period->getCurrentPeriod(),
                $period->getYear(),
                $period->getMonth(),
                $expense->formatted(),
                $total->formatted(),
            );
            $this->getLog()->warn($msg);
        }

        return $total;
    }

    /**
     * Special case
     */
    public function stashSurplus(Money $amount)
    {
        /** @var Asset $asset */
        foreach ($this->assets as $asset) {
            if ($asset->name() === 'Checking Account') {
                $asset->increaseCurrentBalance($amount->value(true));
                return;
            }
        }
    }

    /**
     * Activate all activatable assets for a period
     * @param Period $period
     * @return Asset[]
     */
    public function activateAssets(Period $period): array
    {
        /** @var Asset[] $assets */
        $assets = $this->getAssets();
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
        $beginAfterAsset = $this->getBeginAfter($asset->beginAfter());
        $asset->activate($period, $beginAfterAsset);
        return $asset->isActive();
    }

    /**
     * @param int|null $beginAfter
     * @return Asset|null
     */
    private function getBeginAfter(?int $beginAfter): ?Asset
    {
        foreach ($this->assets as $asset) {
            if ($asset->id() === $beginAfter) {
                return $asset;
            }
        }
        return null;
    }

    /**
     * Loop through each asset and add interest
     */
    public function earnInterest()
    {
        /** @var Asset $asset */
        foreach ($this->assets as $asset) {
            if ($asset->canEarnInterest()) {
                $interest = Util::calculateInterest($asset->currentBalance()->value(), $asset->apr());
                $asset->increaseCurrentBalance($interest->value());
            }
        }
    }

    public function getBalances(bool $formatted = false): array
    {
        $assets = [];
        /** @var Asset $asset */
        foreach ($this->assets as $asset) {
            $assets[$asset->name()] = $formatted ?
                $asset->currentBalance()->formatted() :
                $asset->currentBalance()->value();
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
                ->setOpeningBalance(new Money((float)$row['opening_balance']))
                ->setCurrentBalance(new Money((float)$row['opening_balance']))
                ->setMaxWithdrawal(new Money((float)$row['max_withdrawal']))
                ->setApr($row['apr'])
                ->setIncomeType($row['income_type_id'])
                ->setBeginAfter($row['begin_after'])
                ->setBeginYear($row['begin_year'])
                ->setBeginMonth($row['begin_month'])
                ->markUntapped();
            $collection[] = $asset;
        }

        return $collection;
    }

}
