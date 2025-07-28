<?php declare(strict_types = 1);

namespace Bug4809;

class SomeClass
{
	/**
	 * @param array{
	 *     some?: string,
	 *     another?: string,
	 * } $data
	 * @param string $key
	 * @return void
	 */
	public function someFunction(array $data, string $key): void
	{
		if($key !== 'some' && $key !== 'another') {
			return;
		}

		if(!array_key_exists($key, $data)) {
			return;
		}

		var_dump($data[$key]);
	}
}
