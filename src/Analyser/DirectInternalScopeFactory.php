<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Analyser\Fiber\FiberScope;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
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

final class DirectInternalScopeFactory implements InternalScopeFactory
{

	private ExpressionResultStorageStack $expressionResultStorageStack;

	/**
	 * @param int|array{min: int, max: int}|null $configPhpVersion
	 * @param callable(Node $node, Scope $scope): void|null $nodeCallback
	 * @param ExtensionsCollection<ExpressionTypeResolverExtension> $expressionTypeResolverExtensions
	 */
	public function __construct(
		private Container $container,
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ExtensionsCollection $expressionTypeResolverExtensions,
		private ExprPrinter $exprPrinter,
		private TypeSpecifier $typeSpecifier,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private Parser $parser,
		private PhpVersion $phpVersion,
		private AttributeReflectionFactory $attributeReflectionFactory,
		private int|array|null $configPhpVersion,
		private $nodeCallback,
		private ConstantResolver $constantResolver,
		private bool $fiber = false,
		?ExpressionResultStorageStack $expressionResultStorageStack = null,
	)
	{
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
	): MutatingScope
	{
		$className = MutatingScope::class;
		if ($this->fiber) {
			$className = FiberScope::class;
		}

		return new $className(
			$this->container,
			$this,
			$this->reflectionProvider,
			$this->initializerExprTypeResolver,
			$this->expressionTypeResolverExtensions,
			$this->exprPrinter,
			$this->typeSpecifier,
			$this->propertyReflectionFinder,
			$this->parser,
			$this->constantResolver,
			$this->expressionResultStorageStack,
			$context,
			$this->phpVersion,
			$this->attributeReflectionFactory,
			$this->configPhpVersion,
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
		return $this->withFlavor(true);
	}

	public function toMutatingFactory(): InternalScopeFactory
	{
		return $this->withFlavor(false);
	}

	private function withFlavor(bool $fiber): self
	{
		return new self(
			$this->container,
			$this->reflectionProvider,
			$this->initializerExprTypeResolver,
			$this->expressionTypeResolverExtensions,
			$this->exprPrinter,
			$this->typeSpecifier,
			$this->propertyReflectionFinder,
			$this->parser,
			$this->phpVersion,
			$this->attributeReflectionFactory,
			$this->configPhpVersion,
			$this->nodeCallback,
			$this->constantResolver,
			$fiber,
			$this->expressionResultStorageStack,
		);
	}

}
