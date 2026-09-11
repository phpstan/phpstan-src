<?php // lint >= 8.0

namespace UnusedVariableRulePhp8;

/** @param mixed $v */
function sink($v): void
{
}

/**
 * @return mixed
 * @phpstan-impure
 */
function source()
{
	return rand();
}

function catchUnused(): void
{
	try {
		sink(1);
	} catch (\Exception $e) { // unused $e
	}
}

function catchUsed(): void
{
	try {
		sink(1);
	} catch (\Exception $e) {
		sink($e);
	}
}

function matchRead(int $i): int
{
	$a = source();
	return match ($i) {
		1 => $a,
		default => 0,
	};
}

function nullsafeRead(?\stdClass $o): void
{
	$x = $o?->foo;
	sink($x);
}

function namedArgs(): void
{
	$v = 1;
	sink(v: $v);
}

class NullsafeReads
{

	public function __construct(private ?\DateTimeImmutable $maxDate, private ?NullsafeReads $inner, private ?\ArrayObject $bag)
	{
	}

	public function argumentOfNullsafeCall(): ?\DateTimeImmutable
	{
		$nightsFrom = 1;

		return $this->maxDate?->modify(sprintf('-%d days', $nightsFrom));
	}

	public function argumentOfNestedNullsafeCall(): void
	{
		$weekDay = 3;
		sink($this->inner?->argumentOfNullsafeCallWith($weekDay));
	}

	public function argumentOfNullsafeCallWith(int $weekDay): int
	{
		return $weekDay;
	}

	public function nullsafeInCondition(): void
	{
		$time = new \DateTimeImmutable();
		if ($this->maxDate?->getTimestamp() === $time->getTimestamp()) {
			sink(1);
		}
	}

	public function nullsafeOffsetOnPropertyFetch(): void
	{
		$key = 'k';
		sink($this->bag?->offsetGet($key));
	}

	public function unreadArgumentVariable(): void
	{
		$nightsFrom = 1; // unused $nightsFrom
		$nightsFrom = 2;
		sink($this->maxDate?->modify(sprintf('-%d days', $nightsFrom)));
	}

}

function matchArmFlow(): void
{
	$a = source(); // unused $a
	$b = match (true) { // unused $b
		default => $a,
	};
}

function matchConditionIsSink(): void
{
	$a = source();
	$b = match ($a) { // unused $b
		default => 1,
	};
}
