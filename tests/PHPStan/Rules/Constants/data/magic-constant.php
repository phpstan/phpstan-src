<?php declare(strict_types=1);

namespace MagicClassConstantRule;

echo __CLASS__;
echo __FUNCTION__;
echo __METHOD__;
echo __NAMESPACE__;
echo __TRAIT__;

class x {
	function doFoo (): void {
		echo __CLASS__;
		echo __FUNCTION__;
		echo __METHOD__;
		echo __NAMESPACE__;
		echo __TRAIT__;
	}
}

function doFoo (): void {
	echo __CLASS__;
	echo __FUNCTION__;
	echo __METHOD__;
	echo __NAMESPACE__;
	echo __TRAIT__;
}

trait t {
	function doFoo (): void {
		echo __CLASS__;
		echo __FUNCTION__;
		echo __METHOD__;
		echo __NAMESPACE__;
		echo __TRAIT__;
	}
}

class T1 {
	use t ;
}
