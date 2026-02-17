<?php declare(strict_types = 1);

namespace Bug3191;

interface Carbon
{
	public function startOfWeek(int $number): void;
}

/**
 * @method startOfWeek()
 */
trait Rounding
{
	public function startOfWeek(int $number): void
	{
		echo $number;
	}
}

class MyCarbon implements Carbon
{
	use Rounding;
}

$class = new MyCarbon();

$class->startOfWeek(1);
