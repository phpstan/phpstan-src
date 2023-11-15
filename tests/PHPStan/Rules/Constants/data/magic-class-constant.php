<?php declare(strict_types=1);

namespace MagicClassConstantRule;

echo __CLASS__;

class x {
	function doFoo (): void {
		echo __CLASS__;
	}
}

function doFoo (): void {
	echo __CLASS__;
}

trait t {
	function doFoo (): void {
		echo __CLASS__;
	}
}
