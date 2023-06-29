<?php

namespace Bug4608;

function doFoo() {
	$c = new class {
		public function abc(): void {}
	};

	$s = rand(0, 1) ? 'abc' : 'not_abc';

	$c->{$s}();
	call_user_func([$c, $s]);
	[$c, $s]();
}
