<?php declare(strict_types = 1);

namespace Bug5656;

function okpoValidate(array $values): bool
{
	$c = count($values);
	if (8 !== $c && 10 !== $c) {
		return false;
	}

	$expected = array_pop($values);

	$control = 0;
	for ($start = 1; $start <= 3; $start += 2) {
		$i = $start;
		$control = 0;
		foreach ($values as $v) {
			if ($i > 10) {
				$i = 1;
			}
			$control += $i * $v;
			++$i;
		}

		$control %= 11;

		if (10 !== $control) {
			break;
		}
	}

	if (10 === $control) {
		$control = 0;
	}

	return $expected === $control;
}

$values = [0, 1, 0, 6, 0, 6, 2, 3, 1, 5];
okpoValidate($values);
