<?php

namespace MethodPhpDocsNamespace;

use function PHPStan\Testing\assertType;

use SomeNamespace\Amet as Dolor;
use SomeNamespace\Consecteur;

trait FooTraitForRecursive
{

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
			assertType('mixed', $mixedParameter);
		assertType('MethodPhpDocsNamespace\Bar|MethodPhpDocsNamespace\Foo', $unionTypeParameter);
		assertType('int', $anotherMixedParameter);
		assertType('mixed', $yetAnotherMixedParameter);
		assertType('int', $integerParameter);
		assertType('int', $anotherIntegerParameter);
		assertType('array', $arrayParameterOne);
		assertType('array<mixed>', $arrayParameterOther);
		assertType('MethodPhpDocsNamespace\Lorem', $objectRelative);
		assertType('SomeOtherNamespace\Ipsum', $objectFullyQualified);
		assertType('SomeNamespace\Amet', $objectUsed);
		assertType('int|null', $nullableInteger);
		assertType('SomeNamespace\Amet|null', $nullableObject);
		assertType('SomeNamespace\Amet|null', $anotherNullableObject);
		assertType('null', $nullType);
		assertType('MethodPhpDocsNamespace\Bar', $barObject->doBar());
		assertType('MethodPhpDocsNamespace\Bar', $conflictedObject);
		assertType('MethodPhpDocsNamespace\Baz', $moreSpecifiedObject);
		assertType('MethodPhpDocsNamespace\Baz', $moreSpecifiedObject->doFluent());
		assertType('MethodPhpDocsNamespace\Baz|null', $moreSpecifiedObject->doFluentNullable());
		assertType('MethodPhpDocsNamespace\Baz', $moreSpecifiedObject->doFluentArray()[0]);
		assertType('iterable<MethodPhpDocsNamespace\Baz>&MethodPhpDocsNamespace\Collection', $moreSpecifiedObject->doFluentUnionIterable());
		assertType('MethodPhpDocsNamespace\Baz', $fluentUnionIterableBaz);
		assertType('resource', $resource);
		assertType('mixed', $yetAnotherAnotherMixedParameter);
		assertType('mixed', $yetAnotherAnotherAnotherMixedParameter);
		assertType('null', $voidParameter);
		assertType('SomeNamespace\Consecteur', $useWithoutAlias);
		assertType('true', $true);
		assertType('false', $false);
		assertType('true', $boolTrue);
		assertType('false', $boolFalse);
		assertType('bool', $trueBoolean);
		assertType('bool', $parameterWithDefaultValueFalse);
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $selfType);
		assertType('static(MethodPhpDocsNamespace\FooWithRecursiveTrait)', $staticType);
		assertType('MethodPhpDocsNamespace\Foo', $this->doFoo());
		assertType('MethodPhpDocsNamespace\Bar', static::doSomethingStatic());
		assertType('static(MethodPhpDocsNamespace\FooWithRecursiveTrait)', parent::doLorem());
		assertType('static(MethodPhpDocsNamespace\FooWithRecursiveTrait)', $this->doLorem());
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $differentInstance->doLorem());
		assertType('static(MethodPhpDocsNamespace\FooWithRecursiveTrait)', parent::doIpsum());
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $differentInstance->doIpsum());
		assertType('static(MethodPhpDocsNamespace\FooWithRecursiveTrait)', $this->doIpsum());
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $this->doBar()[0]);
		assertType('MethodPhpDocsNamespace\Bar', self::doSomethingStatic());
		assertType('MethodPhpDocsNamespace\Bar', \MethodPhpDocsNamespace\Foo::doSomethingStatic());
		assertType('$this(MethodPhpDocsNamespace\FooWithRecursiveTrait)', parent::doThis());
		assertType('$this(MethodPhpDocsNamespace\FooWithRecursiveTrait)|null', parent::doThisNullable());
		assertType('$this(MethodPhpDocsNamespace\FooWithRecursiveTrait)|MethodPhpDocsNamespace\Bar|null', parent::doThisUnion());
		assertType('array<null>', $this->returnNulls());
		assertType('object', $objectWithoutNativeTypehint);
		assertType('object', $objectWithNativeTypehint);
		assertType('object', $this->returnObject());
		assertType('MethodPhpDocsNamespace\FooParent', new parent());
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $inlineSelf);
		assertType('MethodPhpDocsNamespace\Bar', $inlineBar);
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $this->phpDocVoidMethod());
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $this->phpDocVoidMethodFromInterface());
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $this->phpDocVoidParentMethod());
		assertType('MethodPhpDocsNamespace\FooWithRecursiveTrait', $this->phpDocWithoutCurlyBracesVoidParentMethod());
		assertType('array<string>', $this->returnsStringArray());
		assertType('mixed', $this->privateMethodWithPhpDoc());
		}
	}

}
