<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\ExpressionTypeResolverExtension;

final class DirectInternalScopeFactoryFactory implements InternalScopeFactoryFactory
{

	/**
	 * @param int|array{min: int, max: int}|null $configPhpVersion
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
			$nodeCallback,
			$this->constantResolver,
		);
	}

}
