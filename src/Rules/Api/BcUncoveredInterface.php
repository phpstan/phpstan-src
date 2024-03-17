<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
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
		CallableParametersAcceptor::class,
		FileRuleError::class,
		IdentifierRuleError::class,
		LineRuleError::class,
		MetadataRuleError::class,
		NonIgnorableRuleError::class,
		RuleError::class,
		TipRuleError::class,
	];

}
