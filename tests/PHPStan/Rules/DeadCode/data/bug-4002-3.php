<?php declare(strict_types=1);

namespace Bug4002;

test3();
exit;

function test3()
{
	echo 'hello';
}

echo 'unreachable';
