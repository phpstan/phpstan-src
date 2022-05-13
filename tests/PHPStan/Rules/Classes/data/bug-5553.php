<?php // lint >= 8.0

namespace Bug5553;

use DatePeriod;
use DateTime;

class Foo
{

	public function weekNumberToWorkRange(int $week, int $year): DatePeriod
	{
		$dto = new DateTime();
		$dto->setISODate($year, $week);
		$dto->setTime(8, 0, 0, 0);

		$start = clone $dto;
		$dto->modify('+4 days');
		$end = clone $dto;
		$end->setTime(18, 0, 0, 0);

		return new DatePeriod(
			start: $start,
			interval: new \DateInterval('P1D'),
			end: $end
		);
	}

}
