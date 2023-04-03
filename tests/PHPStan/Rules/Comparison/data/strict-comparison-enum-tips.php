<?php // lint >= 8.1

namespace StrictComparisonEnumTips;

enum SomeEnum
{

	case One;
	case Two;

	public function exhaustiveWithSafetyCheck(): int
	{
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
		if ($this === self::One) {
			return -1;
		}

		if ($this === self::Two) {
			return 0;
		}

		throw new \LogicException('New case added, handling missing');
	}

}
