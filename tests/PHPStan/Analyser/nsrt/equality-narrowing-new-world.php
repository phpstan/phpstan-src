<?php // lint >= 8.1

declare(strict_types = 1);

namespace EqualityNarrowingNewWorld;

use function PHPStan\Testing\assertType;

class Foo
{

	public const BAR = 'bar';

}

enum Suit: string
{

	case Hearts = 'H';
	case Spades = 'S';

}

class Basics
{

	public function identicalNull(?int $a): void
	{
		if ($a === null) {
			assertType('null', $a);
		} else {
			assertType('int', $a);
		}
		if ($a !== null) {
			assertType('int', $a);
		} else {
			assertType('null', $a);
		}
		if (null === $a) {
			assertType('null', $a);
		} else {
			assertType('int', $a);
		}
	}

	public function identicalLiteral(int $a, string $s): void
	{
		if ($a === 5) {
			assertType('5', $a);
		} else {
			assertType('int<min, 4>|int<6, max>', $a);
		}
		if (5 === $a) {
			assertType('5', $a);
		}
		if ($s === 'foo') {
			assertType("'foo'", $s);
		} else {
			assertType("string", $s);
		}
	}

	public function identicalBool(bool $b, int $i): void
	{
		if ($b === true) {
			assertType('true', $b);
		} else {
			assertType('false', $b);
		}
		if ($b !== false) {
			assertType('true', $b);
		}
		if (($i > 3) === true) {
			assertType('int<4, max>', $i);
		}
		if (($i > 3) === false) {
			assertType('int<min, 3>', $i);
		}
	}

	/**
	 * @param 'a'|'b'|'c' $abc
	 * @param int|string $is
	 */
	public function unionMembers(string $abc, $is): void
	{
		if ($abc === 'b') {
			assertType("'b'", $abc);
		} else {
			assertType("'a'|'c'", $abc);
		}
		if ($is === 'x') {
			assertType("'x'", $is);
		} else {
			assertType('int|string', $is);
		}
	}

	/**
	 * @param int|null $a
	 * @param string|null $b
	 */
	public function bothSidesSpecifiable($a, $b): void
	{
		if ($a === $b) {
			assertType('null', $a);
			assertType('null', $b);
		}
		if ($a !== $b) {
			// nothing certain about either side
			assertType('int|null', $a);
			assertType('string|null', $b);
		}
	}

	public function enumCases(Suit $suit): void
	{
		if ($suit === Suit::Hearts) {
			assertType('EqualityNarrowingNewWorld\Suit::Hearts', $suit);
		} else {
			assertType('EqualityNarrowingNewWorld\Suit::Spades', $suit);
		}
		if ($suit !== Suit::Spades) {
			assertType('EqualityNarrowingNewWorld\Suit::Hearts', $suit);
		}
	}

	public function classConstant(string $s): void
	{
		if ($s === Foo::BAR) {
			assertType("'bar'", $s);
		}
	}

	/** @param array<int> $arr */
	public function countNarrowing(array $arr): void
	{
		if (count($arr) === 0) {
			assertType('array{}', $arr);
		} else {
			assertType('non-empty-array<int>', $arr);
		}
		if (count($arr) === 2) {
			assertType('non-empty-array<int>', $arr);
		}
		if (count($arr) !== 0) {
			assertType('non-empty-array<int>', $arr);
		}
	}

	/**
	 * @param list<string> $list
	 * @param array{a: int, b?: string} $shape
	 * @param array<int> $ints
	 */
	public function countNarrowingShapes(array $list, array $shape, array $ints): void
	{
		if (count($list) === 2) {
			assertType('array{string, string}', $list);
		} else {
			assertType('list<string>', $list);
		}
		if (count($list) !== 1) {
			assertType('list<string>', $list);
		} else {
			assertType('array{string}', $list);
		}
		if (count($shape) === 1) {
			assertType('array{a: int, b?: string}', $shape);
		}
		if (sizeof($ints) === 0) {
			assertType('array{}', $ints);
		}
		if (count($list, COUNT_RECURSIVE) === 2) {
			// non-nested list: recursive count equals normal count
			assertType('array{string, string}', $list);
		}
	}

	public function strlenNarrowing(string $s): void
	{
		if (strlen($s) === 0) {
			assertType("''", $s);
		} else {
			assertType('non-empty-string', $s);
		}
		if (strlen($s) !== 0) {
			assertType('non-empty-string', $s);
		}
		if (strlen($s) === 1) {
			assertType('non-empty-string', $s);
		}
		if (strlen($s) === 2) {
			assertType('non-falsy-string', $s);
		}
		if (mb_strlen($s) === 0) {
			assertType("''", $s);
		} else {
			assertType('non-empty-string', $s);
		}
	}

