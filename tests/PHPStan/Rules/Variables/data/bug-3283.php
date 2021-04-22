<?php

namespace Bug3283;

$test = random_int(0, 10);

if ($test > 5) {
	$user = new \stdClass;
	$user->name = 'Jane';
}

echo $user->name ?? 'Default';