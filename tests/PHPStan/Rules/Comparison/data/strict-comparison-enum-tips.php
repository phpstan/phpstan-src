<?php // lint >= 8.1

namespace StrictComparisonEnumTips;

enum SomeEnum
{

	case One;
	case Two;

	public function exhaustiveWithSafetyCheck(): int
	{
		// not reported by this rule at all
		if ($this === self::One) {
			return -1;
		} elseif ($this === self::Two) {
			return 0;
		} else {
			throw new \LogicException('New case added, handling missing');
		}
	}


	public function exhaustiveWithSafetyCheck2(): int
	{
		// not reported by this rule at all
		if ($this === self::One) {
			return -1;
		}

		if ($this === self::Two) {
			return 0;
		}

		throw new \LogicException('New case added, handling missing');
	}

	public function exhaustiveWithSafetyCheckInMatchAlready(): int
	{
		// not reported by this rule at all
		return match ($this) {
			self::One => -1,
			self::Two => 0,
			default => throw new \LogicException('New case added, handling missing'),
		};
	}

	public function exhaustiveWithSafetyCheckInMatchAlready2(self $self): int
	{
		return match (true) {
			$self === self::One => -1,
			$self === self::Two => 0,
			default => throw new \LogicException('New case added, handling missing'),
		};
	}

}
