<?php // lint >= 8.2

namespace Bug12421;

readonly class NativeReadonlyClass
{
	public Y $y;

	public function __construct()
	{
		$this->y = new Y();
	}
}

class NativeReadonlyProperty
{
	public readonly Y $y;

	public function __construct()
	{
		$this->y = new Y();
	}
}

/** @readonly */
class PhpdocReadonlyClass
{
	public Y $y;

	public function __construct()
	{
		$this->y = new Y();
	}
}

class PhpdocReadonlyProperty
{
	/** @readonly */
	public Y $y;

	public function __construct()
	{
		$this->y = new Y();
	}
}

/** @immutable  */
class PhpdocImmutableClass
{
	public Y $y;

	public function __construct()
	{
		$this->y = new Y();
	}
}

class RegularProperty
{
	public Y $y;

	public function __construct()
	{
		$this->y = new Y();
	}
}

class Y
{
}

function doFoo() {
	$x = new RegularProperty();
	unset($x->y);
	var_dump($x->y);

	$x = new NativeReadonlyClass();
	unset($x->y);
	var_dump($x->y);

	$x = new NativeReadonlyProperty();
	unset($x->y);
	var_dump($x->y);

	$x = new PhpdocReadonlyClass();
	unset($x->y);
	var_dump($x->y);

	$x = new PhpdocReadonlyProperty();
	unset($x->y);
	var_dump($x->y);

	$x = new PhpdocImmutableClass();
	unset($x->y);
	var_dump($x->y);

	$x = new \stdClass();
	unset($x->y);

}

