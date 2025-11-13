<?php

namespace Bug8438;

class HelloWorld
{
	/**
	 * @param array<string, string> $array
	 *
	 * @return array{expr: mixed, ...}
	 */
	protected function foo(array $array): array
	{
		$rnd = mt_rand();
		if ($rnd === 0) {
			return ['expr' => 'test'];
		} elseif ($rnd === 1) {
			// no error with checkBenevolentUnionTypes: false (default even with l9 + strict rules)
			return ['expr' => 'test', 1 => 'ok'];
		} else {
			// phpstan must understand 'expr' key is always present in the result,
			// then there will be no error here neither
			return array_merge($array, ['expr' => 'test', 1 => 'ok']);
		}
	}
}
