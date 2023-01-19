<?php declare(strict_types = 1);

namespace Bug6776;

class HelloWorld
{
	/**
	 * @param positive-int|null $date
	 */
	public function sayHello(?int $date): void
	{
		if($date !== null && $date < 1){
			throw new \InvalidArgumentException();
		}
	}
}
