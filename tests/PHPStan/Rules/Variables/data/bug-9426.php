<?php

namespace Bug9426;

final class A
{
	/**
	 * @param array{something?: string} $a
	 */
	public function something(array $a): void
	{
		if (isset($a['something'])) {
			$b = new DateTimeImmutable();
		}

		$c = [
			!isset($a['something']) ? 'something' : $b,
		];
	}
}
