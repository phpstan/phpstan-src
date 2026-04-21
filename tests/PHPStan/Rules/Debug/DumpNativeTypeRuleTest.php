<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DumpNativeTypeRule>
 */
class DumpNativeTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DumpNativeTypeRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/dump-native-type.php'], [
			[
				'Dumped type: non-empty-array',
				11,
			],
			[
				'Dumped type: array',
				12,
			],
			[
				'Dumped type #1: non-empty-array',
				14,
			],
			[
				'Dumped type #2: array',
				14,
			],
		]);
	}

	public function testBug14508(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14508-native.php'], [
			[
				'Dumped type #1: int',
				10,
			],
			[
				'Dumped type #2: bool',
				10,
			],
		]);
	}

}
