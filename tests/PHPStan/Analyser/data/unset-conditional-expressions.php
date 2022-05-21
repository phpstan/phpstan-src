<?php

namespace UnsetConditionalExpressions;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, mixed> $filteredParameters
	 */
	public function doFoo(array $filteredParameters, array $a): void
	{
		$otherFilteredParameters = $filteredParameters;
		foreach ($a as $k => $v) {
			if (rand(0, 1)) {
				unset($otherFilteredParameters[$k]);
			}
		}

		if (count($otherFilteredParameters) > 0) {
			return;
		}

		assertType('array{}', $otherFilteredParameters);
		assertType('array<string, mixed>', $filteredParameters);
	}

}
