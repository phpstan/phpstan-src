<?php declare(strict_types = 1);

namespace Bug7799Unreachable;

function foo(): void {
	try {
		trigger_error("hello", E_USER_ERROR);
		print "world\n";
	}
	catch (\Exception $e) {}
	print "reachable\n";
}

function bar(): void {
	trigger_error("hello", E_USER_ERROR);
	print "reachable\n";
}

function baz(): void {
	user_error("hello", E_USER_ERROR);
	print "reachable\n";
}
