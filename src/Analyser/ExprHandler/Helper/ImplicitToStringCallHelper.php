<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use function sprintf;

#[AutowiredService]
final class ImplicitToStringCallHelper
{

	public function __construct(
		private PhpVersion $phpVersion,
		private MethodThrowPointHelper $methodThrowPointHelper,
	)
	{
	}

	public function processImplicitToStringCall(Expr $expr, MutatingScope $scope): ExpressionResult
	{
		$throwPoints = [];
		$impurePoints = [];

		$exprType = $scope->getType($expr);

		$toStringMethod = null;
		if (!$exprType->isObject()->no()) {
			$toStringMethod = $scope->getMethodReflection($exprType, '__toString');
		}
		if ($toStringMethod === null) {
			return new ExpressionResult(
				$scope,
				hasYield: false,
				isAlwaysTerminating: false,
				throwPoints: [],
				impurePoints: [],
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
			$throwPoint = $this->methodThrowPointHelper->getThrowPoint(
				$toStringMethod,
				$toStringMethod->getOnlyVariant(),
				new Expr\MethodCall($expr, new Identifier('__toString')),
				$scope,
			);
			if ($throwPoint !== null) {
				$throwPoints[] = $throwPoint;
			}
		}

		return new ExpressionResult(
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

}
