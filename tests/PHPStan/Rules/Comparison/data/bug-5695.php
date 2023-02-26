<?php declare(strict_types = 1);

namespace Bug5695;

class HelloWorld
{
	/**
	 * @param array{value: string, weight: int} $data
	 */
	public function sayHello(array $data): void
	{
		if (
			!\is_string($data['value']) || // <-- try commenting this line
			!\is_numeric($data['weight'])
		) {
			echo 'oups';
		} else {
			echo 'good';
		}
	}
}
