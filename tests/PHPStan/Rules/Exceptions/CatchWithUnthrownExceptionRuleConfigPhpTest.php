<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class CatchWithUnthrownExceptionRuleConfigPhpTest extends RuleTestCase
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

	public function testThrowTypesInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/throw-type-php-versions.php'], [
			[
				'Dead catch - ValueError is never thrown in the try block.',
				19,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/throw-type-php-version.neon',
		];
	}

}
