<?php // lint >= 8.0

namespace PromotedPropertiesTypes;

use function PHPStan\Analyser\assertType;

/**
 * @template T
 */
class Foo
{

	/**
	 * @param array<int, string> $anotherPhpDocArray
	 * @param T $anotherTemplateProperty
	 * @param string $bothProperty
	 */
	public function __construct(
		public $noType,
		public int $nativeIntType,
		/** @var array<int, string> */ public $phpDocArray,
		public $anotherPhpDocArray,
		/** @var array<int, string> */ public array $yetAnotherPhpDocArray,
		/** @var T */ public $templateProperty,
		public $anotherTemplateProperty,
		/** @var int */ public $bothProperty
	) { }

}

function (Foo $foo): void {
	assertType('mixed', $foo->noType);
	assertType('int', $foo->nativeIntType);
	assertType('array<int, string>', $foo->phpDocArray);
	assertType('array<int, string>', $foo->anotherPhpDocArray);
	assertType('array<int, string>', $foo->yetAnotherPhpDocArray);
	assertType('int', $foo->bothProperty);
};

/**
 * @extends Foo<\stdClass>
 */
class Bar extends Foo
{

}

function (Bar $bar): void {
	assertType('stdClass', $bar->templateProperty);
	assertType('stdClass', $bar->anotherTemplateProperty);
};

/**
 * @template T
 */
class Lorem
{

	/**
	 * @param T $foo
	 */
	public function __construct(
		public $foo
	) { }

}

function (): void {
	$lorem = new Lorem(new \stdClass);
	assertType('stdClass', $lorem->foo);
};
