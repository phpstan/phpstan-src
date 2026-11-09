<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class CatchWithUnthrownExceptionRuleConfigPhp73Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CatchWithUnthrownExceptionRule(new DefaultExceptionTypeResolver(
			self::createReflectionProvider(),
			[],
			[],
			[],
			[],
		), self::getContainer()->getByType(ConstantConditionInTraitHelper::class), true);
	}

	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/unthrown-exception-php-version-range.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/php-version-range-73.neon',
		];
	}

}
