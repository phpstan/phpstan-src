<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;

final class LazyInternalScopeFactory implements InternalScopeFactory
{

	public function __construct(
		private Container $container,
	)
	{
	}

	/**
	 * @param array<string, ExpressionTypeHolder> $expressionTypes
	 * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, true> $currentlyAllowedUndefinedExpressions
	 * @param list<array{FunctionReflection|MethodReflection|null, ParameterReflection|null}> $inFunctionCallsStack
	 */
	public function create(
		ScopeContext $context,
		bool $declareStrictTypes = false,
		PhpFunctionFromParserNodeReflection|null $function = null,
		?string $namespace = null,
		array $expressionTypes = [],
		array $nativeExpressionTypes = [],
		array $conditionalExpressions = [],
		array $inClosureBindScopeClasses = [],
		?ParametersAcceptor $anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $currentlyAllowedUndefinedExpressions = [],
		array $inFunctionCallsStack = [],
		bool $afterExtractCall = false,
		?Scope $parentScope = null,
		bool $nativeTypesPromoted = false,
	): MutatingScope
	{
		return new MutatingScope(
			$this,
			$this->container->getByType(ReflectionProvider::class),
			$this->container->getByType(InitializerExprTypeResolver::class),
			$this->container->getByType(DynamicReturnTypeExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(ExpressionTypeResolverExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(ExprPrinter::class),
			$this->container->getByType(TypeSpecifier::class),
			$this->container->getByType(PropertyReflectionFinder::class),
			$this->container->getService('currentPhpVersionSimpleParser'),
			$this->container->getByType(NodeScopeResolver::class),
			$this->container->getByType(RicherScopeGetTypeHelper::class),
			$this->container->getByType(ConstantResolver::class),
			$context,
			$this->container->getByType(PhpVersion::class),
			$this->container->getParameter('phpVersion'),
			$declareStrictTypes,
			$function,
			$namespace,
			$expressionTypes,
			$nativeExpressionTypes,
			$conditionalExpressions,
			$inClosureBindScopeClasses,
			$anonymousFunctionReflection,
			$inFirstLevelStatement,
			$currentlyAssignedExpressions,
			$currentlyAllowedUndefinedExpressions,
			$inFunctionCallsStack,
			$afterExtractCall,
			$parentScope,
			$nativeTypesPromoted,
		);
	}

}