	public function substrFamilyNarrowing(string $s): void
	{
		if (substr($s, 0, 3) === 'foo') {
			assertType('non-falsy-string', $s);
		}
		if (strtolower($s) === 'abc') {
			assertType('non-falsy-string', $s);
		}
		if (strtoupper($s) === '0') {
			assertType('non-empty-string', $s);
		}
		if (ucfirst($s) === 'Foo') {
			assertType('non-falsy-string', $s);
		} else {
			assertType('string', $s);
		}
	}

	/** @param mixed $m */
	public function trimAndParentClass(string $s, object $o, $m): void
	{
		if (trim($s) !== '') {
			assertType('non-empty-string', $s);
		}
		if (ltrim($s) === '') {
			assertType('string', $s);
		} else {
			assertType('non-empty-string', $s);
		}
		if (get_parent_class($o) === Foo::class) {
			assertType('EqualityNarrowingNewWorld\Foo', $o);
		}
		if (get_parent_class($m) === Foo::class) {
			assertType('class-string<EqualityNarrowingNewWorld\Foo>|EqualityNarrowingNewWorld\Foo', $m);
		}
	}

	public function getClassNarrowing(object $o): void
	{
		if (get_class($o) === Foo::class) {
			assertType('EqualityNarrowingNewWorld\Foo', $o);
		} else {
			assertType('object', $o);
		}
		if (Foo::class === get_class($o)) {
			assertType('EqualityNarrowingNewWorld\Foo', $o);
		}
		if (get_debug_type($o) === Foo::class) {
			assertType('EqualityNarrowingNewWorld\Foo', $o);
		}
		if (get_class($o) !== Foo::class) {
			assertType('object', $o);
		} else {
			assertType('EqualityNarrowingNewWorld\Foo', $o);
		}
	}

	/**
	 * @param int|string $is
	 * @param mixed $m
	 */
	public function gettypeNarrowing($is, $m): void
	{
		if (gettype($is) === 'string') {
			assertType('string', $is);
		} else {
			assertType('int', $is);
		}
		if (gettype($m) === 'NULL') {
			assertType('null', $m);
		}
		if (gettype($is) !== 'integer') {
			assertType('string', $is);
		} else {
			assertType('int', $is);
		}
		if (gettype($m) === 'double') {
			assertType('float', $m);
		}
	}

	public function pregMatchNarrowing(string $s): void
	{
		if (preg_match('/^a(b)c$/', $s, $matches) === 1) {
			assertType("array{non-falsy-string, 'b'}", $matches);
		}
		if (1 === preg_match('/^a(b)c$/', $s, $matches2)) {
			assertType("array{non-falsy-string, 'b'}", $matches2);
		}
		if (preg_match('/^a(b)c$/', $s, $matches3) === 0) {
			assertType("array{}|array{non-falsy-string, 'b'}", $matches3);
		}
	}

	/**
	 * @param 5 $five
	 * @param int|string $is
	 * @param Suit $suit
	 * @param Suit $otherSuit
	 * @param array{a: int}|null $arrOrNull
	 * @param array{a: int}|false $arrOrFalse
	 */
	public function generalExprVsExpr($five, $is, Suit $suit, Suit $otherSuit, $arrOrNull, $arrOrFalse, ?int $ni): void
	{
		if ($is === $five) {
			assertType('5', $is);
		} else {
			assertType('int<min, 4>|int<6, max>|string', $is);
		}
		if ($suit === $otherSuit) {
			assertType('EqualityNarrowingNewWorld\\Suit', $suit);
		} else {
			assertType('EqualityNarrowingNewWorld\\Suit', $suit);
		}
		if ($arrOrNull === $ni) {
			assertType('null', $arrOrNull);
			assertType('null', $ni);
		}
		if ($arrOrFalse === $arrOrNull) {
			assertType('array{a: int}', $arrOrFalse);
			assertType('array{a: int}', $arrOrNull);
		}
	}

	/**
	 * @param int<2, 3> $smallSize
	 * @param 0 $zero
	 * @param 'string' $stringName
	 * @param list<string> $list
	 * @param mixed $m
	 */
	public function typeBasedConstantSides(array $list, string $s, int $smallSize, int $zero, string $stringName, $m): void
	{
		if (count($list) === $smallSize) {
			assertType('array{0: string, 1: string, 2?: string}', $list);
		}
		if (count($list) === $zero) {
			assertType('array{}', $list);
		}
		if (strlen($s) === $smallSize) {
			assertType('non-falsy-string', $s);
		}
		if (gettype($m) === $stringName) {
			assertType('string', $m);
		} else {
			assertType('mixed~string', $m);
		}
	}

