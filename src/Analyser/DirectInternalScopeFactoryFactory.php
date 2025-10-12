<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;

final class DirectInternalScopeFactoryFactory implements InternalScopeFactoryFactory
{

	/**
	 * @param int|array{min: int, max: int}|null $configPhpVersion
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider,
		private ExpressionTypeResolverExtensionRegistryProvider $expressionTypeResolverExtensionRegistryProvider,
		private ExprPrinter $exprPrinter,
		private TypeSpecifier $typeSpecifier,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private Parser $parser,
		private NodeScopeResolver $nodeScopeResolver,
		private RicherScopeGetTypeHelper $richerScopeGetTypeHelper,
		private PhpVersion $phpVersion,
		private AttributeReflectionFactory $attributeReflectionFactory,
		private int|array|null $configPhpVersion,
		private ConstantResolver $constantResolver,
	)
	{
	}

	/**
	 * @param callable(Node $node, Scope $scope): void|null $nodeCallback
	 */
	public function create(?callable $nodeCallback): DirectInternalScopeFactory
	{
		return new DirectInternalScopeFactory(
			$this->reflectionProvider,
			$this->initializerExprTypeResolver,
			$this->dynamicReturnTypeExtensionRegistryProvider,
			$this->expressionTypeResolverExtensionRegistryProvider,
			$this->exprPrinter,
			$this->typeSpecifier,
			$this->propertyReflectionFinder,
			$this->parser,
			$this->nodeScopeResolver,
			$this->richerScopeGetTypeHelper,
			$this->phpVersion,
			$this->attributeReflectionFactory,
			$this->configPhpVersion,
			$nodeCallback,
			$this->constantResolver,
		);
	}

}
