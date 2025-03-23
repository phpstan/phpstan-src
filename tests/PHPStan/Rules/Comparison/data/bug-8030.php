<?php

namespace Bug8030;

function (): ?string
{
	$refs = range('a', 'e');

	while (null !== $ref = array_shift($refs)) {
		if (random_int(0, 1) === 1) {
			return $ref;
		}
	}

	return null;
};
