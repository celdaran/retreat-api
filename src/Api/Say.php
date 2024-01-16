<?php namespace App\Api;

class Say
{
	/**
	 * @param string $to
	 * @return array
	 */
	function hello(string $to = 'world') {
		return [
			'result' => "Hello $to!",
			'to' => $to,
		];
	}

	/**
	 * @param string $to
	 * @return array
	 */
	function hi($to) {
		return [
			'result' => "Hi $to!",
			'to' => $to,
		];
	}
}