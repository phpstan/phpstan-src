<?php declare(strict_types = 1);

namespace Bug1348;

$closure = function () {
	$this->foo = 'bar';
};

$object = new \stdClass();

\Closure::bind($closure, $object, $object)();
\Closure::bind(
	function () {
		$this->foo = 'bar';
	},
	$object,
	$object
)();

// arrow function case
$arrow = fn() => $this;

// static closures should still report $this as undefined
static function () {
	$this->foo = 'bar';
};

static fn() => $this;
