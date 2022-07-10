<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug6647;

class HelloWorld
{
	public ?int $test1;
	public ?int $test2;

	public function getStatusAttribute(): string
	{
		$compare_against = [
			't1' => !is_null($this->test1),
			't2' => !is_null($this->test2),
		];

		$map = fn(bool $t1, bool $t2) => [
			't1' => $t1,
			't2' => $t2,
		];

		return match($compare_against) {
			$map(true, false) => 'abc',
			$map(false, true) => 'def',

			default =>
				throw new RuntimeException("Unknown status: " . json_encode($compare_against)),
		};
	}
}
