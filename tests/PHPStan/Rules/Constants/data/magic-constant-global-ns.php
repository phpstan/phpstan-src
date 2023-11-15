<?php declare(strict_types=1);



echo __CLASS__;
echo __FUNCTION__;
echo __METHOD__;
echo __NAMESPACE__;

class MagicClassConstantRule {
	function doFoo (): void {
		echo __CLASS__;
		echo __FUNCTION__;
		echo __METHOD__;
		echo __NAMESPACE__;
	}
}

function MagicClassConstantRuleFunc (): void {
	echo __CLASS__;
	echo __FUNCTION__;
	echo __METHOD__;
	echo __NAMESPACE__;
}

trait MagicClassConstantTrait {
	function doFoo (): void {
		echo __CLASS__;
		echo __FUNCTION__;
		echo __METHOD__;
		echo __NAMESPACE__;
	}
}
