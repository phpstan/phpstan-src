<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use WeakReference;

#[GenerateFactory(interface: InternalScopeFactoryFactory::class, resultType: LazyInternalScopeFactory::class)]
final class LazyInternalScopeFactory implements InternalScopeFactory
{

	/** @var int|array{min: int, max: int}|null */
	private int|array|null $phpVersion;

	private Parser $currentSimpleVersionParser;

	private ?ReflectionProvider $reflectionProvider = null;

	private ?InitializerExprTypeResolver $initializerExprTypeResolver = null;

	/** @var ExtensionsCollection<ExpressionTypeResolverExtension>|null */
	private ?ExtensionsCollection $expressionTypeResolverExtensions = null;

	private ?ExprPrinter $exprPrinter = null;

	private ?TypeSpecifier $typeSpecifier = null;

	private ?PropertyReflectionFinder $propertyReflectionFinder = null;

	private ?ConstantResolver $constantResolver = null;

	private ExpressionResultStorageStack $expressionResultStorageStack;

	private ?PhpVersion $phpVersionType = null;

	private ?AttributeReflectionFactory $attributeReflectionFactory = null;

	private ?self $twin = null;

	/** @var WeakReference<self>|null */
	private ?WeakReference $origin = null;

	/**
	 * @param callable(Node $node, Scope $scope): void|null $nodeCallback
	 */
	public function __construct(
		private Container $container,
		private $nodeCallback,
		private bool $createsNodeCallbackScopes = false,
		?ExpressionResultStorageStack $expressionResultStorageStack = null,
	)
	{
		$this->phpVersion = $this->container->getParameter('phpVersion');
		$this->currentSimpleVersionParser = $this->container->getService('currentPhpVersionSimpleParser');
		$this->expressionResultStorageStack = $expressionResultStorageStack ?? new ExpressionResultStorageStack();
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
		array $resultProvenance = [],
	): MutatingScope
	{
		$className = MutatingScope::class;
		if ($this->createsNodeCallbackScopes) {
			$className = NodeCallbackScope::class;
		}

		$this->reflectionProvider ??= $this->container->getByType(ReflectionProvider::class);
		$this->initializerExprTypeResolver ??= $this->container->getByType(InitializerExprTypeResolver::class);
		$this->expressionTypeResolverExtensions ??= $this->container->getExtensionsCollection(ExpressionTypeResolverExtension::class);
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
			$this->expressionTypeResolverExtensions,
			$this->exprPrinter,
			$this->typeSpecifier,
			$this->propertyReflectionFinder,
			$this->currentSimpleVersionParser,
			$this->constantResolver,
			$this->expressionResultStorageStack,
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
			$resultProvenance,
		);
	}

	public function toNodeCallbackScopeFactory(): InternalScopeFactory
	{
		return $this->createsNodeCallbackScopes ? $this : $this->twin();
	}

	public function toWalkScopeFactory(): InternalScopeFactory
	{
		return $this->createsNodeCallbackScopes ? $this->twin() : $this;
	}

	/**
	 * The factory for the other scope flavour, created once. Scopes switch
	 * flavour constantly, and a fresh factory would start with empty memos —
	 * resolving every service above out of the container again on its first
	 * create().
	 *
	 * The pair is held one way strongly and the other way weakly: a factory
	 * belongs to a single analysed file (ScopeFactory::create() makes one per
	 * file, closing over that file's node callback), and a strong cycle here
	 * would keep every file's callback alive for the whole run — PHPStan runs
	 * with the cycle collector disabled, so nothing would ever free it.
	 */
	private function twin(): self
	{
		if ($this->twin !== null) {
			return $this->twin;
		}

		if ($this->origin !== null) {
			$origin = $this->origin->get();
			if ($origin !== null) {
				return $origin;
			}
		}

		$this->twin = new self($this->container, $this->nodeCallback, !$this->createsNodeCallbackScopes, $this->expressionResultStorageStack);
		$this->twin->origin = WeakReference::create($this);

		return $this->twin;
	}

}
