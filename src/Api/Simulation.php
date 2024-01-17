<?php

namespace App\Api;

use App\Service\Engine;

class Simulation
{
    // GET https://www.technitivity.com/retreat/api/simulation
    /**
     * @url GET /simulation
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
    public function getTable(
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
            [
                "x" => "2026-04",
                "y" => 30000
            ],
            [
                "x" => "2026-05",
                "y" => 29000
            ],
            [
                "x" => "2026-06",
                "y" => 28000
            ],
            [
                "x" => "2026-07",
                "y" => 27500
            ],
            [
                "x" => "2026-08",
                "y" => 26000
            ],
            [
                "x" => "2026-09",
                "y" => 21000
            ]
        ];
    }


}
