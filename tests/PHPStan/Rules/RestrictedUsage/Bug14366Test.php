<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedFunctionUsageRule>
 */
class Bug14366Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RestrictedFunctionUsageRule(
			self::getContainer(),
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14366.php'], [
			[
				'Call to deprecated function curl_close().',
				28,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-14366.neon',
			...parent::getAdditionalConfigFiles(),
		];
	}

}
