<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use function sprintf;

#[AutowiredService]
final class ToStringThrowPointHelper
{

	public function __construct(
		private PhpVersion $phpVersion,
		private MethodThrowPointHelper $methodThrowPointHelper,
	)
	{
	}

	/**
	 * @return array{list<InternalThrowPoint>, list<ImpurePoint>}
	 */
	public function getToStringThrowAndImpurePoints(Expr $expr, MutatingScope $scope): array
	{
		$throwPoints = [];
		$impurePoints = [];

		$exprType = $scope->getType($expr);
		$toStringMethod = $scope->getMethodReflection($exprType, '__toString');
		if ($toStringMethod === null) {
			return [[], []];
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

		return [$throwPoints, $impurePoints];
	}

}
