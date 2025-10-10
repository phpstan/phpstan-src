<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Exceptions\CatchWithUnthrownExceptionRule;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class DynamicMethodThrowTypeExtensionDeadCatchPhpDocNotCertainTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return self::getContainer()->getByType(CatchWithUnthrownExceptionRule::class);
	}

	public function testDynamicMethodThrowTypeExtension(): void
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
		]);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return false;
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/dynamic-throw-type-extension.neon',
			__DIR__ . '/do-not-treat-php-doc-type-as-certain.neon',
		];
	}

}
