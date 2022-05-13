<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7048;

class HelloWorld
{
	public function sayHello(): \DatePeriod
	{
		return new \DatePeriod(
			start: new \DateTime('now'),
			interval: new \DateInterval('P1D'),
			end: new \DateTime('now + 3 days'),
		);
	}

	public function doFoo(): void
	{
		new \DatePeriod(
			start: new \DateTime('now'),
			interval: new \DateInterval('P1D'),
			recurrences: 5,
		);

		new \DatePeriod(
			isostr: 'R4/2012-07-01T00:00:00Z/P7D'
		);
	}

	public function allValid(): void
	{
		$start = new \DateTime('2012-07-01');
		$interval = new \DateInterval('P7D');
		$end = new \DateTime('2012-07-31');
		$recurrences = 4;
		$iso = 'R4/2012-07-01T00:00:00Z/P7D';


		$period = new \DatePeriod($start, $interval, $recurrences);
		$period = new \DatePeriod($start, $interval, $end);
		$period = new \DatePeriod($iso);
	}
}
