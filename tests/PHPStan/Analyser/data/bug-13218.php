<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13218;

/**
 * @template TSteps of iterable<mixed>|int
 */
class Progress
{
	public mixed $total = 0;

	/**
	 * Create a new ProgressBar instance.
	 *
	 * @param  TSteps  $steps
	 */
	public function __construct(public string $label, public iterable|int $steps, public string $hint = '')
	{
		$this->total = match (true) {
			is_int($this->steps) => $this->steps,
			is_countable($this->steps) => count($this->steps),
			is_iterable($this->steps) => iterator_count($this->steps),
		};
	}
}
