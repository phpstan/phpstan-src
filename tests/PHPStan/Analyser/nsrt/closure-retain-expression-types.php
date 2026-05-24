<?php

namespace ClosureRetainExpressionTypes;

use function class_exists;
use function interface_exists;
use function enum_exists;
use function trait_exists;
use function function_exists;
use function PHPStan\Testing\assertType;

function () {
	assertType('bool', function_exists('foo123'));
	if (function_exists('foo123')) {
		assertType('true', function_exists('foo123'));
		function () {
			assertType('true', function_exists('foo123'));
		};
	} else {
		assertType('false', function_exists('foo123'));
		function () {
			assertType('bool', function_exists('foo123'));
		};
	}
};

function () {
	assertType('bool', class_exists('foo345'));
	if (class_exists('foo345')) {
		assertType('true', class_exists('foo345'));
		function () {
			assertType('true', class_exists('foo345'));
		};
	} else {
		assertType('false', class_exists('foo345'));
		function () {
			assertType('bool', class_exists('foo345'));
		};
	}
};

function () {
	assertType('bool', enum_exists('foo567'));
	if (enum_exists('foo567')) {
		assertType('true', enum_exists('foo567'));
		function () {
			assertType('true', enum_exists('foo567'));
		};
	} else {
		assertType('false', enum_exists('foo567'));
		function () {
			assertType('bool', enum_exists('foo567'));
		};
	}
};

function () {
	assertType('bool', interface_exists('foo890'));
	if (interface_exists('foo890')) {
		assertType('true', interface_exists('foo890'));
		function () {
			assertType('true', interface_exists('foo890'));
		};
	} else {
		assertType('false', interface_exists('foo890'));
		function () {
			assertType('bool', interface_exists('foo890'));
		};
	}
};

function () {
	assertType('bool', trait_exists('fooabc'));
	if (trait_exists('fooabc')) {
		assertType('true', trait_exists('fooabc'));
		function () {
			assertType('true', trait_exists('fooabc'));
		};
	} else {
		assertType('false', trait_exists('fooabc'));
		function () {
			assertType('bool', trait_exists('fooabc'));
		};
	}
};
