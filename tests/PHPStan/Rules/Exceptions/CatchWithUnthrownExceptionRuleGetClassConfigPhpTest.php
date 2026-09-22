<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class CatchWithUnthrownExceptionRuleGetClassConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CatchWithUnthrownExceptionRule(new DefaultExceptionTypeResolver(
			self::createReflectionProvider(),
			[],
			[],
			[],
			[],
		), true);
	}

	public function testGetClassThrowTypeInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/get-class-throw-type-php-versions.php'], [
			[
				'Dead catch - TypeError is never thrown in the try block.',
				19,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/get-class-throw-type-php-version.neon',
		];
	}

}
