<?php // lint >= 8.0

namespace Bug14368;

final class Snowflake
{
	public function __construct(
		private readonly int $value,
	) {}

	public static function cast(int $snowflake): self
	{
		return new self($snowflake);
	}

	public function equals(?self $other): bool
	{
		return $this->value === $other?->value;
	}
}

final class BalanceId
{
	public static function work(): Snowflake
	{
		/** @var Snowflake */
		static $work = Snowflake::cast(1);
		return $work;
	}

	public static function holiday(): Snowflake
	{
		/** @var Snowflake */
		static $holiday = Snowflake::cast(2);
		return $holiday;
	}
}

function test(Snowflake $balanceId): void
{
	// First match — no error expected
	$a = match (true) {
		$balanceId->equals(BalanceId::work()) => -1.0,
		$balanceId->equals(BalanceId::holiday()) => 1.0,
		default => throw new \InvalidArgumentException(),
	};

	// Second match — should not report match.alwaysTrue
	$b = match (true) {
		$balanceId->equals(BalanceId::work()) => -2.0,
		$balanceId->equals(BalanceId::holiday()) => 2.0,
		default => throw new \InvalidArgumentException(),
	};
}
