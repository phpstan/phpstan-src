<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Analyser\Generics\TemplateArgumentConstraints;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use WeakReference;

#[GenerateFactory(interface: InternalScopeFactoryFactory::class, resultType: LazyInternalScopeFactory::class)]
#[ReferencedByTurboExtension(key: 'lazyInternalScopeFactory')]
final class LazyInternalScopeFactory implements InternalScopeFactory
{

	private Parser $currentSimpleVersionParser;

	private ExpressionResultStorageStack $expressionResultStorageStack;

	/** The services the created scopes share, built on the first create(). */
	private ?ScopeServices $services = null;

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
		?TemplateArgumentFrame $templateArgumentFrame = null,
		?TemplateArgumentConstraints $templateArgumentConstraints = null,
	): MutatingScope
	{
		$className = MutatingScope::class;
		if ($this->createsNodeCallbackScopes) {
			$className = NodeCallbackScope::class;
		}

		$this->services ??= new ScopeServices(
			$this->container,
			$this->container->getByType(ReflectionProvider::class),
			$this->container->getByType(InitializerExprTypeResolver::class),
			$this->container->getExtensionsCollection(ExpressionTypeResolverExtension::class),
			$this->container->getByType(ExprPrinter::class),
			$this->container->getByType(TypeSpecifier::class),
			$this->container->getByType(PropertyReflectionFinder::class),
			$this->currentSimpleVersionParser,
			$this->container->getByType(ConstantResolver::class),
			$this->expressionResultStorageStack,
			$this->container->getByType(PhpVersion::class),
			$this->container->getByType(AttributeReflectionFactory::class),
			$this->container->getByType(ConfiguredPhpVersionRangeHelper::class),
			$this->nodeCallback,
		);

		return new $className(
			$this->services,
			$this,
			$context,
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
			$templateArgumentFrame,
			$templateArgumentConstraints,
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
		// the same services for both flavours: the same container, node
		// callback and storage stack
		$this->twin->services = $this->services;

		return $this->twin;
	}

}
