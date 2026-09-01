<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use function sprintf;

#[AutowiredService]
final class ImplicitToStringCallHelper
{

	public function __construct(
		private PhpVersion $phpVersion,
		private MethodThrowPointHelper $methodThrowPointHelper,
		private MethodCallReturnTypeHelper $methodCallReturnTypeHelper,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	/**
	 * @param ExpressionResult $exprResult the already-computed result of $expr -
	 *     every caller processed it on $scope, so this helper reads its type
	 *     directly instead of re-walking via Scope::getType()
	 */
	public function processImplicitToStringCall(Expr $expr, MutatingScope $scope, ExpressionResult $exprResult): ExpressionResult
	{
		$throwPoints = [];
		$impurePoints = [];

		$exprType = $exprResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);

		$toStringMethod = null;
		if (!$exprType->isObject()->no()) {
			$toStringMethod = $scope->getMethodReflection($exprType, '__toString');
		}
		if ($toStringMethod === null) {
			return $this->expressionResultFactory->create(
				$scope,
				beforeScope: $scope,
				expr: $expr,
				hasYield: false,
				isAlwaysTerminating: false,
				throwPoints: [],
				impurePoints: [],
				typeCallback: static fn () => new MixedType(),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			);
		}

		if (!$toStringMethod->hasSideEffects()->no()) {
			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'methodCall',
				sprintf('call to method %s::%s()', $toStringMethod->getDeclaringClass()->getDisplayName(), $toStringMethod->getName()),
				$toStringMethod->isPure()->no(),
			);
		}

		if ($this->phpVersion->throwsOnStringCast()) {
			// the __toString() call's return type resolves directly (the receiver
			// type is already in hand); the fabricated node is only the payload
			// dynamic extensions receive - nothing walks it
			$toStringCall = new Expr\MethodCall($expr, new Identifier('__toString'), attributes: [TemplateArgumentFrame::SYNTHETIC_SITE_ATTRIBUTE => true]);
			if ($scope->nativeTypesPromoted) {
				$toStringReturnType = ParametersAcceptorSelector::combineAcceptors($toStringMethod->getVariants())->getNativeReturnType();
			} else {
				$toStringReturnType = $this->methodCallReturnTypeHelper->methodCallReturnType($scope, $exprType, '__toString', $toStringCall) ?? new ErrorType();
			}
			$throwPoint = $this->methodThrowPointHelper->getThrowPoint(
				$toStringMethod,
				$toStringMethod->getOnlyVariant(),
				$toStringCall,
				$scope,
				ExpressionContext::createDeep(),
				$toStringReturnType,
			);
			if ($throwPoint !== null) {
				$throwPoints[] = $throwPoint;
			}
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: static fn () => new MixedType(),
			specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
		);
	}

}
