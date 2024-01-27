<?php

namespace App\Api;

use Exception;
use App\Service\Engine\Engine;

class Simulation
{
    /**
     * @url POST /summary
     *
     * @api
     *
     * @param string $expense
     * @param string $asset
     * @param string $income
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
        string $periods,
        string $startYear,
        string $startMonth
    ): array {
        $payload = [];

        try {
            $plan = $this->getPlan($expense, $asset, $income, $periods, $startYear, $startMonth);
            foreach ($plan as $period) {
                $payload[] = [
                    "x" => sprintf("%04d-%02d", $period["year"], $period["month"]),
                    "y" => $period["net_expense"]->value(),
                ];
            }
        } catch (Exception $e) {
            $payload = [
                'code' => 500,
                'message' => $e->getMessage(),
            ];
        }

        return $payload;
    }

    /**
     * @url POST /asset/depletion
     *
     * @api
     *
     * @param string $expense
     * @param string $asset
     * @param string $income
     *
     * @param string $periods
     * @param string $startYear
     * @param string $startMonth
     *
     * @return array
     */
    public function assetDepletion(
        string $expense,
        string $asset,
        string $income,
        string $periods,
        string $startYear,
        string $startMonth
    ) {

        // Generate payload template
        $payload = [
            'dataset' => [
                'source' => [
                ],
            ],
            'tooltip' => [
                "trigger" => "axis",
                "axisPointer" => [
                    "type" => "shadow"
                ]
            ],
            'title' => [
                "text" => "Asset Depletion",
                "left" => "center",
            ],
            'legend' => [
                "top" => 40,
                "type" => "scroll",
            ],
            'grid' => [
                "left" => 15,
                "right" => 15,
                "bottom" => 30,
                "top" => 100,
                "containLabel" => true
            ],
            'xAxis' => [
                "type" => "category"
            ],
            'yAxis' => [
                "type" => "value"
            ],
            'series' => [],
        ];

        // Fill in data source entries
        try {
            $assetList = [];
            $first = true;
    
            $plan = $this->getPlan($expense, $asset, $income, $periods, $startYear, $startMonth);
            foreach ($plan as $period) {

                $entry = [
                    sprintf("%04d-%02d", $period["year"], $period["month"]),
                ];

                foreach ($period['assets'] as $name => $value) {
                    $entry[] = $value;
                    if ($first) {
                        $assetList[] = $name;
                    }
                }

                if ($first) {
                    $header = ["period"];
                    foreach ($assetList as $asset) {
                        $header[] = $asset;
                    }
                    $payload['dataset']['source'][] = $header;
                    $first = false;
                }

                $payload['dataset']['source'][] = $entry;
            }

            // Lastly define series
            for ($i = 0; $i < count($assetList); $i++) {
                $payload['series'][] = [
                    "type" => "bar",
                    "stack" => "Asset",
                ];
            }
        } catch (Exception $e) {
            $payload = [
                'code' => 500,
                'message' => $e->getMessage(),
            ];
        }

        return $payload;

    }


    /**
     * getPlan
     *
     * This method instantiates the engine, executes a plan, and returns the results.
     *
     * @param string $expense
     * @param string $asset
     * @param string $income
     *
     * @param string $periods
     * @param string $startYear
     * @param string $startMonth
     *
     * @throws Exception
     * @return array
     */
    private function getPlan(
        string $expense,
        string $asset,
        string $income,
        string $periods,
        string $startYear,
        string $startMonth
    ): array {

        $engine = new Engine(
            $expense,
            $asset,
            $income
        );

        $success = $engine->run($periods, $startYear, $startMonth);

        if (!$success) {
            throw new Exception("Error running simulation");
        }

        return $engine->getPlan();
    }

}
