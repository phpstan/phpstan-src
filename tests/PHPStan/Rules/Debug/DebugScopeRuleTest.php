<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function implode;

/**
 * @extends RuleTestCase<DebugScopeRule>
 */
class DebugScopeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DebugScopeRule(self::createReflectionProvider());
	}

	public function testRuleInPhpStanNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/debug-scope.php'], [
			[
				'Scope is empty',
				7,
			],
			[
				implode("\n", [
					'$a (Yes): int',
					'$b (Yes): int',
					'$debug (Yes): bool',
					'native $a (Yes): int',
					'native $b (Yes): int',
					'native $debug (Yes): bool',
				]),
				10,
			],
			[
				implode("\n", [
					'$a (Yes): int',
					'$b (Yes): int',
					'$debug (Yes): bool',
					'$c (Maybe): 1',
					'native $a (Yes): int',
					'native $b (Yes): int',
					'native $debug (Yes): bool',
					'native $c (Maybe): 1',
					'condition about $c #1: if $debug=false then $c is *ERROR* (No)',
					'condition about $c #2: if $debug=true then $c is 1 (Yes)',
				]),
				16,
			],
		]);
	}

}
