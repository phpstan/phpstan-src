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

	function MagicClassConstantRuleFuncParams(
		string $file = __FILE__,
		int $line = __LINE__,
		string $class = __CLASS__,
		string $dir = __DIR__,
		string $namespace = __NAMESPACE__,
		string $method = __METHOD__,
		string $function = __FUNCTION__,
		string $trait = __TRAIT__
	): void
	{
	}
}

class T1 {
	use t ;
}

function MagicClassConstantRuleFuncParams(
	string $file = __FILE__,
	int $line = __LINE__,
	string $class = __CLASS__,
	string $dir = __DIR__,
	string $namespace = __NAMESPACE__,
	string $method = __METHOD__,
	string $function = __FUNCTION__,
	string $trait = __TRAIT__
): void
{
}

class y {
	function MagicClassConstantRuleFuncParams(
		string $file = __FILE__,
		int $line = __LINE__,
		string $class = __CLASS__,
		string $dir = __DIR__,
		string $namespace = __NAMESPACE__,
		string $method = __METHOD__,
		string $function = __FUNCTION__,
		string $trait = __TRAIT__
	): void
	{
	}

}

