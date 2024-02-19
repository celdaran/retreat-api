<?php namespace App\Service\Reporting;

class Report
{
    /** @var array */
    private array $output = [];

    /**
     * @param array $simulation
     * @return array
     */
    public function standard(array $simulation): array
    {
        $i = 0;
        foreach ($simulation as $step) {
            // Header
            if ($i === 0) {
                $this->renderHeader($step);
            }

            // Body
            $this->renderLine($step);
            $i++;
        }
        return $this->output;
    }

    public function renderHeader(array $p)
    {
        $line = sprintf('%s,%s,,', 'period', 'month');

        // Expenses
        if (count($p['expenses']) > 0) {
            foreach (array_keys($p['expenses']) as $expenseName) {
                $line .= sprintf('"%s",', addslashes($expenseName));
            }
        }
        $line .= '"total expenses","income","income tax",,';

        // Earnings
        if (count($p['earnings']) > 0) {
            foreach (array_keys($p['earnings']) as $earningsName) {
                $line .= sprintf('"%s",', addslashes($earningsName));
            }
        }
        $line .= '"total earnings",';
        $line .= '"shortfall",,';
        
        // Assets
        if (count($p['assets']) > 0) {
            foreach (array_keys($p['assets']) as $assetName) {
                $line .= sprintf('"%s",', addslashes($assetName));
            }
        }
        $line .= '"total withdrawals","total assets"' . "\n";

        // Output
        $this->output[] = $line;
    }

    public function renderLine(array $p)
    {
        // Initialize
        $totalExpenses = 0.00;
        $totalEarnings = 0.00;
        $totalAssets = 0.00;

        // Leader
        $line = sprintf('%03d,%4d-%02d,,', $p['period'], $p['year'], $p['month']);

        // Expenses
        foreach ($p['expenses'] as $expense) {
            $line .= sprintf('%.2f,', $expense);
            $totalExpenses += $expense;
        }
        $line .= sprintf('%.2f,%.2f,%.2f,,', $totalExpenses, $p['income'], $p['incomeTax']);

        // Earnings
        foreach ($p['earnings'] as $earnings) {
            $line .= sprintf('%.2f,', $earnings);
            $totalEarnings += $earnings;
        }
        $line .= sprintf('%.2f,', $totalEarnings);
        $line .= sprintf('%.2f,,', $p['shortfall']);

        // Assets
        foreach ($p['assets'] as $asset) {
            $line .= sprintf('%.2f,', $asset);
            $totalAssets += $asset;
        }
        $line .= sprintf('%.2f,', $p['withdrawals']);
        $line .= $totalAssets . "\n";

        // Done
        $this->output[] = $line;
    }

}
