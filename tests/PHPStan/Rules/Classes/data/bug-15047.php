<?php declare(strict_types = 1);

namespace Bug15047Instantiation;

use stdClass;

abstract class AbstractJsonRepresentation
{

	/**
	 * @param stdClass $data
	 */
	abstract protected static function fromObjectInternal(stdClass $data): self;

}

final class MissalYearLimits extends AbstractJsonRepresentation
{

	private ?int $untilYear;

	private function __construct(?int $untilYear)
	{
		$this->untilYear = $untilYear;
	}

	public function getUntilYear(): ?int
	{
		return $this->untilYear;
	}

	/**
	 * @param stdClass&object{since_year:int,until_year?:int} $data
	 */
	protected static function fromObjectInternal(stdClass $data): self
	{
		// the optional key must keep its declared type through the ?? narrowing,
		// otherwise this reports "expects int|null, mixed given"
		return new self($data->until_year ?? null);
	}

}
