<?php declare(strict_types=1);

namespace Bug7522;

function doFoo() {
	// Example #2 from https://www.php.net/manual/en/function.ob-start.php
	\ob_start(null, 0, PHP_OUTPUT_HANDLER_STDFLAGS ^ PHP_OUTPUT_HANDLER_REMOVABLE);
}
