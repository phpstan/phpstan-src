<?php

namespace StrictComparison;

class Foo
{

	public function doFoo()
	{
		1 === 1;
		1 === '1'; // wrong
		1 !== '1'; // wrong
		doFoo() === doBar();
		1 === null;
		(new Bar()) === 1; // wrong

		/** @var Foo[]|Collection|bool $unionIterableType */
		$unionIterableType = doFoo();
		1 === $unionIterableType;
		false === $unionIterableType;
		$unionIterableType === [new Foo()];
		$unionIterableType === new Collection();

		/** @var bool $boolean */
		$boolean = doFoo();
		true === $boolean;
		false === $boolean;
		$boolean === true;
		$boolean === false;
		true === false;
		false === true;

		$foo = new self();
		$this === $foo;

		$trueOrFalseInSwitch = false;
		switch ('foo') {
			case 'foo':
				$trueOrFalseInSwitch = true;
				break;
		}
		if ($trueOrFalseInSwitch === true) {

		}

		1.0 === 1;
		1 === 1.0;

		/** @var string|mixed $stringOrMixed */
		$stringOrMixed = doFoo();
		$stringOrMixed === 'foo';
	}

	public function doBar(string $a = null, string $b = null): string
	{
		if ($a === null && $b === null) {
			return 'no value';
		}

		if ($a !== null && $b !== null) {
			return $a . $b;
		}

		return '';
	}

	public function acceptsString(string $a)
	{
		if ($a === null) {

		}
	}

	public function anotherAcceptsString(string $a)
	{
		if ($a !== null) {

		}
	}

	public function foreachWithTypeChange()
	{
		$foo = null;
		foreach ([] as $val) {
			if ($foo !== null) {

			}
			if ($foo !== 1) {

			}

			if (something()) {
				$foo = new self();
			}
		}

		foreach ([1, 2, 3] as $val) {
			if ($val === null) {

			}
			$val = null;
		}
	}

	/**
	 * @param int[]|true $a
	 */
	public function unionOfIntegersAndTrue($a)
	{
		if ($a !== true) {
			$a = [];
		}

		if ($a !== true) {
			$a[] = 1;
		}

		if ($a !== true && count($a) > 0) {
			$a = reset($a);
		}
	}

	public function whileWithTypeChange()
	{
		$foo = null;
		while (fetch()) {
			if ($foo !== null) {

			}
			if ($foo !== 1) {

			}

			if (something()) {
				$foo = new self();
			}
		}

		while ($val = $this->returnArray()) {
			if ($val === null) {

			}
			$val = null;
		}
	}

	public function forWithTypeChange()
	{
		for (; $val = $this->returnArray();) {
			if ($val === null) {

			}
			$val = null;
		}

		$foo = null;
		for (;;) {
			if ($foo !== null) {

			}
			if ($foo !== 1) {

			}

			if (something()) {
				$foo = new self();
			}
		}
	}

	private function returnArray(): array
	{

	}

}

class Node
{

	/** @var self|null */
	private $next;

	/** @var int */
	private $id;

	public function iterate(): void
	{
		for ($node = $this; $node !== null; $node = $node->next) {
			// ...
		}
	}

	public function checkCycle()
	{
		if ($this->next !== null) {
			$iter = $this->next;
			while ($iter !== null) {
				if ($iter->id === $this->id) {
					throw new \Exception('Cycle detected.');
				}

				$iter = $iter->next;
			}
		}
	}

	public function checkAnotherCycle()
	{
		if ($this->next !== null) {
			$iter = $this->next;
			while ($iter !== false) {
				if ($iter->id === $this->id) {
					throw new \Exception('Cycle detected.');
				}

				$iter = $iter->next;
			}
		}
	}

	public function finallyNullability()
	{
		$result = null;
		try {
			if (doFoo()) {
				throw new \Exception();
			}
			$result = '1';
		} finally {
			if ($result !== null) {

			}
		}
	}

	public function checkForCycle()
	{
		if ($this->next !== null) {
			$iter = $this->next;
			for (;$iter !== null;) {
				if ($iter->id === $this->id) {
					throw new \Exception('Cycle detected.');
				}

				$iter = $iter->next;
			}
		}
	}

