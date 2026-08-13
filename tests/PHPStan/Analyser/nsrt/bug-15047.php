<?php declare(strict_types = 1);

namespace Bug15047;

use stdClass;
use function PHPStan\Testing\assertType;

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
		assertType('object{since_year: int, until_year?: int}&stdClass', $data);
		assertType('int', $data->since_year);
		assertType('int|null', isset($data->until_year) ? $data->until_year : null);
		assertType('int|null', $data->until_year ?? null);

		// isset() adds a hasProperty() member to the intersection, and resolving the
		// optional key against it must not depend on where that member lands in the
		// member list - which is what this used to be sensitive to.
		if (isset($data->until_year)) {
			assertType('object{since_year: int, until_year: int}&stdClass', $data);
			assertType('int', $data->until_year);
		}

		return new self(isset($data->until_year) ? $data->until_year : null);
	}

}
