<?php

namespace Bug3283;

function (): void
{
	$test = random_int(0, 10);

	if ($test > 5) {
		$user = new \stdClass;
		$user->name = 'Thibaud';
	}

	echo $user->name ?? 'Default';
};
