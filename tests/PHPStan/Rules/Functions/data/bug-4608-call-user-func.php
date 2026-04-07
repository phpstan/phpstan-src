<?php declare(strict_types=1);

namespace Bug4608CallUserFunc;

$c = new class {
	public function abc(): void {}
};

$s = rand(0, 1) ? 'abc' : 'not_abc';

call_user_func([$c, $s]);
