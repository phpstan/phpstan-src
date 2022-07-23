<?php declare(strict_types = 1);

namespace Bug7662;

class Foo {
	public function __construct(public array $bar) {}
}
