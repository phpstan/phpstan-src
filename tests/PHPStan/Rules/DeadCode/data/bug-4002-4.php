<?php declare(strict_types=1);

namespace Bug4002;

if (true) {
	test4();
	exit;

	function test4()
	{
		echo 'inner';
	}
}