	public function checkAnotherForCycle()
	{
		if ($this->next !== null) {
			$iter = $this->next;
			for (;$iter !== false;) {
				if ($iter->id === $this->id) {
					throw new \Exception('Cycle detected.');
				}

				$iter = $iter->next;
			}
		}
	}

	public function looseNullCheck(?\stdClass $foo)
	{
		if ($foo == null) {
			return;
		}

		if ($foo !== null) {

		}
	}
}

class ConstantValuesComparison
{

	function testInt()
	{
		$a = 1;
		$b = 2;
		$a === $b;
	}


	function testArray()
	{
		$a = ['X' => 1];
		$b = ['X' => 2];
		$a === $b;
	}


	function testArrayTricky()
	{
		$a = ['X' => 1, 'Y' => 2];
		$b = ['X' => 2, 'Y' => 1];
		$a === $b;
	}


	function testArrayTrickyAlternative()
	{
		$a = ['X' => 1, 'Y' => 2];
		$b = ['Y' => 2, 'X' => 1];
		$a === $b;
	}

}

class PredefinedConstants
{

	public function doFoo()
	{
		DIRECTORY_SEPARATOR === '/';
		DIRECTORY_SEPARATOR === '\\';
		DIRECTORY_SEPARATOR === '//';
	}

}

class ConstantTypeInWhile
{

	public function doFoo()
	{
		$i = 0;
		while ($i++) {
			if ($i === 1000000) {

			}
			if ($i === 'string') {

			}
		}

		if ($i === 1000000) {

		}
		if ($i === 'string') {

		}
	}

}

class ConstantTypeInDoWhile
{

	public function doFoo()
	{
		$i = 0;
		do {
			if ($i === 1000000) {

			}
			if ($i === 'string') {

			}
		} while ($i++);

		if ($i === 1000000) {

		}
		if ($i === 'string') {

		}
	}

}

class ConstantAssignOperatorInWhile
{

	public function doFoo(bool $bool)
	{
		$i = 10.0;
		while ($bool) {
			$i /= 5;
			if ($i === 1000000.0) {

			}
			if ($i === 'string') {

			}
		}

		if ($i === 1000000.0) {

		}
		if ($i === 'string') {

		}
	}

}

class NullArrayKey
{

	public function doFoo()
	{
		$array = [];
		$array['key'] = null;
		if ($array['key'] !== null) {

		}
	}

}

class OverwriteSpecifiedVariable
{

	public function doFoo()
	{
		/** @var int[] $array */
		$array = doFoo();
		if ($array['key'] === 1) {
			$array = [
				'key' => 0,
			];
			$array['key'] === 0;
		}
	}

}

class StrictComparisonOfSpecifiedFunctionCall
{

	public function doFoo()
	{
		if (is_int($this->nullableInt())) {
			if ($this->nullableInt() ===  null) {

			}
		}
	}
	/** @phpstan-impure */
	public function nullableInt(): ?int
	{

	}

}

class CheckDefaultArrayKeys
{

	/**
	 * @param string[] $array
	 */
	public function doFoo(array $array)
	{
		foreach ($array as $key => $val) {
			if ($key === 1) {

			} elseif ($key === 'str') {

			} elseif ($key === 1.0) {

			} elseif ($key === new \stdClass()) {

			}
		}
	}

}

class DoNotReportPropertyFetchAndNullComparison
{

	/** @var self */
	private $foo;

	/** @var self */
	private static $bar;

	public function doFoo()
	{
		if ($this->foo === null) {

		}
		if ($this->foo !== null) {

		}
		if (null === $this->foo) {

		}
		if (null !== $this->foo) {

		}
	}

	public function doBar()
	{
		if (self::$bar === null) {

		}
		if (self::$bar !== null) {

		}
		if (null === self::$bar) {

		}
		if (null !== self::$bar) {

		}
	}

}

class ComparingAgainstEmptyArray
{

	/**
	 * @param string[] $strings
	 * @param mixed[] $mixeds
	 */
	public function doFoo(
		array $strings,
		array $mixeds
	)
	{
		if ($strings === []) {

		}
		if ($mixeds === []) {

		}
	}

