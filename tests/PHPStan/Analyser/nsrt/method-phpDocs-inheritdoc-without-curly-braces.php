<?php

namespace MethodPhpDocsNamespace;

use function PHPStan\Testing\assertType;

use SomeNamespace\Amet as Dolor;
use SomeNamespace\Consecteur;

class FooInheritDocChildWithoutCurly extends Foo
{

	/**
	 * @inheritdoc
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
		$differentInstance = new Foo();

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
			assertType('MethodPhpDocsNamespace\Foo', $selfType);
			assertType('static(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)', $staticType);
			assertType('MethodPhpDocsNamespace\Foo', $this->doFoo());
			assertType('MethodPhpDocsNamespace\Bar', static::doSomethingStatic());
			assertType('static(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)', parent::doLorem());
			assertType('static(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)', $this->doLorem());
			assertType('MethodPhpDocsNamespace\Foo', $differentInstance->doLorem());
			assertType('static(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)', parent::doIpsum());
			assertType('MethodPhpDocsNamespace\Foo', $differentInstance->doIpsum());
			assertType('static(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)', $this->doIpsum());
			assertType('MethodPhpDocsNamespace\Foo', $this->doBar()[0]);
			assertType('MethodPhpDocsNamespace\Bar', self::doSomethingStatic());
			assertType('MethodPhpDocsNamespace\Bar', \MethodPhpDocsNamespace\Foo::doSomethingStatic());
			assertType('$this(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)', parent::doThis());
			assertType('$this(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)|null', parent::doThisNullable());
			assertType('$this(MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly)|MethodPhpDocsNamespace\Bar|null', parent::doThisUnion());
			assertType('array<null>', $this->returnNulls());
			assertType('object', $objectWithoutNativeTypehint);
			assertType('object', $objectWithNativeTypehint);
			assertType('object', $this->returnObject());
			assertType('MethodPhpDocsNamespace\Foo', new parent());
			assertType('MethodPhpDocsNamespace\FooInheritDocChildWithoutCurly', $inlineSelf);
			assertType('MethodPhpDocsNamespace\Bar', $inlineBar);
			assertType('MethodPhpDocsNamespace\Foo', $this->phpDocVoidMethod());
			assertType('MethodPhpDocsNamespace\Foo', $this->phpDocVoidMethodFromInterface());
			assertType('MethodPhpDocsNamespace\Foo', $this->phpDocVoidParentMethod());
			assertType('MethodPhpDocsNamespace\Foo', $this->phpDocWithoutCurlyBracesVoidParentMethod());
			assertType('array<string>', $this->returnsStringArray());
			assertType('mixed', $this->privateMethodWithPhpDoc());
			assertType('MethodPhpDocsNamespace\FooParent', $parent->doLorem());
			assertType('MethodPhpDocsNamespace\FooParent', $parent->doIpsum());
			assertType('MethodPhpDocsNamespace\FooParent', $this->returnParent());
			assertType('MethodPhpDocsNamespace\FooParent', $this->returnPhpDocParent());
		}
	}

	/**
	 * @inheritdoc
	 */
	private function privateMethodWithPhpDoc()
	{

	}

}
