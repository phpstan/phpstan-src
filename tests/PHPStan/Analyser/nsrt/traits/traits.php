<?php

namespace TraitPhpDocs;

use function PHPStan\Testing\assertType;

class Foo extends Bar
{

	use \TraitPhpDocsTwo\FooTrait, \TraitPhpDocsThree\BarTrait {
		\TraitPhpDocsTwo\FooTrait::methodInMoreTraits insteadof \TraitPhpDocsThree\BarTrait;
		\TraitPhpDocsThree\BarTrait::anotherMethodInMoreTraits insteadof \TraitPhpDocsTwo\FooTrait;
		\TraitPhpDocsTwo\FooTrait::yetAnotherMethodInMoreTraits insteadof \TraitPhpDocsThree\BarTrait;
		\TraitPhpDocsThree\BarTrait::yetAnotherMethodInMoreTraits as aliasedYetAnotherMethodInMoreTraits;
		\TraitPhpDocsThree\BarTrait::yetYetAnotherMethodInMoreTraits insteadof \TraitPhpDocsTwo\FooTrait;
		\TraitPhpDocsTwo\FooTrait::yetYetAnotherMethodInMoreTraits as aliasedYetYetAnotherMethodInMoreTraits;
	}

	/** @var PropertyTypeFromClass */
	private $conflictingProperty;

	/** @var AmbiguousPropertyType */
	private $bogusProperty;

	/** @var BogusPropertyType */
	private $anotherBogusProperty;

	public function doFoo()
	{
		assertType('mixed', $this->propertyWithoutPhpDoc);
		assertType('TraitPhpDocsTwo\TraitPropertyType', $this->traitProperty);
		assertType('TraitPhpDocs\PropertyTypeFromClass', $this->conflictingProperty);
		assertType('TraitPhpDocs\BogusPropertyType', $this->anotherBogusProperty);
		assertType('TraitPhpDocsTwo\BogusPropertyType', $this->differentBogusProperty);
		assertType('string', $this->methodWithoutPhpDoc());
		assertType('TraitPhpDocsTwo\TraitMethodType', $this->traitMethod());
		assertType('TraitPhpDocs\MethodTypeFromClass', $this->conflictingMethod());
		assertType('TraitPhpDocs\AmbiguousMethodType', $this->bogusMethod());
		assertType('TraitPhpDocs\BogusMethodType', $this->anotherBogusMethod());
		assertType('TraitPhpDocsTwo\BogusMethodType', $this->differentBogusMethod());
		assertType('TraitPhpDocsTwo\DuplicateMethodType', $this->methodInMoreTraits());
		assertType('TraitPhpDocsThree\AnotherDuplicateMethodType', $this->anotherMethodInMoreTraits());
		assertType('TraitPhpDocsTwo\YetAnotherDuplicateMethodType', $this->yetAnotherMethodInMoreTraits());
		assertType('TraitPhpDocsThree\YetAnotherDuplicateMethodType', $this->aliasedYetAnotherMethodInMoreTraits());
		assertType('TraitPhpDocsThree\YetYetAnotherDuplicateMethodType', $this->yetYetAnotherMethodInMoreTraits());
		assertType('TraitPhpDocsTwo\YetYetAnotherDuplicateMethodType', $this->aliasedYetYetAnotherMethodInMoreTraits());
		assertType('int', $this->propertyFromTraitUsingTrait);
		assertType('string', $this->methodFromTraitUsingTrait());
		assertType('TraitPhpDocsThree\Foo', $this->loremTraitProperty);
	}

	/**
	 * @return MethodTypeFromClass
	 */
	public function conflictingMethod()
	{

	}

	/**
	 * @return AmbiguousMethodType
	 */
	public function bogusMethod()
	{

	}

	/**
	 * @return BogusMethodType
	 */
	public function anotherBogusMethod()
	{

	}

}
