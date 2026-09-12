<?php declare(strict_types = 1);

namespace Bug12500;

class HelloWorld
{
	/**
	 * @param array<string,string|int> $input
	 * @return array<string,string>
	 */
	public function clean(array $input): array {
		foreach ($input as $k => $v) {
			if (\is_int($v)) { $input[$k] = 'was-int'; }
		}
		return $input;
	}
}
