<?php

declare(strict_types=1);

namespace Bug8430;

class B
{
	public function abc(string $a, bool $b): void
	{
		for ($i = 1; $i <= 5; $i++) {
			if (!$b) {
				$arr = ['a' => 1];
			}
			if (!$a && !$b) {
				echo $arr['a'];
			}
		}
	}

	public function def(string $a, bool $b): void
	{
		if (!$b) {
			$arr = ['a' => 1];
		}
		if (!$a && !$b) {
			echo $arr['a'];
		}
	}
}

