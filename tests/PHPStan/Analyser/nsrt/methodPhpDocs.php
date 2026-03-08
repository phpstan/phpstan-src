<?php

namespace MethodPhpDocsNamespace;

use function PHPStan\Testing\assertType;

use SomeNamespace\Amet as Dolor;
use SomeNamespace\Consecteur;

class Foo extends FooParent
{

	/**
	 * @return Bar
	 */
	public static function doSomethingStatic()
	{

	}

	/**
	 * @param Foo|Bar $unionTypeParameter
	 * @param int $anotherMixedParameter
	 * @param int $anotherMixedParameter
	 * @paran int $yetAnotherMixedProperty
	 * @param int $integerParameter
	 * @param integer $anotherIntegerParameter
	 * @param aRray $arrayParameterOne
	 * @param mixed[] $arrayParameterOther
	 * @param Lorem $objectRelative
	 * @param \SomeOtherNamespace\Ipsum $objectFullyQualified
	 * @param Dolor $objectUsed
	 * @param null|int $nullableInteger
	 * @param Dolor|null $nullableObject
	 * @param Dolor $anotherNullableObject
	 * @param self $selfType
	 * @param static $staticType
	 * @param Null $nullType
	 * @param Bar $barObject
	 * @param Foo $conflictedObject
	 * @param Baz $moreSpecifiedObject
	 * @param resource $resource
	 * @param array[array] $yetAnotherAnotherMixedParameter
	 * @param \\Test\Bar $yetAnotherAnotherAnotherMixedParameter
	 * @param New $yetAnotherAnotherAnotherAnotherMixedParameter
	 * @param void $voidParameter
	 * @param Consecteur $useWithoutAlias
	 * @param true $true
	 * @param false $false
	 * @param true $boolTrue
	 * @param false $boolFalse
	 * @param bool $trueBoolean
	 * @param bool $parameterWithDefaultValueFalse
	 * @param object $objectWithoutNativeTypehint
	 * @param object $objectWithNativeTypehint
	 * @return Foo
	 */
	public function doFoo(
		$mixedParameter,
		$unionTypeParameter,
		$anotherMixedParameter,
		$yetAnotherMixedParameter,
		$integerParameter,
		$anotherIntegerParameter,
		$arrayParameterOne,
		$arrayParameterOther,
		$objectRelative,
		$objectFullyQualified,
		$objectUsed,
		$nullableInteger,
		$nullableObject,
		$selfType,
		$staticType,
		$nullType,
		$barObject,
		Bar $conflictedObject,
		Bar $moreSpecifiedObject,
		$resource,
		$yetAnotherAnotherMixedParameter,
		$yetAnotherAnotherAnotherMixedParameter,
		$yetAnotherAnotherAnotherAnotherMixedParameter,
		$voidParameter,
		$useWithoutAlias,
		$true,
		$false,
		bool $boolTrue,
		bool $boolFalse,
		bool $trueBoolean,
		$objectWithoutNativeTypehint,
		object $objectWithNativeTypehint,
		$parameterWithDefaultValueFalse = false,
		$anotherNullableObject = null
	)
	{
		$parent = new FooParent();
		$differentInstance = new self();

		/** @var self $inlineSelf */
		$inlineSelf = doFoo();

		/** @var Bar $inlineBar */
		$inlineBar = doFoo();

		foreach ($moreSpecifiedObject->doFluentUnionIterable() as $fluentUnionIterableBaz) {
			assertType('MethodPhpDocsNamespace\Foo', $selfType);
			assertType('static(MethodPhpDocsNamespace\Foo)', $staticType);
			assertType('MethodPhpDocsNamespace\Foo', $this->doFoo());
			assertType('MethodPhpDocsNamespace\Bar', static::doSomethingStatic());
			assertType('static(MethodPhpDocsNamespace\Foo)', parent::doLorem());
			assertType('MethodPhpDocsNamespace\FooParent', $parent->doLorem());
			assertType('static(MethodPhpDocsNamespace\Foo)', $this->doLorem());
			assertType('MethodPhpDocsNamespace\Foo', $differentInstance->doLorem());
			assertType('static(MethodPhpDocsNamespace\Foo)', parent::doIpsum());
			assertType('MethodPhpDocsNamespace\FooParent', $parent->doIpsum());
			assertType('MethodPhpDocsNamespace\Foo', $differentInstance->doIpsum());
			assertType('static(MethodPhpDocsNamespace\Foo)', $this->doIpsum());
			assertType('MethodPhpDocsNamespace\Foo', $this->doBar()[0]);
			assertType('MethodPhpDocsNamespace\Bar', self::doSomethingStatic());
			assertType('MethodPhpDocsNamespace\Bar', \MethodPhpDocsNamespace\Foo::doSomethingStatic());
			assertType('$this(MethodPhpDocsNamespace\Foo)', parent::doThis());
			assertType('$this(MethodPhpDocsNamespace\Foo)|null', parent::doThisNullable());
			assertType('$this(MethodPhpDocsNamespace\Foo)|MethodPhpDocsNamespace\Bar|null', parent::doThisUnion());
			assertType('MethodPhpDocsNamespace\FooParent', $this->returnParent());
			assertType('MethodPhpDocsNamespace\FooParent', $this->returnPhpDocParent());
			assertType('array<null>', $this->returnNulls());
			assertType('object', $objectWithoutNativeTypehint);
			assertType('object', $objectWithNativeTypehint);
			assertType('object', $this->returnObject());
			assertType('MethodPhpDocsNamespace\FooParent', new parent());
			assertType('MethodPhpDocsNamespace\Foo', $inlineSelf);
			assertType('MethodPhpDocsNamespace\Bar', $inlineBar);
			assertType('MethodPhpDocsNamespace\Foo', $this->phpDocVoidMethod());
			assertType('MethodPhpDocsNamespace\Foo', $this->phpDocVoidMethodFromInterface());
			assertType('MethodPhpDocsNamespace\Foo', $this->phpDocVoidParentMethod());
			assertType('MethodPhpDocsNamespace\Foo', $this->phpDocWithoutCurlyBracesVoidParentMethod());
			assertType('array<string>', $this->returnsStringArray());
			assertType('mixed', $this->privateMethodWithPhpDoc());
		}
	}

	/**
	 * @return self[]
	 */
	public function doBar(): array
	{

	}

	public function returnParent(): parent
	{

	}

	/**
	 * @return parent
	 */
	public function returnPhpDocParent()
	{

	}

	/**
	 * @return NULL[]
	 */
	public function returnNulls(): array
	{

	}

	public function returnObject(): object
	{

	}

	public function phpDocVoidMethod(): self
	{

	}

	public function phpDocVoidMethodFromInterface(): self
	{

	}

	public function phpDocVoidParentMethod(): self
	{

	}

	public function phpDocWithoutCurlyBracesVoidParentMethod(): self
	{

	}

	/**
	 * @return string[]
	 */
	public function returnsStringArray(): array
	{

	}

	private function privateMethodWithPhpDoc()
	{

	}

}
