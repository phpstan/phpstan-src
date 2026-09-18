<?php // lint >= 8.0

namespace DeadCodeNoopErrorThrows;

function (int $a, int $b, string $s) {
	$a / $b;
	$a % $b;
	match ($a) {
		1 => 'a',
	};
	new \DateTimeImmutable($s);
};

function () {
	throw new \ValueError('x');
};

function (\Error $e) {
	throw $e;
};

function (?int $n) {
	$n ?? throw new \ValueError('x');
};

function (bool $c) {
	$c ? throw new \ValueError('x') : null;
};

function (?int $n) {
	$n ?: throw new \ValueError('x');
};

function (bool $c) {
	$c && throw new \ValueError('x');
};

function (bool $c) {
	$c || throw new \ValueError('x');
};

function (int $a) {
	match (true) {
		$a > 0 => throw new \ValueError('x'),
		default => null,
	};
};

function () {
	(function (): void {
		throw new \ValueError('x');
	})();
};

function () {
	$fn = function (): void {
		throw new \ValueError('x');
	};
	$fn();
};

function () {
	new class {
		public function __construct()
		{
			throw new \ValueError('x');
		}
	};
};
