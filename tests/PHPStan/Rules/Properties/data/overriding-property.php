<?php // lint >= 8.1

namespace OverridingProperty;

class Foo
{

	private $privateFoo;
	private static $privateStaticFoo;

	protected $protectedFoo;
	protected static $protectedStaticFoo;

	public $publicFoo;
	public static $publicStaticFoo;

}

class Bar extends Foo
{

	private static $privateFoo;
	private $privateStaticFoo;

	protected static $protectedFoo;
	protected $protectedStaticFoo;

	public static $publicFoo;
	public $publicStaticFoo;

}

class ReadonlyParent
{

	public mixed $readWrite;
	public readonly mixed $readOnly;
	public readonly mixed $readonly2;

}

class ReadonlyChild extends ReadonlyParent
{

	public readonly mixed $readWrite;
	public mixed $readOnly;
	public readonly mixed $readonly2;

}

class ReadonlyChild2 extends ReadonlyParent
{

	public function __construct(
		public readonly mixed $readWrite,
		public mixed $readOnly,
		public readonly mixed $readonly2
	) {}

}

class Dolor
{

	private $privateFoo;
	protected $protectedFoo;
	public $publicFoo;
	var $anotherPublicFoo;

}

class PrivateDolor extends Dolor
{

	private $privateFoo;
	private $protectedFoo; // error
	private $publicFoo; // error
	private $anotherPublicFoo; // error

}

class ProtectedDolor extends Dolor
{

	protected $privateFoo;
	protected $protectedFoo;
	protected $publicFoo; // error
	protected $anotherPublicFoo; // error

}

class PublicDolor extends Dolor
{

	public $privateFoo;
	public $protectedFoo;
	public $publicFoo;
	public $anotherPublicFoo;

}

class Public2Dolor extends Dolor
{

	var $privateFoo;
	var $protectedFoo;
	var $publicFoo;
	var $anotherPublicFoo;

}

class Typed
{

	public int $withType;
	public $withoutType;
	public int $withType2;
	public $withoutType2;

}

class TypeChild extends Typed
{

	public $withType; // error
	public int $withoutType; // error
	public int $withType2;
	public $withoutType2;

}

class Typed2
{

	protected int $foo;

}

class Typed2Child extends Typed2
{

	protected string $foo;

}

class TypedWithPhpDoc
{

	/** @var 1|2|3 */
	protected int $foo;

}

class Typed2WithPhpDoc extends TypedWithPhpDoc
{

	/** @var 4 */
	protected int $foo;

}
