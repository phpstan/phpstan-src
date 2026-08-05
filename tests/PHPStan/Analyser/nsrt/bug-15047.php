<?php declare(strict_types=1);

namespace Bug15047;

use stdClass;
use function PHPStan\Testing\assertType;

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

	/**
	 * @param stdClass&object{since_year:int,until_year?:int} $data
	 */
	protected static function fromObjectInternal(stdClass $data): self
	{
		assertType('object{since_year: int, until_year?: int}&stdClass', $data);
		assertType('int|null', $data->until_year ?? null);
		assertType('int', $data->since_year);

		if (isset($data->until_year)) {
			assertType('object{since_year: int, until_year: int}&stdClass', $data);
			assertType('int', $data->until_year);
		}

		return new self($data->until_year ?? null);
	}

}

/**
 * The member order of the intersection must not change the result: whether the object shape or
 * stdClass is written first, isset()/?? narrowing resolves the optional key to its declared type.
 *
 * @param stdClass&object{u?:int} $stdFirst
 * @param object{u?:int}&stdClass $shapeFirst
 */
function orderIndependent($stdFirst, $shapeFirst): void
{
	assertType('int|null', $stdFirst->u ?? null);
	assertType('int|null', $shapeFirst->u ?? null);
}
