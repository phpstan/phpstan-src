<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
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
		$functionName = $function->getName();
		$functionHasSideEffects = !$function->hasSideEffects()->no();

		if (in_array($functionName, [
			'PHPStan\\dumpType',
			'PHPStan\\Testing\\assertType',
			'PHPStan\\Testing\\assertNativeType',
			'PHPStan\\Testing\\assertVariableCertainty',
		], true)) {
			return [];
		}

		if ($functionName === 'file_get_contents') {
			$hasNamedParameter = false;
			foreach ($funcCall->getRawArgs() as $i => $arg) {
				if (!$arg instanceof Arg) {
					return [];
				}

				$isContextParameter = false;

				if ($arg->name !== null) {
					$hasNamedParameter = true;

					if ($arg->name->name === 'context') {
						$isContextParameter = true;
					}
				}

				if (!$hasNamedParameter && $i === 2) {
					$isContextParameter = true;
				}

				if ($isContextParameter && !$scope->getType($arg->value)->isNull()->yes()) {
					return [];
				}
			}

			$functionHasSideEffects = false;
		}

		if (!$functionHasSideEffects || $node->expr->isFirstClassCallable()) {
			if (!$node->expr->isFirstClassCallable()) {
				$throwsType = $function->getThrowType();
				if ($throwsType !== null && !$throwsType->isVoid()->yes()) {
					return [];
				}
			}

			$functionResult = $scope->getType($funcCall);
			if ($functionResult instanceof NeverType && $functionResult->isExplicit()) {
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
