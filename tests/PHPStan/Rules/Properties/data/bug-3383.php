<?php declare(strict_types = 1);

namespace Bug3383;

class HelloWorld
{
	/** @var 0|1|2|3 */
	public $classification = 0;

	public function test(): void {
		$this->classification = random_int(0, 3);
	}
}
