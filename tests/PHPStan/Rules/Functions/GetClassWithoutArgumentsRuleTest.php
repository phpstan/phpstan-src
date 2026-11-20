<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<GetClassWithoutArgumentsRule>
 */
#[RequiresPhp('>= 8.1.0')]
class GetClassWithoutArgumentsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new GetClassWithoutArgumentsRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/get-class-without-arguments.php'], [
			[
				'Calling get_class() without arguments is deprecated in PHP 8.3.',
				15,
				'Use self::class instead.',
			],
			[
				'Calling get_parent_class() without arguments is deprecated in PHP 8.3.',
				16,
				'Pass the object or class name to get_parent_class().',
			],
			[
				'Calling get_class() without arguments is deprecated in PHP 8.3.',
				28,
				'Use self::class instead.',
			],
			[
				'Calling get_class() without arguments is deprecated in PHP 8.3.',
				29,
				'Use self::class instead.',
			],
			[
				'Calling get_parent_class() without arguments is deprecated in PHP 8.3.',
				30,
				'Use parent::class instead.',
			],
			[
				'Calling get_class() without arguments is deprecated in PHP 8.3.',
				31,
				'Use self::class instead.',
			],
			[
				'Calling get_parent_class() without arguments is deprecated in PHP 8.3.',
				46,
				'Class GetClassWithoutArguments\WithoutParent does not extend any class, so this call always returns false.',
			],
			[
				'Function get_class() cannot be called without arguments outside of a class.',
				53,
			],
			[
				'Calling get_parent_class() without arguments is deprecated in PHP 8.3.',
				54,
				'Pass the object or class name to get_parent_class().',
			],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/get-class-without-arguments-php-version.php'], [
			['Calling get_class() without arguments is deprecated in PHP 8.3.', 15, 'Use self::class instead.'],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/get-class-without-arguments.php', __DIR__ . '/data/get-class-without-arguments.php.fixed');
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80200-80300.neon',
		];
	}

}
