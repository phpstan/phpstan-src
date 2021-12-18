<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use function is_a;

class LazyScopeFactory implements ScopeFactory
{

	private string $scopeClass;

	private Container $container;

	/** @var string[] */
	private array $dynamicConstantNames;

	private bool $treatPhpDocTypesAsCertain;

	public function __construct(
		string $scopeClass,
		Container $container,
	)
	{
		$this->scopeClass = $scopeClass;
		$this->container = $container;
		$this->dynamicConstantNames = $container->getParameter('dynamicConstantNames');
		$this->treatPhpDocTypesAsCertain = $container->getParameter('treatPhpDocTypesAsCertain');
	}

	/**
	 * @param array<string, Type> $constantTypes
	 * @param VariableTypeHolder[] $variablesTypes
	 * @param VariableTypeHolder[] $moreSpecificTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param array<string, true> $currentlyAssignedExpressions
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
			$this->container->getByType(ReflectionProvider::class),
			$this->container->getByType(DynamicReturnTypeExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(OperatorTypeSpecifyingExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(Standard::class),
			$this->container->getByType(TypeSpecifier::class),
			$this->container->getByType(PropertyReflectionFinder::class),
			$this->container->getService('currentPhpVersionSimpleParser'),
			$this->container->getByType(NodeScopeResolver::class),
			$context,
			$this->container->getByType(PhpVersion::class),
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
			$parentScope,
		);
	}

}
