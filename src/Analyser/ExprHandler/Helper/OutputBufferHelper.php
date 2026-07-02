<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Type\Constant\ConstantIntegerType;
use function in_array;

/**
 * Tracks the output-buffering nesting level (the value of ob_get_level())
 * across calls. The level is stored in scope as the type of the
 * ob_get_level() expression. Calls to ob_start()/ob_end_*() shift it by a
 * known delta, but impure code that PHPStan cannot inspect (user functions,
 * methods, callables) may open or close buffers, so the tracked value must be
 * forgotten after such calls.
 */
final class OutputBufferHelper
{

	private const LEVEL_INCREMENTING_FUNCTIONS = ['ob_start'];

	private const LEVEL_DECREMENTING_FUNCTIONS = ['ob_get_clean', 'ob_get_flush', 'ob_end_clean', 'ob_end_flush'];

	public static function getLevelDelta(string $functionName): int
	{
		if (in_array($functionName, self::LEVEL_INCREMENTING_FUNCTIONS, true)) {
			return 1;
		}

		if (in_array($functionName, self::LEVEL_DECREMENTING_FUNCTIONS, true)) {
			return -1;
		}

		return 0;
	}

	public static function applyLevelDelta(MutatingScope $scope, int $delta): MutatingScope
	{
		foreach ([new Name('ob_get_level'), new Name\FullyQualified('ob_get_level')] as $name) {
			$obGetLevelCall = new FuncCall($name, []);

			$scope = $scope->assignExpression(
				$obGetLevelCall,
				$scope->getType(new BinaryOp\Plus(
					new TypeExpr($scope->getType($obGetLevelCall)),
					new TypeExpr(new ConstantIntegerType($delta)),
				)),
				$scope->getType(new BinaryOp\Plus(
					new TypeExpr($scope->getNativeType($obGetLevelCall)),
					new TypeExpr(new ConstantIntegerType($delta)),
				)),
			);
		}

		return $scope;
	}

	/**
	 * Whether the output-buffering level is currently narrowed in scope. Cheap
	 * O(1) check used to skip the level-tracking work for the common case of
	 * code that never touches output buffering.
	 */
	public static function isLevelTracked(MutatingScope $scope): bool
	{
		foreach ([new Name('ob_get_level'), new Name\FullyQualified('ob_get_level')] as $name) {
			if (!$scope->hasExpressionType(new FuncCall($name, []))->no()) {
				return true;
			}
		}

		return false;
	}

	public static function invalidateLevel(MutatingScope $scope): MutatingScope
	{
		foreach ([new Name('ob_get_level'), new Name\FullyQualified('ob_get_level')] as $name) {
			$scope = $scope->invalidateExpression(new FuncCall($name, []));
		}

		return $scope;
	}

}
