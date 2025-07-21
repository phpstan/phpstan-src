<?php declare(strict_types = 1);

namespace Bug13279;

class ReproduceMe
{
	public function crashIt(string $addMe = ''): string
	{
		$arg = [];
		if ($addMe !== '') {
			$arg[] = $addMe;
		}

		array_splice($arg, 'extra', 1);

		return implode(' ', $arg);
	}
}