	/**
	 * @param string[] $strings
	 * @param mixed[] $mixeds
	 */
	public function doBar(
		array $strings,
		array $mixeds
	)
	{
		if ([] === $strings) {

		}
		if ([] === $mixeds) {

		}
	}

}

class StaticVar
{

	public function doFoo()
	{
		static $i = null;

		if ($i === 5) {

		}

		$i = rand(0, 1);
	}

}

class DuplicateConditionNeverError
{

	public function sort(int $a, int $b)
	{
		$c = 0;

		if ($c === $a && $c === $b) {
			return +1;
		}

		if ($c === $a) {
			return -1;
		}

		if ($c === $b) {
			return +1;
		}
	}

}

class CoalesceWithConstantArray
{

	/** @var string[] */
	private const B = [
		'foo' => 'bar',
	];

	public function doFoo(string $x): string
	{
		$class = self::B[$x] ?? null;

		if ($class === null) {
			throw new \Exception();
		}

		return $class;
	}

}

class NonIdempotentOperationInForeach
{

	public function doFoo(array $array)
	{
		$i = 10;
		foreach ($array as $foo) {
			if (rand(0, 1) === 100) {
				$i *= 10;
				if ($i === 'foo') {
				}
			}
		}

		$nullableVal = null;
		foreach ($array as $foo) {
			if ($nullableVal === null) {
				$nullableVal = 1;
			} else {
				$nullableVal *= 10;
				if ($nullableVal === 'foo') {

				}
			}
		}
	}

}

class ArrayWithLongStrings
{

	public function doFoo()
	{
		$array = ['foofoofoofoofoofoofoo','foofoofoofoofoofoofob'];

		foreach ($array as $value) {
			if ('foofoofoofoofoofoofoo' === $value) {
				echo 'nope';
			} elseif ('foofoofoofoofoofoofob' === $value) {
				echo 'nop nope';
			}
		}
	}

}

class ArrayObjectToArrayCount
{

	public function doFoo()
	{
		$rules = (array) new \ArrayObject([1, 2, 3]);
		if (count($rules) === 1) {

		}
	}

}

class WrongNullabilityInPhpDoc
{

	/**
	 * @param string $str
	 */
	public function doFoo(?string $str)
	{
		$str === 'str';
		$str === null;
		$str === 1;
	}

	/**
	 * @param string $str
	 */
	public function doBar(?string $str = null)
	{
		$str === 'str';
		$str === null;
		$str === 1;
	}

	/**
	 * @param string $str
	 */
	public function doBaz(?string $str = '')
	{
		$str === 'str';
		$str === null;
		$str === 1;
	}

}

class ComplexSwitch
{

	public function testing(array $types): void {
		$test = null;
		foreach ($types as $t) {
			switch ($t) {
				case 'foo':
					$test = 'fff';
					break;

				case 'bar':
					$test = 'bbb';
					break;
			}

			if ($test !== null) {
				echo "Found";
				break;
			}
		}

		if ($test === null) {
			echo "Is null";
		}
	}

}

class IgnoredBreakBranchInForeach
{

	public function doFoo(string $xvalue, array $allVowels)
	{
		$lastLetter = null;
		foreach ($allVowels as $yvalue) {
			if (strcmp($xvalue, $yvalue) == 0 ) {
				$lastLetter = $xvalue;
				break;
			} else {
				continue;
			}
		}
		if ($lastLetter !== null) {
		}
	}

}

class DecrementInForeachWithBreak
{

	public function doFoo()
	{
		$max = 0 === rand(0, 1) ? 2 : 3;
		foreach ([1, 2, 3, 4, 5] as $number) {
			if (0 === $max) {
				break;
			}

			echo $number;
			$max--;
		}
	}

}

class RewrittenArray
{

	public function doFoo(array $args)
	{
		if (isset($args[0]) === true) {
			if (is_array($args[0]) === true) {
				$args = $args[0];
			}

			if (isset($args[0]) === false) {
				echo 'foo';
			}
		}
	}

}

class SubtractedMixed
{

