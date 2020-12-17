<?php

namespace Bug1306;

function bar($foo = null) {
	if ($foo !== null) {
		$someBoolean = false;
	}

	if ($foo !== null && $someBoolean === false) {
	}
}
