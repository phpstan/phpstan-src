<?php declare(strict_types = 1);

namespace Bug11984;

class Foo
{
	/**
	 * @return array<string, mixed>
	 */
	public function loadFromFile(): array
	{
		return ['x' => 1];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function test(): array
	{
		while (true) {
			try {
				$data = $this->loadFromFile();

				break;
			} catch (\Exception $ex) {
			}
		}

		return $data;
	}
}
