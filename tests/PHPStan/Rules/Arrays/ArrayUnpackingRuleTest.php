<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ArrayUnpackingRule>
 */
class ArrayUnpackingRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ArrayUnpackingRule(self::getContainer()->getByType(PhpVersion::class));
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID >= 80100) {
			$this->markTestSkipped('Test requires PHP version <= 8.0');
		}

		$this->analyse([__DIR__ . '/data/array-unpacking.php'], [
			[
				'Array unpacking cannot be used on array that potentially has string keys.',
				7,
			],
			[
				'Array unpacking cannot be used on array that potentially has string keys.',
				18,
			],
			[
				'Array unpacking cannot be used on array that potentially has string keys.',
				24,
			],
			[
				'Array unpacking cannot be used on array that potentially has string keys.',
				29,
			],
			[
				'Array unpacking cannot be used on array that potentially has string keys.',
				40,
			],
			[
				'Array unpacking cannot be used on array that potentially has string keys.',
				52,
			],
			[
				'Array unpacking cannot be used on array that potentially has string keys.',
				63,
			],
		]);
	}

	public function testRuleOnPHP81(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1+');
		}

		$this->analyse([__DIR__ . '/data/array-unpacking.php'], []);
	}

}
