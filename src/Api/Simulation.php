<?php

namespace App\Api;

use Exception;
use App\Service\Engine\Engine;

class Simulation
{
    /**
     * @url POST /summary
     *
     * @param string $expense
     * @param string $asset
     * @param string $earnings
     *
     * @param string $periods
     * @param string $startYear
     * @param string $startMonth
     *
     * @return array
     * @api
     *
     */
    public function summary(
        string $expense,
        string $asset,
        string $earnings,
        string $periods,
        string $startYear,
        string $startMonth
    ): array {
        $payload = [];

        try {
            $plan = $this->getPlan($expense, $asset, $earnings, $periods, $startYear, $startMonth);
            foreach ($plan['simulation'] as $period) {
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
     * @param string $expense
     * @param string $asset
     * @param string $earnings
     *
     * @param string $periods
     * @param string $startYear
     * @param string $startMonth
     *
     * @return array
     * @api
     *
     */
    public function assetDepletion(
        string $expense,
        string $asset,
        string $earnings,
        string $periods,
        string $startYear,
        string $startMonth
    ): array {

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
        $logs = [];

        // Fill in data source entries
        try {
            $assetList = [];
            $first = true;

            $plan = $this->getPlan($expense, $asset, $earnings, $periods, $startYear, $startMonth);
            $logs = $plan['logs'];
            foreach ($plan['simulation'] as $period) {

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

        return [
            'simulation' => $payload,
            'logs' => $logs,
        ];

    }


    /**
     * getPlan
     *
     * This method instantiates the engine, executes a plan, and returns the results.
     *
     * @param string $expense
     * @param string $asset
     * @param string $earnings
     *
     * @param string $periods
     * @param string $startYear
     * @param string $startMonth
     *
     * @return array
     * @throws Exception
     */
    private function getPlan(
        string $expense,
        string $asset,
        string $earnings,
        string $periods,
        string $startYear,
        string $startMonth
    ): array {

        $engine = new Engine(
            $expense,
            $asset,
            $earnings
        );

        $success = $engine->run($periods, $startYear, $startMonth);

        if (!$success) {
            throw new Exception("Error running simulation");
        }

        $simulation = $engine->getPlan();
        $logs = $engine->getLogs();

        return [
            'simulation' => $simulation,
            'logs' => $logs,
        ];
    }

}
