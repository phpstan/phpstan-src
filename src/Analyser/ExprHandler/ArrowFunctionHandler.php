<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ClosureProcessor;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\Generics\ClosureSignatureInference;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function is_string;

/**
 * @implements ExprHandler<ArrowFunction>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ArrowFunctionHandler.cpp')]
final class ArrowFunctionHandler implements ExprHandler
{

	public function __construct(
		private ClosureTypeResolver $closureTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private ClosureProcessor $closureProcessor,
		private ClosureSignatureInference $closureSignatureInference,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ArrowFunction;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$arrowFunctionResult = $this->closureProcessor->processArrowFunctionNode($nodeScopeResolver, $stmt, $expr, $scope, $storage, $nodeCallback, $context->getPassedToType(), $context->getNativePassedToType(), $context);
		$result = $arrowFunctionResult->getExpressionResult();

		// A plain typeCallback recursing through getClosureType() would re-walk
		// the body each getType() ask before the cache populates and hang;
		// ExpressionResult excludes closures from its tracked-type early return.
		// Compute the ClosureType once here and store it as an eager value.
		//
		// Both flavours are built from the arrow function body the single walk in
		// processArrowFunctionNode() already covered, without a second walk: the
		// native flavour reads the body expression's stored native types off the
		// same arrowScope (an arrow's native return type is its body's native type).
		$arrowScope = $arrowFunctionResult->getArrowFunctionScope();
		$type = $this->closureTypeResolver->buildClosureTypeForArrowFunction(
			$scope,
			$expr,
			$arrowScope,
			$arrowFunctionResult->getClosureTypeThrowPoints(),
			$arrowFunctionResult->getClosureTypeImpurePoints(),
			$arrowFunctionResult->getInvalidateExpressions(),
			false,
			$storage,
			$context->getPassedToType(),
			$context->getNativePassedToType(),
		);
		$nativeType = $this->closureTypeResolver->buildClosureTypeForArrowFunction(
			$scope,
			$expr,
			$arrowScope,
			$arrowFunctionResult->getClosureTypeThrowPoints(),
			$arrowFunctionResult->getClosureTypeImpurePoints(),
			$arrowFunctionResult->getInvalidateExpressions(),
			true,
			$storage,
			$context->getPassedToType(),
			$context->getNativePassedToType(),
		);

		return $this->expressionResultFactory->create(
			$result->getScope()->addTemplateArgumentConstraints($this->closureSignatureInference->collectSites($scope, $type)),
			beforeScope: $scope,
			expr: $expr,
			variableFlow: $result->getVariableFlow(),
			hasYield: $result->hasYield(),
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			specifyTypesCallback: fn (TypeSpecifierContext $c, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $c),
			type: $type,
			nativeType: $nativeType,
			typeCallback: null,
		);
	}

	public static function getVariableFlow(ArrowFunction $expr, ExpressionResult $bodyResult): VariableFlow
	{
		$outputs = [];
		foreach ($expr->params as $param) {
			if (!$param->byRef || !$param->var instanceof Expr\Variable || !is_string($param->var->name)) {
				continue;
			}
			$outputs[] = VariableFlow::read($param->var->name);
		}

		return VariableFlow::arrow($expr, $bodyResult->getVariableFlow(), VariableFlow::sequence(...$outputs));
	}

}
