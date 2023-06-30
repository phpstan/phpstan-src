<?php

namespace Bug5326;

function normalize(string $value): string
{
	switch (true) {
		case strpos($value, 'get_') === 0 && preg_match('/get_(\S+)/m', $value, $match) === 1:
			return $match[1];
		default:
			return $value;
	}
}
