<?php

namespace Bug4662;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var \DateTimeImmutable|null */
	private $programStartDate;

	public function getProgramStartDate(): ?\DateTimeImmutable
	{
		return $this->programStartDate;
	}

}

function testParenthesized(Foo $foo): void
{
	$now = new \DateTimeImmutable('now');

	// Correct: with parentheses, the assignment happens in the left side of &&
	// and the narrowed variable is available in the right side
	if ((null !== $programStartDate = $foo->getProgramStartDate()) && $now > $programStartDate) {
		assertType('DateTimeImmutable', $programStartDate);
	}
}

function testParenthesizedReversed(Foo $foo): void
{
	$now = new \DateTimeImmutable('now');

	// Correct: reversed comparison style
	if (($programStartDate = $foo->getProgramStartDate()) !== null && $now > $programStartDate) {
		assertType('DateTimeImmutable', $programStartDate);
	}
}

function testWithLogicalAnd(Foo $foo): void
{
	$now = new \DateTimeImmutable('now');

	// 'and' has lower precedence than '=', so this parses as:
	// (null !== ($programStartDate = $foo->getProgramStartDate())) and ($now > $programStartDate)
	if (null !== $programStartDate = $foo->getProgramStartDate() and $now > $programStartDate) {
		assertType('DateTimeImmutable', $programStartDate);
	}
}

function testChained(Foo $foo): void
{
	// Multiple assignments chained with &&
	if ((null !== $a = $foo->getProgramStartDate()) && (null !== $b = $foo->getProgramStartDate()) && $a > $b) {
		assertType('DateTimeImmutable', $a);
		assertType('DateTimeImmutable', $b);
	}
}

function testFalseCheck(string $haystack, string $needle): void
{
	// false !== with assignment and &&
	if ((false !== $pos = strpos($haystack, $needle)) && $pos > 5) {
		assertType('int<6, max>', $pos);
	}
}

function testWithOr(Foo $foo): void
{
	// Using || with === null (the inverse pattern)
	if (($programStartDate = $foo->getProgramStartDate()) === null || $programStartDate->getTimestamp() < 0) {
		assertType('DateTimeImmutable|null', $programStartDate);
	} else {
		assertType('DateTimeImmutable', $programStartDate);
	}
}

function testLogicalOr(Foo $foo): void
{
	// 'or' has lower precedence than '='
	if (null === $programStartDate = $foo->getProgramStartDate() or $programStartDate->getTimestamp() < 0) {
		assertType('DateTimeImmutable|null', $programStartDate);
	} else {
		assertType('DateTimeImmutable', $programStartDate);
	}
}

function testNested(Foo $foo): void
{
	// Assignment result used in chained &&
	if ((null !== $a = $foo->getProgramStartDate()) && ($b = $a->format('Y-m-d')) !== '') {
		assertType('DateTimeImmutable', $a);
		assertType('non-falsy-string', $b);
	}
}

function testAssignInWhile(Foo $foo): void
{
	// Assignment in while condition with &&
	$i = 0;
	while ((null !== $val = $foo->getProgramStartDate()) && $i < 10) {
		assertType('DateTimeImmutable', $val);
		$i++;
	}
}

function testSimpleAssignInCondition(Foo $foo): void
{
	// Simple assignment in condition without &&
	if (null !== $programStartDate = $foo->getProgramStartDate()) {
		assertType('DateTimeImmutable', $programStartDate);
	} else {
		assertType('null', $programStartDate);
	}
	assertType('DateTimeImmutable|null', $programStartDate);
}
