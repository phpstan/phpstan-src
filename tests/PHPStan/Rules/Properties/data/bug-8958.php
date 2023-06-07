<?php declare(strict_types=1); // lint >= 8.1

namespace Bug8958;

interface TimeRangeInterface
{
	public function getStart(): \DateTimeInterface;

	public function getEnd(): \DateTimeInterface;
}

trait TimeRangeTrait
{
	private readonly \DateTimeImmutable $start;

	private readonly \DateTimeImmutable $end;

	public function getStart(): \DateTimeImmutable
	{
		return $this->start; // @phpstan-ignore-line
	}

	public function getEnd(): \DateTimeImmutable
	{
		return $this->end; // @phpstan-ignore-line
	}

	private function initTimeRange(
		\DateTimeInterface $start,
		\DateTimeInterface $end
	): void {
		$this->start = \DateTimeImmutable::createFromInterface($start); // @phpstan-ignore-line
		$this->end = \DateTimeImmutable::createFromInterface($end); // @phpstan-ignore-line
	}
}

class Foo implements TimeRangeInterface {
	use TimeRangeTrait;

	public function __construct(\DateTimeInterface $start, \DateTimeInterface $end)
	{
		$this->initTimeRange($start, $end);
	}
}

class Bar implements TimeRangeInterface {
	use TimeRangeTrait;

	public function __construct(
		private TimeRangeInterface $first,
		private TimeRangeInterface $second,
		?\DateTimeInterface $start = null,
		\DateTimeInterface $end = null
	) {
		$this->initTimeRange(
			$start ?? max($first->getStart(), $second->getStart()),
			$end ?? min($first->getEnd(), $second->getEnd()),
		);
	}

	public function getFirst(): TimeRangeInterface
	{
		return $this->first;
	}
	public function getSecond(): TimeRangeInterface
	{
		return $this->second;
	}
}
