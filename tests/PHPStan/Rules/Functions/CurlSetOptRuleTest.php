<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CurlSetOptRule>
 */
class CurlSetOptRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CurlSetOptRule($this->createReflectionProvider());
	}

	public function testFile(): void
	{
		$expectedErrors = [
			[
				'Call to curl_setopt expects parameter 3 to be 0|2, bool given.',
				8,
			],
			[
				'Call to curl_setopt expects parameter 3 to be non-empty-string, int given.',
				14,
			],
			[
				'Call to curl_setopt expects parameter 3 to be array, int given.',
				15,
			],
			[
				'Call to curl_setopt expects parameter 3 to be bool, int given.',
				17,
			],
			[
				'Call to curl_setopt expects parameter 3 to be bool, string given.',
				19,
			],
			[
				'Call to curl_setopt expects parameter 3 to be int, string given.',
				20,
			],
			[
				'Call to curl_setopt expects parameter 3 to be array, string given.',
				22,
			],
			[
				'Call to curl_setopt expects parameter 3 to be resource, string given.',
				24,
			],
		];

		$this->analyse([__DIR__ . '/data/curl_setopt.php'], $expectedErrors);
	}

}
