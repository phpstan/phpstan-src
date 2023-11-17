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

class props {
	public $f = __FILE__;
	public $l = __LINE__;
	public $c = __CLASS__;
	public $d = __DIR__;
	public $n = __NAMESPACE__;
	public $m = __METHOD__;
	public $fun = __FUNCTION__;
	public $t = __TRAIT__;
}

$func = function() {
	echo __CLASS__;
	echo __FUNCTION__;
	echo __METHOD__;
	echo __NAMESPACE__;
	echo __TRAIT__;
};

$staticFunc = static function() {
	echo __CLASS__;
	echo __FUNCTION__;
	echo __METHOD__;
	echo __NAMESPACE__;
	echo __TRAIT__;
};

$funcParams = function(
	string $file = __FILE__,
	int $line = __LINE__,
	string $class = __CLASS__,
	string $dir = __DIR__,
	string $namespace = __NAMESPACE__,
	string $method = __METHOD__,
	string $function = __FUNCTION__,
	string $trait = __TRAIT__
) {
};

class inConstruct {
	function __construct(
		string $file = __FILE__,
		int $line = __LINE__,
		string $class = __CLASS__,
		string $dir = __DIR__,
		string $namespace = __NAMESPACE__,
		string $method = __METHOD__,
		string $function = __FUNCTION__,
		string $trait = __TRAIT__
	)
	{
	}

}
