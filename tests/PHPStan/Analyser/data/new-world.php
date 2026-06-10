<?php declare(strict_types = 1);

namespace NewWorldTypeInference;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	public function scalarsAndAssigns(): void
	{
		$a = 1;
		assertType('1', $a);

		$b = 'foo';
		assertType('\'foo\'', $b);

		$c = 1.5;
		assertType('1.5', $c);

		$d = $e = 5;
		assertType('5', $d);
		assertType('5', $e);
	}

	public function functionCalls(int $i, string $s): void
	{
		assertType('int', $i);
		assertType('string', $s);

		$len = strlen($s);
		assertType('int<0, max>', $len);

		$cnt = strlen('abc');
		assertType('3', $cnt);

		$abs = abs($i);
		assertType('int<0, max>', $abs);

		$abs2 = abs(7);
		assertType('7', $abs2);

		$nested = strlen(strtoupper($s));
		assertType('int<0, max>', $nested);

		$pi = pi();
		assertType('float', $pi);
	}

	public function narrowingInIf(string $s): void
	{
		$v = 1;
		if ($v) {
			assertType('1', $v);
		} else {
			assertType('*NEVER*', $v);
		}

		$w = rand(0, 1);
		assertType('int<0, 1>', $w);
		if ($w) {
			assertType('1', $w);
		} else {
			assertType('0', $w);
		}

		$len = strlen($s);
		assertType('int<0, max>', $len);
		if ($len) {
			assertType('int<1, max>', $len);
		} else {
			assertType('0', $len);
		}
	}

	public function assignInCondition(string $s): void
	{
		if ($len = strlen($s)) {
			assertType('int<1, max>', $len);
		} else {
			assertType('0', $len);
		}
	}

	public function functionAsserts(): void
	{
		$m = mixedValue();
		assertType('mixed', $m);
		assertInt($m);
		assertType('int', $m);
	}

	public function conditionalReturnType(int $i): void
	{
		assertType('bool', isPositive($i));
		if (isPositive($i)) {
			assertType('int<1, max>', $i);
		} else {
			assertType('int<min, 0>', $i);
		}
	}

	public function conditionalExpressionHolders(string $s): void
	{
		$len = strlen($s);
		if ($len) {
			assertType('non-empty-string', $s);
			assertType('int<1, max>', $len);
			assertType('int<1, max>', strlen($s));
		} else {
			assertType('\'\'', $s);
			assertType('0', $len);
		}
	}

	public function assignByReference(): void
	{
		$q = 1;
		$r = &$q;
		assertType('1', $r);
	}

	/**
	 * Each item type is captured at its own evaluation point in the sequence —
	 * the old world resolves all items on a single scope and cannot get this right.
	 */
	public function arrayLiteralWithSequentialSideEffects(): void
	{
		$a = [
			$b = 1,
			$b + 1,
			$c = $b,
			$c + 2,
			$c++,
			$c,
		];
		assertType('array{1, 2, 1, 3, 1, 2}', $a);
	}

	public function comparisonOperators(int $i, int $j): void
	{
		assertType('bool', $i < $j);
		assertType('bool', $i <= $j);
		assertType('bool', $i > $j);
		assertType('bool', $i >= $j);
		assertType('true', 1 < 2);
		assertType('true', 2 > 1);
		assertType('false', 1 >= 2);
		assertType('true', 1 <= 1);
	}

	public function equalityOperators(int $i, int $j): void
	{
		assertType('true', $i == $i);
		assertType('false', $i != $i);
		assertType('bool', $i == $j);
		assertType('bool', $i != $j);
		assertType('true', 1 == 1);
		assertType('false', 1 != 1);
		assertType('bool', $i === $j);
		assertType('bool', $i !== $j);
		assertType('true', 1 === 1);
		assertType('false', 1 !== 1);
	}

	public function logicalAndArithmeticOperators(int $i, int $j, bool $b1, bool $b2): void
	{
		assertType('bool', $b1 xor $b2);
		assertType('true', true xor false);
		assertType('false', true xor true);
		assertType('int<-1, 1>', $i <=> $j);
		assertType('1', 2 <=> 1);
		assertType("'ab'", 'a' . 'b');
		assertType('int', $i & $j);
		assertType('4', 6 & 5);
		assertType('int', $i | $j);
		assertType('7', 6 | 5);
		assertType('int', $i ^ $j);
		assertType('3', 6 ^ 5);
		assertType('5', 10 / 2);
		assertType('(float|int)', $i / $j);
		assertType('1', 10 % 3);
		assertType('3', 5 - 2);
		assertType('int', $i - $j);
		assertType('10', 5 * 2);
		assertType('25', 5 ** 2);
		assertType('20', 5 << 2);
		assertType('1', 5 >> 2);
	}

	public function incrementDecrement(int $i): void
	{
		$a = 1;
		$preInc = ++$a;
		assertType('2', $preInc);
		assertType('2', $a);

		$b = 5;
		$preDec = --$b;
		assertType('4', $preDec);
		assertType('4', $b);

		$c = 7;
		$postDec = $c--;
		assertType('7', $postDec);
		assertType('6', $c);

		$u = rand(0, 1) ? 1 : 5;
		$u++;
		assertType('2|6', $u);

		$d = rand(0, 1) ? 9 : 3;
		$d--;
		assertType('2|8', $d);

		$i++;
		assertType('int', $i);
		--$i;
		assertType('int', $i);
	}

	public function keyedArrayLiteral(int $i): void
	{
		$a = ['a' => 1, 'b' => $i];
		assertType('array{a: 1, b: int}', $a);
	}

	public function callablePairArray(string $method): void
	{
		if (is_callable([$this, $method])) {
			assertType('list{$this(NewWorldTypeInference\Foo), string}&callable(): mixed', [$this, $method]);
		}
	}

	public function nullableTruthyNarrowing(): void
	{
		$n = rand(0, 1) ? 'x' : null;
		if ($n) {
			assertType('\'x\'', $n);
		} else {
			assertType('null', $n);
		}
	}

	public function postIncInCondition(int $i): void
	{
		if ($i++) {
			assertType('int', $i);
		}
	}

	/**
	 * Nullsafe short-circuiting: a truthy `$bar?->...` implies the subject is
	 * non-null and the plain-chain variant is narrowed too. In the new world
	 * this knowledge lives in the nullsafe handlers alone (they process first,
	 * parents compose their results) — no parent re-derives it from types.
	 */
	public function nullsafeShortCircuiting(?Holder $holder): void
	{
		if ($holder?->count) {
			assertType('NewWorldTypeInference\Holder', $holder);
			assertType('int<min, -1>|int<1, max>', $holder->count);
		}

		if ($holder?->name !== null) {
			assertType('NewWorldTypeInference\Holder', $holder);
			assertType('string', $holder->name);
		}

		// nullsafe embedded under a migrated handler's narrowing: the FuncCall's
		// conditional return narrows its argument, the apply side narrows $holder
		if (strlen((string) $holder?->name) > 0) {
			assertType('non-empty-string', (string) $holder?->name);
		}
	}

	public function nullsafeVariants(Holder $definite, ?Holder $maybe, string $prop): void
	{
		// non-nullable subject: ?-> behaves like -> (no null union)
		assertType('int', $definite?->count);
		if ($definite?->count) {
			assertType('int<min, -1>|int<1, max>', $definite->count);
		} else {
			assertType('0', $definite->count);
		}

		// null subject: always short-circuits
		$nothing = null;
		assertType('null', $nothing?->count);

		// chain: the short-circuit null propagates through the plain fetch
		assertType('int|null', $maybe?->inner->count);

		// dynamic property names take the legacy bridge
		assertType('mixed', $definite->{$prop});
		assertType('mixed', $maybe?->{$prop});

		// bare statement (null context narrowing)
		$maybe?->count;
		assertType('NewWorldTypeInference\\Holder|null', $maybe);
	}

	/**
	 * @param array<int, Holder|null> $holders
	 */
	public function nullsafeOnArrayDimFetch(array $holders): void
	{
		assertType('int|null', $holders[0]?->count);
	}

	public function propertyNativeTypes(Holder $h): void
	{
		assertNativeType('int', $h->count);
		assertNativeType('mixed', $h->untyped);
		assertType('*ERROR*', $h->unknownProp);
		assertNativeType('*ERROR*', $h->unknownProp);
	}

	/**
	 * @param positive-int $p
	 */
	public function nativeTypes(int $i, string $s, $p): void
	{
		assertNativeType('int', $i);
		assertNativeType('int<0, max>', strlen($s));
		assertType('int<1, max>', $p);
		assertNativeType('mixed', $p);
	}

	public function methodCallResult(): void
	{
		assertType('string', $this->name());
		assertNativeType('string', $this->name());
	}

	public function trackedPropertyNarrowing(): void
	{
		if (is_int($this->mixedProp)) {
			assertType('int', $this->mixedProp);
		}
	}

	public function mixedNarrowingViaIsFunctions(): void
	{
		$m = mixedValue();
		if (is_int($m)) {
			assertType('int', $m);
		} else {
			assertType('mixed~int', $m);
		}

		$m2 = mixedValue();
		if (is_string($m2)) {
			assertType('string', $m2);
		}
	}

	public function dynamicVariables(string $name): void
	{
		assertType('*ERROR*', $undefined);
		$holder = 1;
		assertType('mixed', $$name);
	}

	public function unmigratedConditions(string $s, bool $a, bool $b, mixed $m): void
	{
		if (!$a) {
			assertType('false', $a);
		} else {
			assertType('true', $a);
		}

		if ($a && $b) {
			assertType('true', $a);
			assertType('true', $b);
		}

		if ($a || $b) {
			assertType('bool', $a);
		}

		if ($m instanceof Foo) {
			assertType('NewWorldTypeInference\Foo', $m);
		}

		if (!empty($s)) {
			assertType('non-falsy-string', $s);
		}

		$arr = [];
		if (rand(0, 1)) {
			$arr[] = 'v';
		}
		if (isset($arr[0])) {
			assertType("array{'v'}", $arr);
		}
		if (count($arr) > 0) {
			assertType("array{'v'}", $arr);
		}
	}

	public function bareCallStatement(): void
	{
		$this->name();
		assertType('string', $this->name());
	}

	public function trackedCallExpression(string $s): void
	{
		$len = strlen($s);
		assertType('int<0, max>', strlen($s));
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function assertOnUntrackedExpression(array $data): void
	{
		assert(is_int($data['k']));
		assertType('int', $data['k']);
	}

	public function variadicSignatureSelection(int $i): void
	{
		assertType('int<5, max>', max($i, 5));
		assertType('int<min, 1>', min(1, $i));
	}

	public function echoStatement(string $s): void
	{
		echo $s;
		assertType('string', $s);
	}

	public function elseifConditions(int $i): void
	{
		if ($i > 10) {
			assertType('int<11, max>', $i);
		} elseif ($i > 5) {
			assertType('int<6, 10>', $i);
		} else {
			assertType('int<min, 5>', $i);
		}
	}

	public function firstClassCallable(): void
	{
		$f = strlen(...);
		assertType('Closure(string): int<0, max>', $f);
	}

	public function listAssignment(): void
	{
		[$x, $y] = [1, 'a'];
		assertType('1', $x);
		assertType('\'a\'', $y);
	}

	public function closures(): void
	{
		$fn = function (): int {
			return 1;
		};
		assertType('1', $fn());

		$af = static fn (int $z): int => $z + 1;
		assertType('int', $af(5));
	}

	public function foreachValueAssignment(): void
	{
		foreach ([1, 2, 3] as $val) {
			assertType('1|2|3', $val);
		}
	}

	public function dynamicReturnTypeExtensions(mixed $m): void
	{
		assertType('true', is_int(5));
		assertType('false', is_int('x'));
		assertType('bool', is_int($m));
	}

	/**
	 * intdiv() throw point comes from its DynamicFunctionThrowTypeExtension:
	 * a possibly-zero divisor throws DivisionByZeroError, a non-zero literal cannot.
	 */
	public function dynamicThrowTypeExtensions(int $i, int $j): void
	{
		try {
			intdiv($i, $j);
			$maybe = 1;
		} finally {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $maybe);
		}

		try {
			intdiv($i, 2);
			$certain = 1;
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $certain);
		}
	}

	public function negatedAndEqualityAsserts(): void
	{
		$m = mixedValue();
		assertNotInt($m);
		assertType('mixed~int', $m);

		$n = mixedValue();
		assertSame5($n);
		assertType('5', $n);
	}

	private function name(): string
	{
		return 'x';
	}

	/** @var mixed */
	private $mixedProp;

}

class Holder
{

	public int $count = 0;

	public string $name = '';

	public Holder $inner;

	/** @var mixed */
	public $untyped;

}

function mixedValue(): mixed
{
	return 1;
}

/**
 * @phpstan-assert int $value
 */
function assertInt(mixed $value): void
{
}

/**
 * @return ($i is int<1, max> ? true : false)
 */
function isPositive(int $i): bool
{
	return $i >= 1;
}

/**
 * @phpstan-assert !int $value
 */
function assertNotInt(mixed $value): void
{
}

/**
 * @phpstan-assert =5 $value
 */
function assertSame5(mixed $value): void
{
}
