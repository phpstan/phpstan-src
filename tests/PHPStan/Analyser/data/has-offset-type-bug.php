<?php

namespace HasOffsetTypeBug;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param string[] $errorMessages
	 * @return void
	 */
	public function doFoo(array $errorMessages): void
	{
		$fileErrorsCounts = [];
		assertType('array{}', $fileErrorsCounts);
		foreach ($errorMessages as $errorMessage) {
			assertType('string', $errorMessage);
			if (!isset($fileErrorsCounts[$errorMessage])) {
				assertType('array<string, int<1, max>>', $fileErrorsCounts);
				assertType('int<1, max>', $fileErrorsCounts[$errorMessage]);
				$fileErrorsCounts[$errorMessage] = 1;
				assertType('non-empty-array<string, int<1, max>>', $fileErrorsCounts);
				assertType('1', $fileErrorsCounts[$errorMessage]);
				continue;
			}

			assertType('array<string, int<1, max>>', $fileErrorsCounts);
			assertType('int<1, max>', $fileErrorsCounts[$errorMessage]);

			$fileErrorsCounts[$errorMessage]++;

			assertType('non-empty-array<string, int<1, max>>', $fileErrorsCounts);
			assertType('int<2, max>', $fileErrorsCounts[$errorMessage]);
		}

		assertType('array<string, int<1, max>>', $fileErrorsCounts);
	}

}
