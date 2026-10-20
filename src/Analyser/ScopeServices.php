<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\ExpressionTypeResolverExtension;

/**
 * What every scope a factory creates shares: the services, the expression
 * result storage stack and the node callback. A self-analysis creates
 * hundreds of thousands of scopes - one reference to this object instead of
 * a property per service each spares copying them into every scope and
 * releasing them from it.
 */
final class ScopeServices
{

	/**
	 * @param ExtensionsCollection<ExpressionTypeResolverExtension> $expressionTypeResolverExtensions
	 * @param (callable(Node $node, Scope $scope): void)|null $nodeCallback
	 */
	public function __construct(
		public readonly Container $container,
		public readonly ReflectionProvider $reflectionProvider,
		public readonly InitializerExprTypeResolver $initializerExprTypeResolver,
		public readonly ExtensionsCollection $expressionTypeResolverExtensions,
		public readonly ExprPrinter $exprPrinter,
		public readonly TypeSpecifier $typeSpecifier,
		public readonly PropertyReflectionFinder $propertyReflectionFinder,
		public readonly Parser $parser,
		public readonly ConstantResolver $constantResolver,
		public readonly ExpressionResultStorageStack $expressionResultStorageStack,
		public readonly PhpVersion $phpVersion,
		public readonly AttributeReflectionFactory $attributeReflectionFactory,
		public readonly ConfiguredPhpVersionRangeHelper $configuredPhpVersionRangeHelper,
		public readonly mixed $nodeCallback,
	)
	{
	}

}
