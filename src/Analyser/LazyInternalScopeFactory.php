<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Analyser\Fiber\FiberScope;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\GenerateFactory;
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
use PHPStan\Type\ExpressionTypeResolverExtensionRegistry;

#[GenerateFactory(interface: InternalScopeFactoryFactory::class, resultType: LazyInternalScopeFactory::class)]
final class LazyInternalScopeFactory implements InternalScopeFactory
{

	/** @var int|array{min: int, max: int}|null */
	private int|array|null $phpVersion;

	private Parser $currentSimpleVersionParser;

	private ?ReflectionProvider $reflectionProvider = null;

	private ?InitializerExprTypeResolver $initializerExprTypeResolver = null;

	private ?ExpressionTypeResolverExtensionRegistry $expressionTypeResolverExtensionRegistry = null;

	private ?ExprPrinter $exprPrinter = null;

	private ?TypeSpecifier $typeSpecifier = null;

	private ?PropertyReflectionFinder $propertyReflectionFinder = null;

	private ?ConstantResolver $constantResolver = null;

	private ?PhpVersion $phpVersionType = null;

	private ?AttributeReflectionFactory $attributeReflectionFactory = null;

	/**
	 * @param callable(Node $node, Scope $scope): void|null $nodeCallback
	 */
	public function __construct(
		private Container $container,
		private $nodeCallback,
		private bool $fiber = false,
		private bool $resultAware = false,
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
		?MutatingScope $parentScope = null,
		bool $nativeTypesPromoted = false,
	): MutatingScope
	{
		$className = MutatingScope::class;
		if ($this->fiber) {
			$className = FiberScope::class;
		} elseif ($this->resultAware) {
			$className = ResultAwareScope::class;
		}

		$this->reflectionProvider ??= $this->container->getByType(ReflectionProvider::class);
		$this->initializerExprTypeResolver ??= $this->container->getByType(InitializerExprTypeResolver::class);
		$this->expressionTypeResolverExtensionRegistry ??= $this->container->getByType(ExpressionTypeResolverExtensionRegistryProvider::class)->getRegistry();
		$this->exprPrinter ??= $this->container->getByType(ExprPrinter::class);
		$this->typeSpecifier ??= $this->container->getByType(TypeSpecifier::class);
		$this->propertyReflectionFinder ??= $this->container->getByType(PropertyReflectionFinder::class);

		$this->constantResolver ??= $this->container->getByType(ConstantResolver::class);

		$this->phpVersionType ??= $this->container->getByType(PhpVersion::class);
		$this->attributeReflectionFactory ??= $this->container->getByType(AttributeReflectionFactory::class);

		return new $className(
			$this->container,
			$this,
			$this->reflectionProvider,
			$this->initializerExprTypeResolver,
			$this->expressionTypeResolverExtensionRegistry,
			$this->exprPrinter,
			$this->typeSpecifier,
			$this->propertyReflectionFinder,
			$this->currentSimpleVersionParser,
			$this->constantResolver,
			$context,
			$this->phpVersionType,
			$this->attributeReflectionFactory,
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

	public function toResultAwareFactory(): InternalScopeFactory
	{
		return new self($this->container, $this->nodeCallback, false, true);
	}

}
