<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use function is_string;

/**
 * Resolves which variable names a `Variable` node can refer to.
 *
 * For variable variables like `$$name` or `${$name}` the names come from the
 * constant strings the name expression can evaluate to. Each name is paired with
 * the scope narrowed by that name so callers see the variable types belonging to it.
 */
final class VariableNameResolver
{

	/**
	 * Returns null when the names cannot be determined.
	 *
	 * @return non-empty-list<array{string, Scope}>|null
	 */
	public static function resolveNamesWithScopes(Scope $scope, Variable $variable): ?array
	{
		if (is_string($variable->name)) {
			return [[$variable->name, $scope]];
		}

		$namesWithScopes = [];
		foreach ($scope->getType($variable->name)->getConstantStrings() as $constantString) {
			$name = $constantString->getValue();
			$namesWithScopes[] = [
				$name,
				$scope->filterByTruthyValue(new Identical($variable->name, new String_($name))),
			];
		}

		if ($namesWithScopes === []) {
			return null;
		}

		return $namesWithScopes;
	}

}
