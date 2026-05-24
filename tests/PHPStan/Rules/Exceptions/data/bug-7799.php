<?php

namespace Bug7799;

function foo(): void {
	try {
		trigger_error("hello", E_USER_ERROR);
		print "world\n";
	}
	catch (\Exception $e) {}
	print "reachable\n";
}
