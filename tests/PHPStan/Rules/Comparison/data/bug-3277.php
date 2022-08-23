<?php declare(strict_types = 1);

namespace Bug3277;

function (): void {
	for($i = 5; $i < 4; $i++) {
		loop();
	}

	function loop() : void {
		echo 'Looping';
	}
};