	public function classConstFetchNarrowing(object $o): void
	{
		if ($o::class === Foo::class) {
			assertType('EqualityNarrowingNewWorld\\Foo', $o);
		} else {
			assertType('object', $o);
		}
		if (Foo::class === $o::class) {
			assertType('EqualityNarrowingNewWorld\\Foo', $o);
		}
		if ($o::class !== Foo::class) {
			assertType('object', $o);
		} else {
			assertType('EqualityNarrowingNewWorld\\Foo', $o);
		}
		if ($o::class === 'EqualityNarrowingNewWorld\\Foo') {
			assertType('object', $o);
		}
	}

	/** @param mixed $m */
	public function looseEquality($m, ?string $s): void
	{
		if ($m == null) {
			assertType("0|0.0|''|array{}|false|null", $m);
		} else {
			assertType("mixed~(0|0.0|''|array{}|false|null)", $m);
		}
		if ($s == false) {
			assertType("''|'0'|null", $s);
		} else {
			assertType('non-falsy-string', $s);
		}
		if ($s != null) {
			assertType('non-empty-string', $s);
		} else {
			assertType("''|null", $s);
		}
	}

	/**
	 * @param int|string $is
	 * @param array<int> $arr
	 * @param 'a'|'b' $ab
	 */
	public function moreLooseEquality(?bool $nb, $is, string $s, array $arr, string $ab, Suit $suit, Suit $otherSuit): void
	{
		if ($nb == true) {
			assertType('true', $nb);
		} else {
			assertType('false|null', $nb);
		}
		if ($is == 0) {
			assertType('0|string', $is);
		} else {
			assertType('int<min, -1>|int<1, max>|string', $is);
		}
		if ($is == '') {
			assertType("0|''", $is);
		} else {
			assertType('int|non-empty-string', $is);
		}
		if ($s == 'foo') {
			assertType("'foo'", $s);
		}
		if ($ab == 'a') {
			assertType("'a'", $ab);
		} else {
			assertType("'b'", $ab);
		}
		if ($arr == []) {
			assertType('array{}', $arr);
		} else {
			assertType('non-empty-array<int>', $arr);
		}
		if (gettype($is) == 'string') {
			assertType('string', $is);
		} else {
			assertType('int', $is);
		}
		if ($suit == $otherSuit) {
			assertType('EqualityNarrowingNewWorld\\Suit', $suit);
		}
	}

	/**
	 * @param int|string $a
	 */
	public function narrowAgainstExpression($a, int $b): void
	{
		if ($a === $b) {
			assertType('int', $a);
		} else {
			assertType('int|string', $a);
		}
	}

	public function nestedInBoolean(?int $a, ?string $b): void
	{
		if ($a !== null && $b !== null) {
			assertType('int', $a);
			assertType('string', $b);
		}
		if ($a === null || $b === null) {
			assertType('int|null', $a);
		} else {
			assertType('int', $a);
			assertType('string', $b);
		}
	}

	/** @var self|null */
	private $selfOrNull;

	public function propertyChain(): void
	{
		if ($this->selfOrNull !== null) {
			assertType('EqualityNarrowingNewWorld\Basics', $this->selfOrNull);
		}
	}

	public function assignInCondition(?int $a): void
	{
		if (($b = $a) !== null) {
			assertType('int', $b);
			assertType('int', $a);
		}
	}

	public function flag(): bool
	{
		return true;
	}

	/** @param array<int>|false $arrOrFalse */
	public function boolConstAgainstExpressions(?self $s, $arrOrFalse, ?bool $nb): void
	{
		if ($s?->flag() === false) {
			assertType('EqualityNarrowingNewWorld\Basics', $s);
		} else {
			assertType('EqualityNarrowingNewWorld\Basics|null', $s);
		}
		if ($s?->flag() === true) {
			assertType('EqualityNarrowingNewWorld\Basics', $s);
		}
		if ($arrOrFalse !== false) {
			assertType('array<int>', $arrOrFalse);
		} else {
			assertType('false', $arrOrFalse);
		}
		if ($nb === true) {
			assertType('true', $nb);
		} else {
			assertType('false|null', $nb);
		}
		if ($nb !== false) {
			assertType('true|null', $nb);
		} else {
			assertType('false', $nb);
		}
	}

	/** @param list<string> $list */
	public function funcCallAgainstNull(array $list): void
	{
		if (array_key_first($list) !== null) {
			assertType('non-empty-list<string>', $list);
		}
		if (($key = array_key_first($list)) !== null) {
			assertType('int<0, max>', $key);
			assertType('non-empty-list<string>', $list);
			assertType('string', $list[$key]);
		}
		if (array_key_first($list) === null) {
			assertType('array{}', $list);
		} else {
			assertType('non-empty-list<string>', $list);
		}
		if (array_key_last($list) !== null) {
			assertType('non-empty-list<string>', $list);
		}
		if (array_find_key($list, static fn (string $v): bool => $v !== '') !== null) {
			assertType('non-empty-list<string>', $list);
		} else {
			// an empty find result does not mean an empty array
			assertType('list<string>', $list);
		}
	}

}
