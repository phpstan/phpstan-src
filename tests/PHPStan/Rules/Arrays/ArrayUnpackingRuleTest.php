<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ArrayUnpackingRule>
 */
class ArrayUnpackingRuleTest extends RuleTestCase
{

	private bool $checkUnions;

	private bool $checkBenevolentUnions = false;

	protected function getRule(): Rule
	{
		return new ArrayUnpackingRule(
			self::getContainer()->getByType(PhpVersion::class),
			new RuleLevelHelper($this->createReflectionProvider(), true, false, $this->checkUnions, false, false, $this->checkBenevolentUnions, true),
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID >= 80100) {
			$this->markTestSkipped('Test requires PHP version <= 8.0');
		}

		$this->checkUnions = true;
		$this->checkBenevolentUnions = true;
		$this->analyse([__DIR__ . '/data/array-unpacking.php'], [
			[
				'Array unpacking cannot be used on an array with potential string keys: array{foo: \'bar\', 0: 1, 1: 2, 2: 3}',
				7,
			],
			[
				'Array unpacking cannot be used on an array with string keys: array<string, string>',
				18,
			],
			[
				'Array unpacking cannot be used on an array with potential string keys: array<string>',
				24,
			],
			[
				'Array unpacking cannot be used on an array with potential string keys: array',
				29,
			],
			[
				'Array unpacking cannot be used on an array with potential string keys: array<string>',
				40,
			],
			[
				'Array unpacking cannot be used on an array with potential string keys: array<string>',
				52,
			],
			[
				'Array unpacking cannot be used on an array with string keys: array{foo: string, bar: int}',
				63,
			],
			[
				'Array unpacking cannot be used on an array with potential string keys: array<int|string, string>',
				71,
			],
		]);
	}

	public function testRuleDoNotCheckBenevolentUnion(): void
	{
		if (PHP_VERSION_ID >= 80100) {
			$this->markTestSkipped('Test requires PHP version <= 8.0');
		}

		$this->checkUnions = true;
		$this->analyse([__DIR__ . '/data/array-unpacking.php'], [
			[
				'Array unpacking cannot be used on an array with potential string keys: array{foo: \'bar\', 0: 1, 1: 2, 2: 3}',
				7,
			],
			[
				'Array unpacking cannot be used on an array with string keys: array<string, string>',
				18,
			],
			[
				'Array unpacking cannot be used on an array with string keys: array{foo: string, bar: int}',
				63,
			],
			[
				'Array unpacking cannot be used on an array with potential string keys: array<int|string, string>',
				71,
			],
		]);
	}

	public function testRuleDoNotCheckUnions(): void
	{
		if (PHP_VERSION_ID >= 80100) {
			$this->markTestSkipped('Test requires PHP version <= 8.0');
		}

		$this->checkUnions = false;
		$this->analyse([__DIR__ . '/data/array-unpacking.php'], [
			[
				'Array unpacking cannot be used on an array with string keys: array<string, string>',
				18,
			],
			[
				'Array unpacking cannot be used on an array with string keys: array{foo: string, bar: int}',
				63,
			],
		]);
	}

	public static function dataRuleOnPHP81(): array
	{
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider dataRuleOnPHP81
	 */
	public function testRuleOnPHP81(bool $checkUnions): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1+');
		}

		$this->checkUnions = $checkUnions;
		$this->analyse([__DIR__ . '/data/array-unpacking.php'], []);
	}

}
