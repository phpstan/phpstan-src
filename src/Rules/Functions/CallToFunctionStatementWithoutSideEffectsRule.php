<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
class CallToFunctionStatementWithoutSideEffectsRule implements Rule
{

	private const SIDE_EFFECT_FLIP_PARAMETERS = [
		// functionName => [name, pos, testName, defaultHasSideEffect]
		'file_get_contents' => ['context', 2, 'isNotNull', false],
		'print_r' => ['return', 1, 'isTruthy', true],
		'var_export' => ['return', 1, 'isTruthy', true],
	];

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

		if (isset(self::SIDE_EFFECT_FLIP_PARAMETERS[$functionName])) {
			[
				$flipParameterName,
				$flipParameterPosition,
				$testName,
				$defaultHasSideEffect,
			] = self::SIDE_EFFECT_FLIP_PARAMETERS[$functionName];

			$sideEffectFlipped = false;
			$hasNamedParameter = false;
			$checker = [
				'isNotNull' => static fn (Type $type) => $type->isNull()->no(),
				'isTruthy' => static fn (Type $type) => $type->toBoolean()->isTrue()->yes(),
			][$testName];

			foreach ($funcCall->getRawArgs() as $i => $arg) {
				if (!$arg instanceof Arg) {
					return [];
				}

				$isFlipParameter = false;

				if ($arg->name !== null) {
					$hasNamedParameter = true;
					if ($arg->name->name === $flipParameterName) {
						$isFlipParameter = true;
					}
				}

				if (!$hasNamedParameter && $i === $flipParameterPosition) {
					$isFlipParameter = true;
				}

				if ($isFlipParameter) {
					$sideEffectFlipped = $checker($scope->getType($arg->value));
					break;
				}
			}

			if ($sideEffectFlipped xor $defaultHasSideEffect) {
				return [];
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
				))->identifier('function.resultUnused')->build(),
			];
		}

		return [];
	}

}
