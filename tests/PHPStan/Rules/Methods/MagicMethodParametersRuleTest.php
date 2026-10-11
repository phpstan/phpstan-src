<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/** @extends RuleTestCase<MagicMethodParametersRule> */
class MagicMethodParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MagicMethodParametersRule();
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug15454(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15454.php'], [
			['Method Bug15454\Foo::__debugInfo() cannot take arguments.', 9],
			['Method Bug15454\Bar::__debugInfo() cannot take arguments.', 18],
			['Method Bug15454\CloneWithArg::__clone() cannot take arguments.', 25],
			['Method Bug15454\DestructWithArg::__destruct() cannot take arguments.', 31],
			['Method Bug15454\VariadicIsNotCounted::__get() must take exactly 1 argument.', 59],
			['Method Bug15454\VariadicIsNotCounted::__call() must take exactly 2 arguments.', 60],
			['Method Bug15454\WrongCounts::__get() must take exactly 1 argument.', 65],
			['Method Bug15454\WrongCounts::__set() must take exactly 2 arguments.', 66],
			['Method Bug15454\WrongCounts::__isset() must take exactly 1 argument.', 67],
			['Method Bug15454\WrongCounts::__unset() must take exactly 1 argument.', 68],
			['Method Bug15454\WrongCounts::__call() must take exactly 2 arguments.', 69],
			['Method Bug15454\WrongCounts::__callStatic() must take exactly 2 arguments.', 70],
			['Method Bug15454\WrongCounts::__toString() cannot take arguments.', 71],
			['Method Bug15454\WrongCounts::__serialize() cannot take arguments.', 72],
			['Method Bug15454\WrongCounts::__unserialize() must take exactly 1 argument.', 73],
			['Method Bug15454\WrongCounts::__set_state() must take exactly 1 argument.', 74],
			['Method Bug15454\WrongCounts::__sleep() cannot take arguments.', 75],
			['Method Bug15454\WrongCounts::__wakeup() cannot take arguments.', 76],
			['Method Bug15454\WrongCounts::__DEBUGINFO() cannot take arguments.', 77],
			['Method Bug15454\ByRef::__get() cannot take arguments by reference.', 82],
			['Method Bug15454\ByRef::__set() cannot take arguments by reference.', 83],
			['Method Bug15454\ByRef::__isset() cannot take arguments by reference.', 84],
			['Method Bug15454\ByRef::__unset() cannot take arguments by reference.', 85],
			['Method Bug15454\ByRef::__call() cannot take arguments by reference.', 86],
			['Method Bug15454\ByRef::__callStatic() cannot take arguments by reference.', 87],
			['Method Bug15454\ByRef::__unserialize() cannot take arguments by reference.', 88],
			['Method Bug15454\ByRef::__set_state() cannot take arguments by reference.', 89],
			['Method Bug15454\SomeInterface::__clone() cannot take arguments.', 94],
			['Method Bug15454\SomeAbstract::__get() must take exactly 1 argument.', 99],
			['Method Bug15454\SomeEnum::__debugInfo() cannot take arguments.', 104],
			['Method Bug15454\UsesTrait::__toString() cannot take arguments.', 109],
		]);
	}

}
