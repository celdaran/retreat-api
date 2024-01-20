<?php namespace App\Api;

use App\Service\Engine\Engine;

class Simulation
{
    /**
     * @url POST /summary
     * @param string $expense
     * @param string $asset
     * @param string $income
     * @param string $tax
     *
     * @param string $periods
     * @param string $startYear
     * @param string $startMonth
     *
     * @return array
     */
    public function summary(
        string $expense,
        string $asset,
        string $income,
        string $tax,
        string $periods,
        string $startYear,
        string $startMonth
    ) {

        $engine = new Engine(
            $expense,
            $asset,
            $income,
            $tax,
        );

        $success = $engine->run($periods, $startYear, $startMonth);
        if (!$success) {
            return [
                'code' => 500,
                'msg' => 'There was a problem running the simulation',
            ];
        }

        $payload = [];

        $plan = $engine->getPlan();
        foreach ($plan as $period) {
            $payload[] = [
                "x" => sprintf("%04d-%02d", $period["year"], $period["month"]),
                "y" => $period["net_expense"]->value(),
            ];
        }

        return $payload;

        /*
        return [
            [
                "x" => "2026-01",
                "y" => 32000
            ],
            [
                "x" => "2026-02",
                "y" => 31000
            ],
            [
                "x" => "2026-03",
                "y" => 30000
            ],
        ];
        */
    }


}
