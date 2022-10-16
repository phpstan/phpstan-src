<?php

namespace Bug3370;


class Animal
{
	private const MAP = [
		'a' => 'A',
		'b' => 'B',
		'c' => 'C',
	];

	public function get($value)
	{
		return array_keys(self::MAP, $value) ?: [$value];
	}
}
