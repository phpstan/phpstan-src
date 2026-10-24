<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
		$errors = [
			[
				'Dead catch - ValueError is never thrown in the try block.',
				19,
			],
		];

		if (PHP_VERSION_ID >= 80500) {
			// Before PHP 8.5, FILTER_FLAG_GLOBAL_RANGE has the same runtime value as FILTER_THROW_ON_FAILURE on PHP 8.5,
			// so it might throw Filter\FilterFailedException in a PHP version range that only maybe includes PHP 8.5.
			$errors[] = [
				'Dead catch - Filter\\FilterFailedException is never thrown in the try block.',
				58,
			];
		}

		$this->analyse([__DIR__ . '/data/throw-type-php-versions.php'], $errors);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/throw-type-php-version.neon',
		];
	}

}
