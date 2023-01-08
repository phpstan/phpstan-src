<?php // lint >= 8.0

namespace Bug8084a;

use Exception;
use function array_shift;
use function PHPStan\Testing\assertType;

class Bug8084
{
	/**
	 * @param string[] $params
	 */
	public function run(array $params): void
	{
		$a = array_shift($params) ?? throw new Exception();
		$b = array_shift($params) ?? "default_b";
	}
}
