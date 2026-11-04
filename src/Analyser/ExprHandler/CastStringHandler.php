<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Analyser\VariableFlowBuilder;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<Cast\String_>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/CastStringHandler.cpp')]
final class CastStringHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Cast\String_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
		$impurePoints = $exprResult->getImpurePoints();
		$throwPoints = $exprResult->getThrowPoints();

		$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->expr, $scope, $exprResult);
		$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());

		$scope = $exprResult->getScope();

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			variableFlow: VariableFlow::sequence($exprResult->getVariableFlow(), VariableFlowBuilder::throws($expr, $throwPoints)),
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: fn (bool $nativeTypesPromoted): Type => $this->initializerExprTypeResolver->getCastType($expr, static function (Expr $e) use ($nativeTypesPromoted, $expr, $exprResult): Type {
				if ($e === $expr->expr) {
					return $nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType();
				}

					throw new ShouldNotHappenException();
			}),
			specifyTypesCallback: function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $exprResult, $beforeScope): SpecifiedTypes {
				if ($context->null()) {
					return new SpecifiedTypes([], []);
				}

				// the cast is falsy when it is '' or '0', not only for the values loosely equal to ''
				$falsyContext = $context->negate();
				if ($falsyContext->true()) {
					$falsyType = TypeCombinator::union(
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantIntegerType(0),
						// -0.0 casts to '-0'
						new ConstantFloatType(0.0),
						new ConstantStringType(''),
						new ConstantStringType('0'),
						// a Stringable object casts to its string
						new ObjectWithoutClassType(),
					);
				} else {
					$falsyType = TypeCombinator::union(
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantIntegerType(0),
						new ConstantStringType(''),
						new ConstantStringType('0'),
					);
				}

				$evaluationScope = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;

				return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $expr->expr, $exprResult, $falsyType, $falsyContext)->setRootExpr($expr);
			},
			dependencies: $exprResult->getDependencies(),
		);
	}

}
