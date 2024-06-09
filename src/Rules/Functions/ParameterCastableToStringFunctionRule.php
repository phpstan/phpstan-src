<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class ParameterCastableToStringFunctionRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
		$args = $node->getArgs();
		$errorMessage = 'Parameter #%d of function %s expects an array of values castable to string, %s given.';
		if (in_array($functionName, ['implode', 'join'], true)) {
			$errorMessage = 'Parameter #%d $array of function %s expects array<string>, %s given.';
			if (count($args) === 1) {
				$argsToCheck = [0 => $args[0]];
			} elseif (count($args) === 2) {
				$argsToCheck = [1 => $args[1]];
			} else {
				return [];
			}
		} elseif (in_array($functionName, ['array_intersect', 'array_intersect_assoc', 'array_diff', 'array_diff_assoc'], true)) {
			$argsToCheck = $args;
		} elseif (
			in_array(
				$functionName,
				[
					'array_unique',
					'array_combine',
					'sort',
					'rsort',
					'asort',
					'arsort',
					'natcasesort',
					'natsort',
					'array_count_values',
					'array_fill_keys',
					'array_flip',
				],
				true,
			)
		) {
			$argsToCheck = [0 => $args[0]];
		} else {
			return [];
		}

		$errors = [];

		foreach ($argsToCheck as $argIdx => $arg) {
			if (!$arg instanceof Node\Arg || $arg->unpack) {
				continue;
			}

			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$arg->value,
				'',
				static fn (Type $type): bool => !$type->getIterableValueType()->toString() instanceof ErrorType,
			);

			if ($typeResult->getType() instanceof ErrorType
				|| !$typeResult->getType()->getIterableValueType()->toString() instanceof ErrorType) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(
				sprintf($errorMessage, $argIdx + 1, $functionName, $typeResult->getType()->describe(VerbosityLevel::typeOnly())),
			)->identifier('argument.type')->build();
		}

		return $errors;
	}

}
