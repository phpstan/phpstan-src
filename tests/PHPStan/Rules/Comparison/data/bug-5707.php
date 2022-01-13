<?php

namespace Bug5707;

class Cl
{
	protected function xx(int $maxUtf8Length = 50): string
	{
		$maxUtf8Length = (int) max(20, min($maxUtf8Length, 100));

		for ($l = $maxUtf8Length; $l > 0; --$l) {
		}

		return 'x';
	}
}
