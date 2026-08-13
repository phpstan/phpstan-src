<?php declare(strict_types = 1);

namespace Bug15047Instantiation;

use stdClass;

final class MissalYearLimits
{

	private ?int $until_year;

	private function __construct(?int $until_year)
	{
		$this->until_year = $until_year;
	}

	/**
	 * @param stdClass&object{since_year:int,until_year?:int} $data
	 */
	protected static function fromObjectInternal(stdClass $data): self
	{
		// the optional key must keep its declared type through the isset() narrowing,
		// otherwise this reports "expects int|null, mixed given"
		return new self(isset($data->until_year) ? $data->until_year : null);
	}

	public function getUntilYear(): ?int
	{
		return $this->until_year;
	}

}
