<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function in_array;

#[AutowiredService]
final class OutputBufferHelper
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	private const LEVEL_INCREMENTING_FUNCTIONS = ['ob_start'];

	private const LEVEL_DECREMENTING_FUNCTIONS = ['ob_get_clean', 'ob_get_flush', 'ob_end_clean', 'ob_end_flush'];

	public function getLevelDelta(string $functionName): int
	{
		if (in_array($functionName, self::LEVEL_INCREMENTING_FUNCTIONS, true)) {
			return 1;
		}

		if (in_array($functionName, self::LEVEL_DECREMENTING_FUNCTIONS, true)) {
			return -1;
		}

		return 0;
	}

	public function applyLevelDelta(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, int $delta): MutatingScope
	{
		foreach ([new Name('ob_get_level'), new Name\FullyQualified('ob_get_level')] as $name) {
			$obGetLevelCall = new FuncCall($name, []);

			// a tracked ob_get_level() holder answers from scope state; only an
			// untracked one prices the synthetic call
			$scope = $scope->assignExpression(
				$obGetLevelCall,
				$this->addDelta($nodeScopeResolver->readScopeStateOrSyntheticType($obGetLevelCall, $scope), $delta),
				$this->addDelta($nodeScopeResolver->readScopeStateOrSyntheticType($obGetLevelCall, $scope->doNotTreatPhpDocTypesAsCertain()), $delta),
			);
		}

		return $scope;
	}

	/** Sums the tracked level type with the delta without walking a synthetic node. */
	private function addDelta(Type $levelType, int $delta): Type
	{
		return $this->initializerExprTypeResolver->getPlusType(
			new TypeExpr($levelType),
			new TypeExpr(new ConstantIntegerType($delta)),
			static fn (Expr $expr): Type => $expr instanceof TypeExpr ? $expr->getExprType() : new MixedType(),
		);
	}

}
