<?php

namespace Bug4805;

final class Foo
{
	/**
	 * @throws \OutOfBoundsException
	 */
	public static function canThrow(bool $throw): self
	{
		if($throw){
			throw new \OutOfBoundsException('Ooops');
		}
		return new self();
	}

	/**
	 * @throws void
	 */
	public static function cannotThrow(bool $throw): void
	{

	}
}

final class Bar
{
	public static function doFoo(Foo $foo, bool $flag): bool
	{
		try{
			$foo::canThrow($flag);
			return true;
		}catch(\OutOfBoundsException $e){
			return false;
		}
	}

	public static function doFoo2(Foo $foo, bool $flag): bool
	{
		try{
			$foo::cannotThrow($flag);
			return true;
		}catch(\OutOfBoundsException $e){
			return false;
		}
	}

	/** @param class-string<Foo> $foo */
	public static function doBar(string $foo, bool $flag): bool
	{
		try{
			$foo::canThrow($flag);
			return true;
		} catch(\OutOfBoundsException $e){
			return false;
		}
	}

	/** @param class-string<Foo> $foo */
	public static function doBar2(string $foo, bool $flag): bool
	{
		try{
			$foo::cannotThrow($flag);
			return true;
		} catch(\OutOfBoundsException $e){
			return false;
		}
	}

	/** @param class-string $foo */
	public static function doBaz(string $foo, bool $flag): bool
	{
		try{
			$foo::canThrow($flag);
			return true;
		} catch(\OutOfBoundsException $e){
			return false;
		}
	}

	/** @param class-string $foo */
	public static function doBaz2(string $foo, bool $flag): bool
	{
		try{
			$foo::cannotThrow($flag);
			return true;
		} catch(\OutOfBoundsException $e){
			return false;
		}
	}

	public static function doLorem(string $foo, bool $flag): bool
	{
		try{
			$foo::canThrow($flag);
			return true;
		} catch(\OutOfBoundsException $e){
			return false;
		}
	}

	public static function doLorem2(string $foo, bool $flag): bool
	{
		try{
			$foo::cannotThrow($flag);
			return true;
		} catch(\OutOfBoundsException $e){
			return false;
		}
	}
}
