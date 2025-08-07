<?php declare(strict_types = 1);

$foo = 'bar';

if (!isset($$foo)) {
	echo 'Wololo';
}

echo $$foo;