	public function doFoo($mixed)
	{
		if ($mixed === 1) {
			return;
		}

		if (is_string($mixed)) {
			return;
		}

		if ($mixed === 'foo') {
			return;
		}

		if ($mixed !== 1) {
			return;
		}
	}

	public function doBar($mixed)
	{
		if ($mixed === null) {

		} elseif ($this->foo === 'bbb') {

		}

		$mixed === null;
	}

}

class InvalidatingProperties
{

	/** @var self */
	private $self;

	/** @var self */
	private $selfa;

	/** @var string */
	private $prop;

	public function test()
	{
		assert($this->self->prop === 'foo');
		assert($this->selfa->prop === 'foo');
		if ($this->self->prop === 'foo') {

		}
		if ($this->selfa->prop === 'foo') {

		}

		$this->self = new self();
		if ($this->self->prop === 'foo') {

		}
		if ($this->selfa->prop === 'foo') {

		}
	}

}

class InvalidatingVariables
{

	/** @var string */
	private $prop;

	public function test()
	{
		$self = new self();
		$selfa = new self();
		assert($self->prop === 'foo');
		assert($selfa->prop === 'foo');
		if ($self->prop === 'foo') {

		}
		if ($selfa->prop === 'foo') {

		}

		$self = new self();
		if ($self->prop === 'foo') {

		}
		if ($selfa->prop === 'foo') {

		}
	}

}

class InvalidationAfterCallingSideEffects
{

	public function doFoo(\DateTime $dt)
	{
		assert($dt->getTimestamp() === 1000);
		$dt->modify('+1 hours');
		if ($dt->getTimestamp() === 1000) {

		}
	}

	public function doBar(\DateTimeImmutable $dti)
	{
		assert($dti->getTimestamp() === 1000);
		$dti->modify('+1 hours');
		if ($dti->getTimestamp() === 1000) {

		}
	}

}

class InvalidateAfterCallingSideEffectsUserlandMethod
{

	/** @var int|null */
	private $foo;

	public function doFoo()
	{
		$this->foo = 123;
		$this->returnsVoid();
		if ($this->foo === 456) {

		}
	}

	private function returnsVoid(): void
	{
		$this->foo = null;
	}

}

abstract class RandomAbstractClass {}

class AvoidRegressionBecauseThisWasReturningAnErrorInPhpstan11_5
{
	private const MAPPING = [\stdClass::class, RandomAbstractClass::class];

	public function test(int $key)
	{
		if (is_subclass_of(self::MAPPING[$key], RandomAbstractClass::class) === false) {
			return;
		}
	}
}

class ArrayWithNonEmptyStringValue
{

	/**
	 * @param array<int, non-empty-string> $a
	 */
	public function doFoo(array $a): void
	{
		if ($a === []) {

		}
	}

	/**
	 * @param array<int, numeric-string> $a
	 */
	public function doBar(array $a): void
	{
		if ($a === []) {

		}
	}

}

function () {
	INF === INF;
	NAN === NAN;

	INF !== INF;
	NAN !== NAN;
};

class ArrayWithLongStrings2
{

	public function doFoo()
	{
		$array = ['foofoofoofoofoofoofoo','foofoofoofoofoofoofob'];

		foreach ($array as $value) {
			if ('foofoofoofoofoofoofoo' === $value) {
				echo 'nope';
			} elseif ('foofoofoofoofoofoofob' === $value) {
				echo 'nop nope';
			} elseif (rand(0, 1) === 0) {
				echo 'nope';
			}
		}
	}

}

class TestLiteralStringVerbosityFix
{

	/**
	 * @param lowercase-string|false $a
	 */
	public function doFoo($a): void
	{
		if ($a === 'AB') {

		}
	}

}

class SubtractedMixedAgainstNull
{

	public function doFoo($m): void
	{
		if ($m === null) {
			return;
		}

		if ($m === null) {

		}

		if ($m !== null) {

		}
	}

	public function doBar($m, int $i, int $j): void
	{
		if ($m === null) {
			return;
		}

		$a = [1, $m, 3];
		$b = [$i, null, $j];

		if ($a !== $b) {

		}
	}

}
