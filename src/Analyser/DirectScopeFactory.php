<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\Type;

/**
 * @internal
 */
class DirectScopeFactory implements ScopeFactory
{

	private string $scopeClass;

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider;

	private OperatorTypeSpecifyingExtensionRegistryProvider $operatorTypeSpecifyingExtensionRegistryProvider;

	private \PhpParser\PrettyPrinter\Standard $printer;

	private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;

	private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;

	private \PHPStan\Parser\Parser $parser;

	private NodeScopeResolver $nodeScopeResolver;

	private bool $treatPhpDocTypesAsCertain;

	/** @var string[] */
	private array $dynamicConstantNames;

	private PhpVersion $phpVersion;

	public function __construct(
		string $scopeClass,
		ReflectionProvider $reflectionProvider,
		DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider,
		OperatorTypeSpecifyingExtensionRegistryProvider $operatorTypeSpecifyingExtensionRegistryProvider,
		\PhpParser\PrettyPrinter\Standard $printer,
		TypeSpecifier $typeSpecifier,
		PropertyReflectionFinder $propertyReflectionFinder,
		\PHPStan\Parser\Parser $parser,
		NodeScopeResolver $nodeScopeResolver,
		bool $treatPhpDocTypesAsCertain,
		Container $container,
		PhpVersion $phpVersion
	)
	{
		$this->scopeClass = $scopeClass;
		$this->reflectionProvider = $reflectionProvider;
		$this->dynamicReturnTypeExtensionRegistryProvider = $dynamicReturnTypeExtensionRegistryProvider;
		$this->operatorTypeSpecifyingExtensionRegistryProvider = $operatorTypeSpecifyingExtensionRegistryProvider;
		$this->printer = $printer;
		$this->typeSpecifier = $typeSpecifier;
		$this->propertyReflectionFinder = $propertyReflectionFinder;
		$this->parser = $parser;
		$this->nodeScopeResolver = $nodeScopeResolver;
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->dynamicConstantNames = $container->getParameter('dynamicConstantNames');
		$this->phpVersion = $phpVersion;
	}

	/**
	 * @param  array<string, Type> $constantTypes
	 * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection|null $function
	 * @param \PHPStan\Analyser\VariableTypeHolder[] $variablesTypes
	 * @param \PHPStan\Analyser\VariableTypeHolder[] $moreSpecificTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, Type> $nativeExpressionTypes
	 * @param array<\PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection> $inFunctionCallsStack
	 *
	 */
	public function create(
		ScopeContext $context,
		bool $declareStrictTypes = false,
		array $constantTypes = [],
		$function = null,
		?string $namespace = null,
		array $variablesTypes = [],
		array $moreSpecificTypes = [],
		array $conditionalExpressions = [],
		?string $inClosureBindScopeClass = null,
		?ParametersAcceptor $anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $nativeExpressionTypes = [],
		array $inFunctionCallsStack = [],
		bool $afterExtractCall = false,
		?Scope $parentScope = null
	): MutatingScope
	{
		$scopeClass = $this->scopeClass;
		if (!is_a($scopeClass, MutatingScope::class, true)) {
			throw new \PHPStan\ShouldNotHappenException();
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
			$nativeExpressionTypes,
			$inFunctionCallsStack,
			$this->dynamicConstantNames,
			$this->treatPhpDocTypesAsCertain,
			$afterExtractCall,
			$parentScope
		);
	}

}
