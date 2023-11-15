<?php declare(strict_types=1);

namespace MagicClassConstantRule;

echo __CLASS__;
echo __FUNCTION__;
echo __METHOD__;
echo __NAMESPACE__;

class x {
	function doFoo (): void {
		echo __CLASS__;
		echo __FUNCTION__;
		echo __METHOD__;
		echo __NAMESPACE__;
	}
}

function doFoo (): void {
	echo __CLASS__;
	echo __FUNCTION__;
	echo __METHOD__;
	echo __NAMESPACE__;
}

trait t {
	function doFoo (): void {
		echo __CLASS__;
		echo __FUNCTION__;
		echo __METHOD__;
		echo __NAMESPACE__;
	}
}
