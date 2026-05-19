<?php

namespace MethodPhpDocsNamespace;

use SomeNamespace\Amet as Dolor;
use SomeNamespace\Consecteur;

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
 * @phan-return Foo
 */
function doFooPhanPrefix(
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
	$parameterWithDefaultValueFalse = false,
	$anotherNullableObject = null
)
{
	$fooFunctionResult = doFoo();

	foreach ($moreSpecifiedObject->doFluentUnionIterable() as $fluentUnionIterableBaz) {
		die;
	}
}
