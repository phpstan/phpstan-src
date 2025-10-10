<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Exceptions\CatchWithUnthrownExceptionRule;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class DynamicMethodThrowTypeExtensionDeadCatchTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return self::getContainer()->getByType(CatchWithUnthrownExceptionRule::class);
	}

	public function testMixedUnknownType(): void
	{
		$this->analyse([__DIR__ . '/data/dynamic-method-throw-type-extension.php'], [
			[
				'Dead catch - Exception is never thrown in the try block.',
				102,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				124,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				148,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				172,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/dynamic-throw-type-extension.neon',
		];
	}

}
