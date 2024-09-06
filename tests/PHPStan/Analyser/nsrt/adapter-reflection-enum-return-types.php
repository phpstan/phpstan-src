<?php

namespace AdapterReflectionEnumReturnTypes;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;
use function PHPStan\Testing\assertType;

function (ReflectionEnum $r, string $s): void {
	assertType('non-empty-string|false', $r->getFileName());
	assertType('int|false', $r->getStartLine());
	assertType('int|false', $r->getEndLine());
	assertType('string|false', $r->getDocComment());
	assertType('PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant|false', $r->getReflectionConstant($s));
	assertType('PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|false', $r->getParentClass());
	assertType('non-empty-string|false', $r->getExtensionName());
	assertType('PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|null', $r->getBackingType());
};

function (ReflectionEnumBackedCase $r): void {
	assertType('string|false', $r->getDocComment());
	assertType('PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|null', $r->getType());
};

function (ReflectionEnumUnitCase $r): void {
	assertType('string|false', $r->getDocComment());
	assertType('PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|null', $r->getType());
};
