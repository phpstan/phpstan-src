<?php

namespace Bug10086;

$a = $b ?? (static fn(): string =>
match($_GET['x']) {
	'x' => 'y',
	default => 'z',
}
)();

define('x', $a);
