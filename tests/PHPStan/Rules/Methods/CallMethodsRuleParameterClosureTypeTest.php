<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CallMethodsRule>
 */
class CallMethodsRuleParameterClosureTypeTest extends RuleTestCase
{

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/bug-12585.neon'];
	}

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(CallMethodsRule::class);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug12585(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12585.php'], [
			[
				'Parameter #2 $callback of method Bug12585\\Builder<Bug12585\\User>::whereHas() expects (Closure(Bug12585\\Builder<Bug12585\\Model>): mixed)|null, Closure(Bug12585\\Builder, int): Bug12585\\PostBuilder given.',
				155,
				'Type #1 from the union: Parameter #2 $i of passed callable is required but accepting callable does not have that parameter. It will be called without it.',
			],
			[
				'Parameter #2 $callback of method Bug12585\\Builder<Bug12585\\User>::whereHas() expects (Closure(Bug12585\\Builder<Bug12585\\Model>): mixed)|null, Closure(Bug12585\\User): Bug12585\\User given.',
				156,
			],
		]);
	}

}
