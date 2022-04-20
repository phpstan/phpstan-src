<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use function is_a;

/**
 * @internal
 */
class DirectScopeFactory implements ScopeFactory
{

	public function __construct(
		private string $scopeClass,
		private ReflectionProvider $reflectionProvider,
		private DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider,
		private OperatorTypeSpecifyingExtensionRegistryProvider $operatorTypeSpecifyingExtensionRegistryProvider,
		private Standard $printer,
		private TypeSpecifier $typeSpecifier,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private Parser $parser,
		private NodeScopeResolver $nodeScopeResolver,
		private bool $treatPhpDocTypesAsCertain,
		private PhpVersion $phpVersion,
		private bool $explicitMixedInUnknownGenericNew,
		private ConstantResolver $constantResolver,
	)
	{
	}

	/**
	 * @param array<string, Type> $constantTypes
	 * @param VariableTypeHolder[] $variablesTypes
	 * @param VariableTypeHolder[] $moreSpecificTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, true> $currentlyAllowedUndefinedExpressions
	 * @param array<string, Type> $nativeExpressionTypes
	 * @param array<(FunctionReflection|MethodReflection)> $inFunctionCallsStack
	 *
	 */
	public function create(
		ScopeContext $context,
		bool $declareStrictTypes = false,
		array $constantTypes = [],
		FunctionReflection|MethodReflection|null $function = null,
		?string $namespace = null,
		array $variablesTypes = [],
		array $moreSpecificTypes = [],
		array $conditionalExpressions = [],
		?string $inClosureBindScopeClass = null,
		?ParametersAcceptor $anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $currentlyAllowedUndefinedExpressions = [],
		array $nativeExpressionTypes = [],
		array $inFunctionCallsStack = [],
		bool $afterExtractCall = false,
		?Scope $parentScope = null,
	): MutatingScope
	{
		$scopeClass = $this->scopeClass;
		if (!is_a($scopeClass, MutatingScope::class, true)) {
			throw new ShouldNotHappenException();
		}

		return new $scopeClass(
			$this,
			$this->reflectionProvider,
			$this->dynamicReturnTypeExtensionRegistryProvider->getRegistry(),
			$this->operatorTypeSpecifyingExtensionRegistryProvider->getRegistry(),
			$this->printer,
			$this->typeSpecifier,
			$this->propertyReflectionFinder,
			$this->parser,
			$this->nodeScopeResolver,
			$this->constantResolver,
			$context,
			$this->phpVersion,
			$declareStrictTypes,
			$constantTypes,
			$function,
			$namespace,
			$variablesTypes,
			$moreSpecificTypes,
			$conditionalExpressions,
			$inClosureBindScopeClass,
			$anonymousFunctionReflection,
			$inFirstLevelStatement,
			$currentlyAssignedExpressions,
			$currentlyAllowedUndefinedExpressions,
			$nativeExpressionTypes,
			$inFunctionCallsStack,
			$this->treatPhpDocTypesAsCertain,
			$afterExtractCall,
			$parentScope,
			$this->explicitMixedInUnknownGenericNew,
		);
	}

}
