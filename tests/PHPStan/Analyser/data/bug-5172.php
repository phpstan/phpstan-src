<?php // lint >= 8.0

namespace Bug5172;

use function PHPStan\Testing\assertType;

class Period
{
	public mixed $from;
	public mixed $to;

	public function year(): ?int
	{
		assertType('mixed', $this->from);
		assertType('mixed', $this->to);

		// let's say $this->from === null && $model->to === null

		if ($this->from?->year !== $this->to?->year) {
			return null;
		}

		assertType('mixed', $this->from);
		assertType('mixed', $this->to);

		return $this->from?->year;
	}
}
