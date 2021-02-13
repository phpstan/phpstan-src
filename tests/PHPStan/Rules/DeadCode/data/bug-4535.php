<?php declare(strict_types=1);

namespace Bug4535;

function (): void {
	$Str = '"';

	while (true) {
		switch ($Str) {
			case '"':
				break 2;
		}
	}

	echo "Unreachable?\n";
};
