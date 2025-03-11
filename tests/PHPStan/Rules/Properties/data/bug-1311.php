<?php declare(strict_types = 1);

namespace Bug1311;

class HelloWorld
{
	/**
	 * @var array<int, string>
	 */
	private $list = [];

	public function convertList(): void
	{
		$temp = [1, 2, 3];

		for ($i = 0; $i < count($temp); $i++) {
			$temp[$i] = (string) $temp[$i];
		}

		$this->list = $temp;
	}
}

(new HelloWorld())->convertList();
