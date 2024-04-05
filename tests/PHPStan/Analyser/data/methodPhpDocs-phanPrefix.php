<?php

namespace MethodPhpDocsNamespace;

use SomeNamespace\Amet as Dolor;
use SomeNamespace\Consecteur;

class FooPhanPrefix extends FooParent
{

	/**
	 * @phan-return Bar
	 */
	public static function doSomethingStatic()
	{

	}

	/**
	 * @phan-param Foo|Bar $unionTypeParameter
	 * @phan-param int $anotherMixedParameter
	 * @phan-param int $anotherMixedParameter
	 * @phan-paran int $yetAnotherMixedProperty
	 * @phan-param int $integerParameter
	 * @phan-param integer $anotherIntegerParameter
	 * @phan-param aRray $arrayParameterOne
	 * @phan-param mixed[] $arrayParameterOther
	 * @phan-param Lorem $objectRelative
	 * @phan-param \SomeOtherNamespace\Ipsum $objectFullyQualified
	 * @phan-param Dolor $objectUsed
	 * @phan-param null|int $nullableInteger
	 * @phan-param Dolor|null $nullableObject
	 * @phan-param Dolor $anotherNullableObject
	 * @phan-param self $selfType
	 * @phan-param static $staticType
	 * @phan-param Null $nullType
	 * @phan-param Bar $barObject
	 * @phan-param Foo $conflictedObject
	 * @phan-param Baz $moreSpecifiedObject
	 * @phan-param resource $resource
	 * @phan-param array[array] $yetAnotherAnotherMixedParameter
	 * @phan-param \\Test\Bar $yetAnotherAnotherAnotherMixedParameter
	 * @phan-param New $yetAnotherAnotherAnotherAnotherMixedParameter
	 * @phan-param void $voidParameter
	 * @phan-param Consecteur $useWithoutAlias
	 * @phan-param true $true
	 * @phan-param false $false
	 * @phan-param true $boolTrue
	 * @phan-param false $boolFalse
	 * @phan-param bool $trueBoolean
	 * @phan-param bool $parameterWithDefaultValueFalse
	 * @phan-param object $objectWithoutNativeTypehint
	 * @phan-param object $objectWithNativeTypehint
	 * @phan-return Foo
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

		/** @phan-var self $inlineSelf */
		$inlineSelf = doFoo();

		/** @phan-var Bar $inlineBar */
		$inlineBar = doFoo();

		foreach ($moreSpecifiedObject->doFluentUnionIterable() as $fluentUnionIterableBaz) {
			die;
		}
	}

	/**
	 * @phan-return self[]
	 */
	public function doBar(): array
	{

	}

	public function returnParent(): parent
	{

	}

	/**
	 * @phan-return parent
	 */
	public function returnPhpDocParent()
	{

	}

	/**
	 * @phan-return NULL[]
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
	 * @phan-return string[]
	 */
	public function returnsStringArray(): array
	{

	}

	private function privateMethodWithPhpDoc()
	{

	}

}
