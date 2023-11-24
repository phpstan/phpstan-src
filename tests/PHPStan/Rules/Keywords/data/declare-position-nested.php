<?php

namespace DeclarePositionDeep;

class X {
	public function doFoo() {
		declare(strict_types = 1);
	}
}

function doFoo() {
	declare(strict_types = 1);
}
