<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class Bug15291Php82Test extends RuleTestCase
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

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15291.php'], [
			[
				'Dead catch - Exception is never thrown in the try block.',
				8,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				15,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				22,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				29,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				36,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				43,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				50,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-15291-php82.neon',
		];
	}

}
