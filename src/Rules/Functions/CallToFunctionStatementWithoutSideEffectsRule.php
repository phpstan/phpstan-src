<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\VoidType;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
class CallToFunctionStatementWithoutSideEffectsRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->expr instanceof Node\Expr\FuncCall) {
			return [];
		}

		$funcCall = $node->expr;
		if (!($funcCall->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
			return [];
		}

		$function = $this->reflectionProvider->getFunction($funcCall->name, $scope);
		if ($function->hasSideEffects()->no() || $node->expr->isFirstClassCallable()) {
			if (!$node->expr->isFirstClassCallable()) {
				$throwsType = $function->getThrowType();
				if ($throwsType !== null && !$throwsType instanceof VoidType) {
					return [];
				}
			}

			$functionResult = $scope->getType($funcCall);
			if ($functionResult instanceof NeverType && $functionResult->isExplicit()) {
				return [];
			}

			if (in_array($function->getName(), [
				'PHPStan\\dumpType',
				'PHPStan\\Testing\\assertType',
				'PHPStan\\Testing\\assertNativeType',
				'PHPStan\\Testing\\assertVariableCertainty',
			], true)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Call to function %s() on a separate line has no effect.',
					$function->getName(),
				))->build(),
			];
		}

		return [];
	}

}
