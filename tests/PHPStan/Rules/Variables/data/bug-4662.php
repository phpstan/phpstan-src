<?php

namespace Bug4662Rule;

class Foo
{

	/** @var \DateTimeImmutable|null */
	private $programStartDate;

	public function getProgramStartDate(): ?\DateTimeImmutable
	{
		return $this->programStartDate;
	}

}

function testUnparenthesized(Foo $foo): void
{
	$now = new \DateTimeImmutable('now');

	// Without parentheses, && has higher precedence than =
	// so this parses as: null !== ($programStartDate = (getProgramStartDate() && ($now > $programStartDate)))
	// $programStartDate is used inside the BooleanAnd RHS before it's assigned
	if (
		null !== $programStartDate = $foo->getProgramStartDate()
		&& $now > $programStartDate
	) {
		echo 'ok';
	}
}

function testParenthesized(Foo $foo): void
{
	$now = new \DateTimeImmutable('now');

	// With parentheses, this works correctly - no undefined variable
	if (
		(null !== $programStartDate = $foo->getProgramStartDate())
		&& $now > $programStartDate
	) {
		echo 'ok';
	}
}

function testLogicalAnd(Foo $foo): void
{
	$now = new \DateTimeImmutable('now');

	// 'and' has lower precedence than '=' so this works without extra parentheses
	if (
		null !== $programStartDate = $foo->getProgramStartDate()
		and $now > $programStartDate
	) {
		echo 'ok';
	}
}
