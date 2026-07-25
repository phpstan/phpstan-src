<?php declare(strict_types = 1);

namespace RuleErrorTransformerTabs;

class Bar
{

	public function doBar(int $e, int $f): int
	{
		$g = $e + $f;
		$h = $g * $e;

		return $g + $h;
	}

}
