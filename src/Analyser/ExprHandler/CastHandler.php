<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<Cast>
 */
#[AutowiredService]
final class CastHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private IdenticalNarrowingHelper $identicalNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Cast && !$expr instanceof Cast\String_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
		$scope = $exprResult->getScope();

		$subjectArgResult = $this->identicalNarrowingHelper->captureFirstArgResult($expr->expr, $storage);

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			typeCallback: function (bool $nativeTypesPromoted) use ($expr, $exprResult): Type {
				if ($expr instanceof Cast\Unset_) {
					return new NullType();
				}

				return $this->initializerExprTypeResolver->getCastType($expr, static function (Expr $e) use ($nativeTypesPromoted, $expr, $exprResult): Type {
					if ($e === $expr->expr) {
						return $nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType();
					}

					throw new ShouldNotHappenException();
				});
			},
			specifyTypesCallback: function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $exprResult, $nodeScopeResolver, $beforeScope, $subjectArgResult): SpecifiedTypes {
				$evaluationScope = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				// a cast's truthiness is a loose comparison of the inner
				// expression - composed from its result; the fabricated
				// literal is only printed into entries, never walked
				if (($expr instanceof Cast\Bool_ || $expr instanceof Cast\Int_ || $expr instanceof Cast\Double) && !$context->null()) {
					if ($expr instanceof Cast\Bool_) {
						$literal = new ConstFetch(new FullyQualified('true'));
						$equalContext = $context;
					} elseif ($expr instanceof Cast\Int_) {
						$literal = new Int_(0);
						$equalContext = $context->negate();
					} else {
						$literal = new Float_(0.0);
						$equalContext = $context->negate();
					}

					// the literal side never reads its stand-in result
					$types = $this->identicalNarrowingHelper->specifyEqual($nodeScopeResolver, $expr->expr, $literal, $exprResult, $exprResult, $equalContext, $evaluationScope, $subjectArgResult, null);
					if ($types !== null) {
						return $types->setRootExpr($expr);
					}
				}

				if ($expr instanceof Cast\Bool_) {
					return $evaluationScope->obtainResultForNode(new Equal($expr->expr, new ConstFetch(new FullyQualified('true'))))->getSpecifiedTypes($context, $nativeTypesPromoted)->setRootExpr($expr);
				}

				if ($expr instanceof Cast\Int_) {
					return $evaluationScope->obtainResultForNode(new NotEqual($expr->expr, new Int_(0)))->getSpecifiedTypes($context, $nativeTypesPromoted)->setRootExpr($expr);
				}

				if ($expr instanceof Cast\Double) {
					return $evaluationScope->obtainResultForNode(new NotEqual($expr->expr, new Float_(0.0)))->getSpecifiedTypes($context, $nativeTypesPromoted)->setRootExpr($expr);
				}

				return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
			},
		);
	}

}
