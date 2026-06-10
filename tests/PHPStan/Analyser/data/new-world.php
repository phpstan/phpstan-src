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

	public function nullsafeMethodCalls(Holder $definite, ?Holder $maybe): void
	{
		assertType('int', $definite?->getCount());
		assertType('int|null', $maybe?->getCount());

		if ($maybe?->getCount()) {
			assertType('NewWorldTypeInference\\Holder', $maybe);
			assertType('int<min, -1>|int<1, max>', $maybe->getCount());
		} else {
			assertType('NewWorldTypeInference\\Holder|null', $maybe);
		}

		assertType('int|null', $maybe?->inner->getCount());
		assertNativeType('int|null', $maybe?->getCount());
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

	/**
	 * Single-pass composition through a chain deeper than the old
	 * BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH = 4: each right operand is evaluated
	 * on the left-truthy scope, so the chain composes linearly with no re-walk.
	 */
	public function deepBooleanAndChain(bool $a, bool $b, bool $c, bool $d, bool $e, bool $f): void
	{
		if ($a && $b && $c && $d && $e && $f) {
			assertType('true', $a);
			assertType('true', $f);
			assertType('true', $a && $b && $c && $d && $e && $f);
		}
	}

	public function deepBooleanOrChain(?int $a, ?int $b, ?int $c, ?int $d, ?int $e, ?int $f): void
	{
		if ($a || $b || $c || $d || $e || $f) {
			assertType('bool', $a || $b || $c || $d || $e || $f);
		} else {
			assertType('0|null', $a);
			assertType('0|null', $f);
		}
	}

	public function booleanConstantFolding(bool $b): void
	{
		assertType('true', 1 && 1);
		assertType('bool', 1 && $b);
		assertType('bool', 0 || $b);
		assertType('true', 1 || $b);
		assertType('false', $b && 0);
	}

	/** the right operand sees the left-truthy/left-falsey scope */
	public function booleanInsideOutNarrowing(?bool $a, bool $b): void
	{
		if ($a && $b) {
			assertType('true', $a);
			assertType('true', $b);
		}
		if ($a || $b) {
			assertType('bool|null', $a);
		} else {
			assertType('false|null', $a);
			assertType('false', $b);
		}
	}

	/**
	 * The truthy scope of `A && B` is composed incrementally from the right
	 * operand's truthy scope — re-deriving the whole conjunction would union
	 * per-arm types and drift the representation (array<mixed> vs the
	 * expected array<mixed, mixed> from is_array()).
	 */
	public function booleanAndNarrowingRepresentation(mixed $m): void
	{
		if ($m != 0 && !is_array($m) && $m != null && !is_object($m)) {
			assertType("mixed~(0|0.0|''|'0'|array<mixed, mixed>|object|false|null)", $m);
		}
	}

	/**
	 * The falsey scope of `A && B` comes from the specify callback: narrowing
	 * originals must be the pre-condition types (per-base adapter seeding) —
	 * the remembered is_bool() narrowing of the truthy branch must not leak.
	 */
	public function booleanAndFalseyOriginals(Holder $h): void
	{
		if (is_bool($h->untyped) && $h->untyped) {
			assertType('true', $h->untyped);
		} else {
			assertType('mixed~true', $h->untyped);
		}
		assertType('mixed', $h->untyped);
	}

	/**
	 * A dynamic-name call as a boolean operand: its narrowing ask must not
	 * bounce between the adapter head-check and the FuncCall specify callback
	 * (the dynamic-name bridge invokes the old-world body directly).
	 *
	 * @param callable(): bool $f
	 */
	public function dynamicNameCallInCondition(callable $f, ?int $i): void
	{
		if ($i !== null && $f()) {
			assertType('int', $i);
		}
	}

	public function booleanOrShortcutNarrowing(bool $b, bool $c): void
	{
		if (0 || $b) {
			assertType('true', $b);
		}
		if ($b || 0) {
			assertType('true', $b);
		}
		if (($b || $c) && 1) {
			assertType('bool', $b);
		}
	}

	public function booleanStatementNullContext(bool $a, bool $b): void
	{
		$a && $b;
		$a || $b;
		assertType('bool', $a);
	}

	public function booleanOrInsideAndFalsey(?int $a, ?int $b, bool $c): void
	{
		if (($a || $b) && $c) {
			assertType('true', $c);
		} else {
			assertType('int|null', $a);
		}
	}

	public function booleanOrUnmigratedArm(?int $a, bool $b): void
	{
		if (!$a || $b) {
			assertType('int|null', $a);
		} else {
			assertType('int<min, -1>|int<1, max>', $a);
		}
	}

	/** @param array<string, int> $arr */
	public function booleanIssetHolderRederivation(array $arr): void
	{
		$ok = isset($arr['a']) && isset($arr['b']);
		if ($ok) {
			assertType('int', $arr['a']);
			assertType('int', $arr['b']);
		}
	}

	public function booleanOverwriteArm(string $s, bool $b): void
	{
		if (in_array($s, ['a', 'b'], true) && $b) {
			assertType("'a'|'b'", $s);
		}
	}

	/** constant folds asked through a parent boolean's specify callback */
	public function booleanFoldsViaParentAsks(bool $b, bool $c): void
	{
		if (($b && 0) || $c) {
			assertType('true', $c);
		}
		if (($b || 1) || $c) {
			assertType('bool', $c);
		}
		if ((0 || 0) || $c) {
			assertType('true', $c);
		}
	}

	/** a negated exactly-true ask drives the mixed truthy-and-false context */
	public function booleanNegatedExactContext(mixed $m, bool $b): void
	{
		if (!($m instanceof Holder && $b) === false) {
			assertType(Holder::class, $m);
			assertType('bool', $b);
		}
	}

	/**
	 * Each ternary branch was evaluated on the matching cond-narrowed scope —
	 * the result type composes from the branch results (no cond re-processing).
	 */
	public function ternaryBasics(bool $b, ?int $a): void
	{
		assertType('1|2', $b ? 1 : 2);
		assertType("'x'|int<min, -1>|int<1, max>", $a ?: 'x');
		assertType("'a'", 1 ? 'a' : 'b');
		assertType("'b'", 0 ? 'a' : 'b');
		assertType('int<min, -1>|int<1, max>', $a ? $a : 5);
	}

	/** ternary narrowing: the synthetic (cond && if) || (!cond && else) */
	public function ternaryAsCondition(?bool $b, ?int $c): void
	{
		if ($b ? $c : 0) {
			assertType('true', $b);
			assertType('int<min, -1>|int<1, max>', $c);
		}
	}

	public function ternaryStatementNullContext(bool $b): void
	{
		$b ? 1 : 2;
		assertType('bool', $b);
	}

	/**
	 * Conditional-expression holders projected from a ternary assignment:
	 * pinning one boolean value pins the recorded cond narrowing.
	 */
	public function ternaryAssignConditionalHolders(mixed $m): void
	{
		$flag = $m instanceof Holder ? 1 : 0;
		if ($flag === 1) {
			assertType(Holder::class, $m);
		} else {
			assertType('mixed~'.Holder::class, $m);
		}
	}

	public function booleanNotFolds(bool $b, ?int $a): void
	{
		assertType('false', !1);
		assertType('true', !0);
		assertType('bool', !$b);
		if (!$a) {
			assertType('0|null', $a);
		} else {
			assertType('int<min, -1>|int<1, max>', $a);
		}
		assertType('bool', !!$b);
	}

	/**
	 * `!$i?->isA()` falsey = the nullsafe truthy scope: the plain call's
	 * type-specifying extensions (assert-if-true) compose into the nullsafe
	 * narrowing (bug-12866 regression).
	 */
	public function nullsafeAssertIfTrueNarrowing(?AssertingInterface $i): void
	{
		if (!$i?->isA()) {
			return;
		}

		assertType(AssertedClass::class, $i);
	}

	public function ternaryShortFoldsAndNative(?int $a): void
	{
		assertType('1', 1 ?: 'x');
		assertType("'x'", 0 ?: 'x');
		assertNativeType("'x'|int<min, -1>|int<1, max>", $a ?: 'x');
	}

	public function ternaryShortAsCondition(?int $a, ?int $b): void
	{
		if ($a ?: $b) {
			assertType('int|null', $a);
		} else {
			assertType('0|null', $a);
			assertType('int|null', $b);
		}
	}

	public function booleanNotStatementNullContext(bool $b): void
	{
		!$b;
		assertType('bool', $b);
	}

	/** untracked compound entries in projected ternary-assign holders */
	public function ternaryAssignUntrackedEntries(Holder $h): void
	{
		$flag = is_int($h->untyped) ? 1 : 0;
		if ($flag === 1) {
			assertType('int', $h->untyped);
		}
	}

	/**
	 * Statement-level condition handling goes through the conditions'
	 * ExpressionResults (NodeScopeResolver getType sweep): elseif chains,
	 * loop conditions and exits, foreach value/key, switch exhaustiveness.
	 */
	public function statementIfElseIfChain(bool $a, bool $b): void
	{
		if ($a) {
			assertType('true', $a);
		} elseif ($b) {
			assertType('false', $a);
			assertType('true', $b);
		} else {
			assertType('false', $b);
		}
	}

	public function statementWhile(?int $i): void
	{
		while ($i) {
			assertType('int<min, -1>|int<1, max>', $i);
			$i = 0;
		}
		// the test config has polluteScopeWithLoopInitialAssignments=false,
		// so the loop-exit merge keeps the tight 0|null
		assertType('0|null', $i);
	}

	public function statementDoWhile(bool $b): void
	{
		do {
			$x = 1;
		} while ($b);
		assertType('1', $x);
	}

	public function statementFor(bool $b): void
	{
		for ($j = 0; $b; $j++) {
			assertType('true', $b);
		}
		assertType('int<0, max>', $j);
	}

	public function statementAlwaysTrueWhile(): void
	{
		$k = 0;
		while (1) {
			$k++;
			if ($k) {
				break;
			}
		}
		assertType('1', $k);
	}

	/** @param non-empty-array<int, string> $items */
	public function statementForeach(array $items): void
	{
		foreach ($items as $key => $value) {
			assertType('int', $key);
			assertType('string', $value);
		}
	}

	public function statementSwitchDefault(int $i): void
	{
		switch ($i) {
			default:
				assertType('int', $i);
		}
	}

	public function constFetchLiterals(bool $b): void
	{
		assertType('true', true);
		assertType('false', false);
		assertType('null', null);
		assertType('2147483647|9223372036854775807', PHP_INT_MAX);
		assertType('bool', $b && true);
		assertType('bool', $b || false);
		assertType('1', true ? 1 : 2);
		assertType('2', false ?: 2);
		if ($b !== false) {
			assertType('true', $b);
		}
	}

	public function unaryMinus(int $i, float $f): void
	{
		assertType('-5', -5);
		assertType('int', -$i);
		assertType('float', -$f);
		assertType('7', -(-7));
		assertType('bool', (bool) -$i);
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

	public function getCount(): int
	{
		return $this->count;
	}

	/** @var mixed */
	public $untyped;

}

interface AssertingInterface
{

	/**
	 * @phpstan-assert-if-true AssertedClass $this
	 */
	public function isA(): bool;

}

class AssertedClass implements AssertingInterface
{

	public function isA(): bool
	{
		return true;
	}

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
