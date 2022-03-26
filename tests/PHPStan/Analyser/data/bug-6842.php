<?php declare(strict_types = 1);

namespace Bug6842;

class HelloWorld
{
	/**
	 * @template T of \DateTimeInterface|\DateTime|\DateTimeImmutable
	 *
	 * @param T $startDate
	 * @param T $endDate
	 *
	 * @return \Iterator<T>
	 */
	public function getScheduledEvents(
		\DateTimeInterface $startDate,
		\DateTimeInterface $endDate
	): \Iterator {
		$interval = \DateInterval::createFromDateString('1 day');

		/** @var \Iterator<T> $datePeriod */
		$datePeriod = new \DatePeriod($startDate, $interval, $endDate);

		foreach ($datePeriod as $dateTime) {
			$scheduledEvent = $this->createScheduledEventFromSchedule($dateTime);

			if ($scheduledEvent >= $startDate) {
				yield $scheduledEvent;
			}
		}
	}

	/**
	 * @template T of \DateTimeInterface
	 *
	 * @param T|\DateTime|\DateTimeImmutable $dateTime
	 *
	 * @return T|\DateTime|\DateTimeImmutable
	 */
	protected function createScheduledEventFromSchedule(
		\DateTimeInterface $dateTime
	): \DateTimeInterface {
		return $dateTime;
	}
}
