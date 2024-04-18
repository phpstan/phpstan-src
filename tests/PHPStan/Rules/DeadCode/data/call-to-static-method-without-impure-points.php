<?php

namespace CallToStaticMethodWithoutImpurePoints;

function (): void {
	X::myFunc();
	X::myFUNC();
	X::throwingFUNC();
	X::throwingFunc();
	X::funcWithRef();
	X::impureFunc();
	X::callingImpureFunc();

	$a = X::myFunc();

	x::myFunc(); // case-insensitive class name

	subY::myFunc();

	SubSubY::myFunc();
	SubSubY::mySubSubFunc();
	SubSubY::mySubSubCallSelfFunc();
	SubSubY::mySubSubCallParentFunc();
	SubSubY::mySubSubCallStaticFunc();
	SubSubY::mySubSubCallSelfImpureFunc();
	SubSubY::mySubSubCallParentImpureFunc();
	SubSubY::mySubSubCallStaticImpureFunc();
};

class y
{
	static function myFunc()
	{
	}

	static function myImpureFunc()
	{
		echo '1';
	}
}

class subY extends y {
}

class SubSubY extends subY {
	static function mySubSubCallStaticFunc()
	{
		static::myFunc();
	}

	static function mySubSubCallSelfFunc()
	{
		self::myFunc();
	}

	static function mySubSubCallParentFunc()
	{
		parent::myFunc();
	}
	static function mySubSubCallStaticImpureFunc()
	{
		static::myImpureFunc();
	}

	static function mySubSubCallSelfImpureFunc()
	{
		self::myImpureFunc();
	}

	static function mySubSubCallParentImpureFunc()
	{
		parent::myImpureFunc();
	}

	static function mySubSubFunc()
	{
	}
}

class X {
	static function myFunc()
	{
	}

	static function throwingFunc()
	{
		throw new \Exception();
	}

	static function funcWithRef(&$a)
	{
	}

	/** @phpstan-impure */
	static function impureFunc()
	{
	}

	static function callingImpureFunc()
	{
		self::impureFunc();
	}
}


class ParentWithConstructor
{
	private $i;

	public function __construct()
	{
		$this->i = 1;
	}
}

class ChildOfParentWithConstructor extends ParentWithConstructor
{
	public function __construct()
	{
		parent::__construct();
	}
}
