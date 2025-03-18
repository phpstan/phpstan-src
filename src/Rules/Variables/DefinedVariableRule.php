<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Variable>
 */
final class DefinedVariableRule implements Rule
{

	public function __construct(
		private bool $cliArgumentsVariablesRegistered,
		private bool $checkMaybeUndefinedVariables,
	)
	{
	}

	public function getNodeType(): string
	{
		return Variable::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		if (is_string($node->name)) {
			$variableNames = [$node->name];
		} else {
			$fetchType = $scope->getType($node->name);
			$variableNames = array_map(static fn (ConstantStringType $type): string => $type->getValue(), $fetchType->getConstantStrings());
			$fetchStringType = $fetchType->toString();
			if (! $fetchStringType->isString()->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot access variable with a non-stringable type %s.', $fetchType->describe(VerbosityLevel::typeOnly())))
					->identifier('variable.fetchInvalidExpression')
					->build();
			}
		}

		foreach ($variableNames as $name) {
			$errors = array_merge($errors, $this->processSingleVariable($scope, $node, $name));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleVariable(Scope $scope, Variable $node, string $variableName): array
	{
		if ($this->cliArgumentsVariablesRegistered && in_array($variableName, [
			'argc',
			'argv',
		], true)) {
			$isInMain = !$scope->isInClass() && !$scope->isInAnonymousFunction() && $scope->getFunction() === null;
			if ($isInMain) {
				return [];
			}
		}

		if ($scope->isInExpressionAssign($node) || $scope->isUndefinedExpressionAllowed($node)) {
			return [];
		}

		if ($scope->hasVariableType($variableName)->no()) {
			return [
				RuleErrorBuilder::message(sprintf('Undefined variable: $%s', $variableName))
					->identifier('variable.undefined')
					->build(),
			];
		} elseif (
			$this->checkMaybeUndefinedVariables
			&& !$scope->hasVariableType($variableName)->yes()
		) {
			return [
				RuleErrorBuilder::message(sprintf('Variable $%s might not be defined.', $variableName))
					->identifier('variable.undefined')
					->build(),
			];
		}

		return [];
	}

}
