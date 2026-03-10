<?php declare(strict_types = 1);

namespace Bug14251;

trait InternalTrait {
	public function doSomething(): void {}
}

$obj = new InternalTrait();
