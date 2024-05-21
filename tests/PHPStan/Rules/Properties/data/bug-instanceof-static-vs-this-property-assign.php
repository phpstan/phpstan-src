<?php declare(strict_types=1); // lint >= 8.1

namespace BugInstanceofStaticVsThisPropertyAssign;

function set(int &$var): void
{
	$var = 5;
}

class FooBase
{
	public function run(): void
	{
		if ($this instanceof FooChild) {
			$this->nativeReadonlyProp = 5;
			$this->phpdocReadonlyProp = 5;
			set($this->nativeReadonlyProp);
			set($this->phpdocReadonlyProp);
			$a = &$this->nativeReadonlyProp;
			$b = &$this->phpdocReadonlyProp;

			if (rand()) $this::$staticStringProp = 5;
			if (rand()) static::$staticStringProp = 5;
			set($this::$staticStringProp);
			set(static::$staticStringProp);
		}

		if (is_a(static::class, FooChild::class, true)) {
			$this->nativeReadonlyProp = 5;
			$this->phpdocReadonlyProp = 5;
			set($this->nativeReadonlyProp);
			set($this->phpdocReadonlyProp);
			$a = &$this->nativeReadonlyProp;
			$b = &$this->phpdocReadonlyProp;

			if (rand()) $this::$staticStringProp = 5;
			if (rand()) static::$staticStringProp = 5;
			set($this::$staticStringProp);
			set(static::$staticStringProp);
		}
	}
}

class FooChild extends FooBase
{
	public readonly int $nativeReadonlyProp;

	/** @readonly */
	public int $phpdocReadonlyProp;

	public static string $staticStringProp;

	public function __construct()
	{
		$this->nativeReadonlyProp = 5;
		$this->phpdocReadonlyProp = 5;
	}
}
