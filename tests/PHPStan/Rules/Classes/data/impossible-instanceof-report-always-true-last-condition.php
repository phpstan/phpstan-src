<?php

namespace ImpossibleInstanceOfReportAlwaysTrueLastCondition;

class Foo
{

	public function doFoo(\Exception $e)
	{
		if (rand(0, 1)) {

		} elseif ($e instanceof \Exception) {

		}
	}

	public function doBar(\Exception $e)
	{
		if (rand(0, 1)) {

		} elseif ($e instanceof \Exception) {

		} else {

		}
	}

	public function cloneDateTime(\DateTimeInterface $dateTime): \DateTimeImmutable
	{
		if ($dateTime instanceof \DateTimeImmutable) {
			return $dateTime;
		}

		if ($dateTime instanceof \DateTime) {
			return \DateTimeImmutable::createFromMutable($dateTime);
		}

		throw new \LogicException('Unknown class of DateTimeInterface implementation.');
	}

}
