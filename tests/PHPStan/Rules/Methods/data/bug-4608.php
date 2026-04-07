<?php declare(strict_types=1);

namespace Bug4608;

$c = new class {
	public function abc(): void {}
};

$s = rand(0, 1) ? 'abc' : 'not_abc';

$c->{$s}();
