<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Analyser\Fiber\FiberScope;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\ClosureType;

#[GenerateFactory(interface: InternalScopeFactoryFactory::class, resultType: LazyInternalScopeFactory::class)]
final class LazyInternalScopeFactory implements InternalScopeFactory
{

	/** @var int|array{min: int, max: int}|null */
	private int|array|null $phpVersion;

	private Parser $currentSimpleVersionParser;

	/**
	 * @param callable(Node $node, Scope $scope): void|null $nodeCallback
	 */
	public function __construct(
		private Container $container,
		private $nodeCallback,
		private bool $fiber = false,
	)
	{
		$this->phpVersion = $this->container->getParameter('phpVersion');
		$this->currentSimpleVersionParser = $this->container->getService('currentPhpVersionSimpleParser');
	}

	public function create(
		ScopeContext $context,
		bool $declareStrictTypes = false,
		PhpFunctionFromParserNodeReflection|null $function = null,
		?string $namespace = null,
		array $expressionTypes = [],
		array $nativeExpressionTypes = [],
		array $conditionalExpressions = [],
		array $inClosureBindScopeClasses = [],
		?ClosureType $anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $currentlyAllowedUndefinedExpressions = [],
		array $inFunctionCallsStack = [],
		bool $afterExtractCall = false,
		?Scope $parentScope = null,
		bool $nativeTypesPromoted = false,
	): MutatingScope
	{
		$className = MutatingScope::class;
		if ($this->fiber) {
			$className = FiberScope::class;
		}

		return new $className(
			$this,
			$this->container->getByType(ReflectionProvider::class),
			$this->container->getByType(InitializerExprTypeResolver::class),
			$this->container->getByType(DynamicReturnTypeExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(ExpressionTypeResolverExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(ExprPrinter::class),
			$this->container->getByType(TypeSpecifier::class),
			$this->container->getByType(PropertyReflectionFinder::class),
			$this->currentSimpleVersionParser,
			$this->container->getByType(NodeScopeResolver::class),
			$this->container->getByType(RicherScopeGetTypeHelper::class),
			$this->container->getByType(ConstantResolver::class),
			$context,
			$this->container->getByType(PhpVersion::class),
			$this->container->getByType(AttributeReflectionFactory::class),
			$this->phpVersion,
			$this->nodeCallback,
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

	public function toFiberFactory(): InternalScopeFactory
	{
		return new self($this->container, $this->nodeCallback, true);
	}

	public function toMutatingFactory(): InternalScopeFactory
	{
		return new self($this->container, $this->nodeCallback, false);
	}

}
