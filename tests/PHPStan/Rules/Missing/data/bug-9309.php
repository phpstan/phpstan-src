<?php

namespace Bug9309;

function a(): int {
	declare(ticks=1) {
		return 1;
	}
}
