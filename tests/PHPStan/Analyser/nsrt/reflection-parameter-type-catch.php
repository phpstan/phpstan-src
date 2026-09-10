<?php

namespace ReflectionParameterTypeCatch;

use function PHPStan\Testing\assertType;

/** @param class-string $className */
function parameterType(string $className, string $propertyName): void
{
	$sourceInterfaceReflection = new \ReflectionClass($className);
	$paramType = null;
	try {
		$reflectionMethod = $sourceInterfaceReflection->getMethod('set' . ucfirst($propertyName));
		$reflectionParams = $reflectionMethod->getParameters();
		if (isset($reflectionParams[0])) {
			$paramType = $reflectionParams[0]->getType();
			if (($paramType !== null) && $reflectionParams[0]->isOptional()) {
				$paramType = '?' . $paramType;
			}
		}

		if ($paramType !== null) {
			$paramType = (string) $paramType;
		}
	} catch (\Exception $e) {
	}

	assertType('string|null', $paramType);
}

function stringifyType(\ReflectionType $type): void
{
	$string = null;
	try {
		$string = (string) $type;
	} catch (\Exception $e) {
	}

	assertType('string', $string);
}

function checkOptional(\ReflectionParameter $parameter): void
{
	$optional = null;
	try {
		$optional = $parameter->isOptional();
	} catch (\Exception $e) {
	}

	assertType('bool', $optional);
}
