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
final class CallToFunctionStatementWithoutSideEffectsRule implements Rule
{

	private const SIDE_EFFECT_FLIP_PARAMETERS = [
		// functionName => [name, pos, testName]
		'print_r' => ['return', 1, 'isTruthy'],
		'var_export' => ['return', 1, 'isTruthy'],
		'highlight_string' => ['return', 1, 'isTruthy'],

	];

	public const PHPSTAN_TESTING_FUNCTIONS = [
		'PHPStan\\dumpType',
		'PHPStan\\Testing\\assertType',
		'PHPStan\\Testing\\assertNativeType',
		'PHPStan\\Testing\\assertVariableCertainty',
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

		if (in_array($functionName, self::PHPSTAN_TESTING_FUNCTIONS, true)) {
			return [];
		}

		if (isset(self::SIDE_EFFECT_FLIP_PARAMETERS[$functionName])) {
			[
				$flipParameterName,
				$flipParameterPosition,
				$testName,
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

			if (!$sideEffectFlipped) {
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
