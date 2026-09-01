<?php

namespace Bug14959;

use function PHPStan\Testing\assertType;

if (1 === preg_match('/^<(\w+)([^>]+?)?/', '<div>hello world</div>', $matches, PREG_OFFSET_CAPTURE)) {
	assertType('int<0, max>', $matches[0][1]);
	assertType('int<0, max>', $matches[1][1]);
	assertType('int<-1, max>|null', $matches[2][1] ?? null);
}
