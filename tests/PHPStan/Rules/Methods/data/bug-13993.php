<?php // lint >= 8.0

namespace Bug13993;

use DateTimeImmutable;

/**
 * @template TData
 */
class NightIntervalDataSet
{
	/**
	 * @param list<NightIntervalData<TData>> $intervals
	 */
	final public function __construct(private array $intervals) {}

	/**
	 * @template TInput
	 * @param iterable<TInput> $inputs
	 * @param callable(TInput): array{0: DateTimeImmutable, 1: DateTimeImmutable} $mapper
	 * @param callable(TData, TInput): TData $reducer
	 * @return self<TData>
	 */
	public function modifyDataByStream(iterable $inputs, callable $mapper, callable $reducer): self
	{
		return new static(array_values($this->intervals));
	}

}

/**
 * @template TData
 */
class NightIntervalData
{

	/**
	 * @param TData $data
	 */
	final public function __construct(public DateTimeImmutable $start, public DateTimeImmutable $end, public $data) {}

}

interface Reservation
{
	function getStart(): DateTimeImmutable;
	function getEnd(): DateTimeImmutable;
	function getRoomType(): ?object;

}

/** @var list<Reservation> */
$reservations = [];
$set = new NightIntervalDataSet([new NightIntervalData(
	new DateTimeImmutable('2017-01-01'),
	new DateTimeImmutable('2017-02-28'),
	['roomTypeId' => 'xxx', 'capacity' => 1],
)]);
$set->modifyDataByStream(
	$reservations,
	static fn (Reservation $occupation): array => [$occupation->getStart(), $occupation->getEnd()],
	static function (array $capacityData, Reservation $reservation): array {
		if ($reservation->getRoomType() === null) {
			$capacityData['capacity'] = max(0, $capacityData['capacity'] - 1);

			return $capacityData;
		}

		return $capacityData;
	},
);

