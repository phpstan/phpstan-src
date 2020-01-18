<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\Type;

class LazyScopeFactory implements ScopeFactory
{

	/** @var string */
	private $scopeClass;

	/** @var Container */
	private $container;

	/** @var string[] */
	private $dynamicConstantNames;

	public function __construct(
		string $scopeClass,
		Container $container
	)
	{
		$this->scopeClass = $scopeClass;
		$this->container = $container;
		$this->dynamicConstantNames = $container->getParameter('dynamicConstantNames');
	}

	/**
	 * @param \PHPStan\Analyser\ScopeContext $context
	 * @param bool $declareStrictTypes
	 * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection|null $function
	 * @param string|null $namespace
	 * @param \PHPStan\Analyser\VariableTypeHolder[] $variablesTypes
	 * @param \PHPStan\Analyser\VariableTypeHolder[] $moreSpecificTypes
	 * @param string|null $inClosureBindScopeClass
	 * @param \PHPStan\Reflection\ParametersAcceptor|null $anonymousFunctionReflection
	 * @param bool $inFirstLevelStatement
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, Type> $nativeExpressionTypes
	 *
	 * @return MutatingScope
	 */
	public function create(
		ScopeContext $context,
		bool $declareStrictTypes = false,
		$function = null,
		?string $namespace = null,
		array $variablesTypes = [],
		array $moreSpecificTypes = [],
		?string $inClosureBindScopeClass = null,
		?ParametersAcceptor $anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $nativeExpressionTypes = []
	): MutatingScope
	{
		$scopeClass = $this->scopeClass;
		if (!is_a($scopeClass, MutatingScope::class, true)) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return new $scopeClass(
			$this,
			$this->container->getByType(ReflectionProvider::class),
			$this->container->getByType(DynamicReturnTypeExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(OperatorTypeSpecifyingExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(Standard::class),
			$this->container->getByType(TypeSpecifier::class),
			$this->container->getByType(PropertyReflectionFinder::class),
			$context,
			$declareStrictTypes,
			$function,
			$namespace,
			$variablesTypes,
			$moreSpecificTypes,
			$inClosureBindScopeClass,
			$anonymousFunctionReflection,
			$inFirstLevelStatement,
			$currentlyAssignedExpressions,
			$nativeExpressionTypes,
			$this->dynamicConstantNames
		);
	}

}
