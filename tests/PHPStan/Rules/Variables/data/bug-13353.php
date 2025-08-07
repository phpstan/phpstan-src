<?php declare(strict_types = 1);

namespace Bug13353;

$foo = 'bar';

if (!isset($$foo)) {
	echo 'Wololo';
}

echo $$foo;
