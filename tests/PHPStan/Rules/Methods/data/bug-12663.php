<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug12663;

final class Example
{
	/**
	 * @param-out string $string
	 */
	public function generate(mixed &$string): self
	{
		$string = random_bytes(8);

		return $this;
	}

	public function dump(string $string): self
	{
		var_dump($string);

		return $this;
	}
}

(new Example)
	->dump($string1 = 'abc')
	->dump($string1);

(new Example)
	->generate($string2)
	->dump($string2);
