<?php declare(strict_types = 1);

namespace Bug11545;

function foo(int $max): void {
	$i = 0;
	while (true) {
		if ($i > $max) {
			$result = 'done';
			break;
		}
		++$i;
	}
	print $result;
}

function foo_for(int $max): void {
	for ($i = 0;; ++$i) {
		if ($i > $max) {
			$result = 'done';
			break;
		}
	}
	print $result;
}

function foo_do_while(int $max): void {
	$i = 0;
	do {
		if ($i > $max) {
			$result = 'done';
			break;
		}
		++$i;
	} while (true);
	print $result;
}

function bar(int $max): void {
	while (true) {
		$result = 'done';
		break;
	}
	print $result;
}
