<?php declare(strict_types = 1);

namespace Bug7799;

function foo(): void {
	try {
		trigger_error("hello", E_USER_ERROR);
		print "world\n";
	}
	catch (\Exception $e) {}
	print "reachable\n";
}

function bar(): void {
	try {
		trigger_error("hello", E_USER_ERROR);
	}
	catch (\Throwable $e) {}
	print "reachable\n";
}

function baz(): void {
	try {
		user_error("hello", E_USER_ERROR);
		print "world\n";
	}
	catch (\Exception $e) {}
	print "reachable\n";
}
