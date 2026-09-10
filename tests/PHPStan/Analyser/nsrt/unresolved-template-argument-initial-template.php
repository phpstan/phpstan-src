<?php // lint >= 8.0

namespace UnresolvedTemplateArgumentInitialTemplate;

use function PHPStan\Testing\assertType;

class DateTime
{

	public function isBefore(DateTime $other): bool
	{
		return true;
	}

	public function isAfter(DateTime $other): bool
	{
		return true;
	}

}

class DateTimeInterval
{

}

class Arr
{

	/**
	 * @param mixed[] $array
	 * @return mixed[]
	 */
	public static function sortComparableValues(array $array): array
	{
		return $array;
	}

}

/**
 * @template TValue
 */
class DateTimeIntervalData
{

	/**
	 * @param TValue $data
	 */
	final public function __construct(private DateTime $start, private DateTime $end, private mixed $data)
	{
	}

	public function getStart(): DateTime
	{
		return $this->start;
	}

	public function getEnd(): DateTime
	{
		return $this->end;
	}

	/** @return TValue */
	public function getData(): mixed
	{
		return $this->data;
	}

	/** @param TValue $otherData */
	public function dataEquals(mixed $otherData): bool
	{
		return $this->data === $otherData;
	}

	/** @param self<TValue>|DateTimeInterval $interval */
	public function intersects(DateTimeInterval|self $interval): bool
	{
		return true;
	}

	/** @param self<TValue>|DateTimeInterval $interval */
	public function touches(DateTimeInterval|self $interval): bool
	{
		return true;
	}

}

/**
 * @template TValue
 */
class DateTimeIntervalDataSet
{

	/** @var list<DateTimeIntervalData<TValue>> */
	public array $intervals = [];

	public function normalize(): void
	{
		/** @var list<DateTimeIntervalData<TValue>> $intervals */
		$intervals = Arr::sortComparableValues($this->intervals);
		assertType('list<UnresolvedTemplateArgumentInitialTemplate\DateTimeIntervalData<TValue (class UnresolvedTemplateArgumentInitialTemplate\DateTimeIntervalDataSet, parameter)>>', $intervals);
		$count = count($intervals) - 1;
		for ($n = 0; $n < $count; $n++) {
			$first = $intervals[$n];
			$second = $intervals[$n + 1];
			if ($first->dataEquals($second->getData()) && ($first->intersects($second) || $first->touches($second))) {
				$intervals[$n + 1] = new DateTimeIntervalData(
					$first->getStart()->isBefore($second->getStart()) ? $first->getStart() : $second->getStart(),
					$first->getEnd()->isAfter($second->getEnd()) ? $first->getEnd() : $second->getEnd(),
					$first->getData(),
				);
				unset($intervals[$n]);
			}
		}
	}

}
