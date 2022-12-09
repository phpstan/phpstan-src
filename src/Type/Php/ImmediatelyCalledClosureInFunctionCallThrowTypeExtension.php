<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_keys;
use function in_array;

class ImmediatelyCalledClosureInFunctionCallThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	private const FUNCTION_ARGUMENTS = [
		'array_reduce' => [1],
		'array_intersect_ukey' => [2],
		'array_uintersect' => [2],
		'array_uintersect_assoc' => [2],
		'array_intersect_uassoc' => [2],
		'array_uintersect_uassoc' => [2, 3],
		'array_diff_ukey' => [2],
		'array_udiff' => [2],
		'array_udiff_assoc' => [2],
		'array_diff_uassoc' => [2],
		'array_udiff_uassoc' => [2, 3],
		'array_filter' => [1],
		'array_map' => [0],
		'array_walk_recursive' => [1],
		'array_walk' => [1],
		'uasort' => [1],
		'uksort' => [1],
		'usort' => [1],

	];

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
	)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
	): bool
	{
		return in_array(
			$functionReflection->getName(),
			array_keys(self::FUNCTION_ARGUMENTS),
			true,
		);
	}

	public function getThrowTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		return $this->combineCallbackAndFunctionThrowTypes($functionCall, $functionReflection, $scope);
	}

	private function combineCallbackAndFunctionThrowTypes(FuncCall $funcCall, FunctionReflection $functionReflection, Scope $scope): ?Type
	{
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException('Unexpected scope implementation');
		}

		$argumentPositions = self::FUNCTION_ARGUMENTS[$functionReflection->getName()];
		$throwTypes = $functionReflection->getThrowType() !== null
			? [$functionReflection->getThrowType()]
			: [];

		foreach ($argumentPositions as $argumentPosition) {
			$args = $funcCall->getArgs();
			if (!isset($args[$argumentPosition])) {
				continue;
			}
			$closure = $args[$argumentPosition]->value;
			if (!$closure instanceof Closure) {
				continue;
			}

			$result = $this->nodeScopeResolver->processStmtNodes(
				$funcCall,
				$closure->getStmts(),
				$scope->enterAnonymousFunction($closure),
				static function (): void {
				},
			);

			foreach ($result->getThrowPoints() as $throwPoint) {
				$throwTypes[] = $throwPoint->getType();
			}
		}

		if ($throwTypes === []) {
			return null;
		}

		return TypeCombinator::union(...$throwTypes);
	}

}
