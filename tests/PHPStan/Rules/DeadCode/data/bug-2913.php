<?php

namespace Bug2913;

function (): int {
	do {
		$tier = mt_rand(1, 6); // generate random tier
		switch ($tier) {
			case 6:
				// tier 6 too high for weapons, try generating
				// another random tier value.
				break;
			default:
				break 2;
		}
	} while (true);

	return $tier;
};
