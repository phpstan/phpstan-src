<?php

namespace Bug9946;

class Foo
{

	function test(?\DateTimeImmutable $a, ?string $b): string
	{
		if (!$a && !$b) {
			throw new \LogicException('Either a or b MUST be set');
		}
		if (!$a) {
			$c = new \DateTimeImmutable($b);
		}
		$a ??= new \DateTimeImmutable($b);

		return $a->format('c');
	}

}
