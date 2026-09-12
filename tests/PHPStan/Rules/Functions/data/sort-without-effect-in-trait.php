<?php

namespace SortWithoutEffectInTrait;

trait SizeFromUsingClass
{

	/** @return list<int> */
	public function doFoo(): array
	{
		$a = self::ITEMS;
		// one element in One, two in Many: pointless in one context and fine in the other,
		// so the using classes do not agree and nothing is reported
		sort($a);

		return $a;
	}

}

class One
{

	use SizeFromUsingClass;

	public const ITEMS = [1];

}

class Many
{

	use SizeFromUsingClass;

	public const ITEMS = [1, 2];

}

trait SizeNotFromUsingClass
{

	/** @return list<int> */
	public function doFoo(): array
	{
		$a = [1];
		sort($a);

		return $a;
	}

	/** @return list<int> */
	public function doBar(): array
	{
		$b = [];
		sort($b);

		return $b;
	}

}

class UsesIt
{

	use SizeNotFromUsingClass;

}

class AlsoUsesIt
{

	use SizeNotFromUsingClass;

}

trait OneUsingClassOnly
{

	/** @return list<int> */
	public function doFoo(): array
	{
		$a = self::ITEMS;
		// with a single using class there is no second opinion, so this is reported the same way
		// the constant-condition rules report it
		sort($a);

		return $a;
	}

}

class TheOnlyUser
{

	use OneUsingClassOnly;

	public const ITEMS = [1];

}
