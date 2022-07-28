<?php declare(strict_types=1);

namespace Bug1016;

function () {
	foreach ([1, 2, 3] as $i) {
		switch ($i) {
			case 1:
				$a = "hello";
				break;
			case 2:
				$a = "goodbye";
				break;
			case 3:
				$a = "hello again";
				break;
		}

		echo $a;
	}
};
