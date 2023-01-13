<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;

final class BcUncoveredInterface
{

	public const CLASSES = [
		Type::class,
		ReflectionProvider::class,
		Scope::class,
		FunctionReflection::class,
		ExtendedMethodReflection::class,
		ParametersAcceptorWithPhpDocs::class,
		ParameterReflectionWithPhpDocs::class,
	];

}
