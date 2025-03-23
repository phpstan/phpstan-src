<?php declare(strict_types = 1);

namespace Bug7937;

class HelloWorld
{
	public function f(int $a, int $b)
	{
		if (!$a && !$b) {
			return "";
		}

		$output = " ";

		if ($a) {
			$output .= "$a";
		}

		\PHPStan\dumpType($a);

		if ($b) {
			\PHPStan\dumpType($a);

			if (!$a) {
				$output .= "_";
			}
		}

		return $output;
	}
}
