<?php namespace App\Api;

use App\Service\Engine\Engine;

class Data
{
    /**
     * @return array
     */
    function sample() {
        return [
            [
                'id' => 1,
                'descr' => 'first record',
                'price' => 10,
            ],
            [
                'id' => 2,
                'descr' => 'second record',
                'price' => 20,
            ],
            [
                'id' => 3,
                'descr' => 'third record',
                'price' => 15,
            ],
            [
                'id' => 4,
                'descr' => 'fourth record',
                'price' => 18,
            ],
        ];
    }

    function getThing() {
        return [
            [
                'id' => 100,
                'descr' => 'centurian record',
                'price' => 10,
            ],
        ];
    }

	/**
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
    function getTable(
        string $expense,
        string $asset,
        string $income,
        string $tax,
        string $periods,
        string $startYear,
        string $startMonth)
    {
        $engine = new Engine(
            $expense,
            $asset,
            $income,
            $tax,
        );

        $success = $engine->run($periods, $startYear, $startMonth);
        if (!$success) {
            echo "Something went wrong. Starting audit...\n";
            $engine->audit();
        }
        
        return [
            'msg' => 'not implemented'
        ];
    }

}