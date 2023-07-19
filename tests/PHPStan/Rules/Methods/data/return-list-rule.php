<?php

namespace ReturnListRule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
/**
 * @implements Rule<BinaryOp>
 */
class BinaryOpEnumValueRule implements Rule
{

	/** @var class-string<BinaryOp> */
	private string $className;

	/**
	 * @param class-string $operator
	 */
	public function __construct(string $operator, ?string $okMessage = null)
	{
		$this->className = $operator;
	}

	public function getNodeType(): string
	{
		return $this->className;
	}

	/**
	 * @param BinaryOp $node
	 * @return list<RuleError>
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$leftType = $scope->getType($node->left);
		$rightType = $scope->getType($node->right);
		$isDirectCompareType = true;

		if (!$this->isEnumWithValue($leftType) || !$this->isEnumWithValue($rightType)) {
			$isDirectCompareType = false;
		}

		$errors = [];
		$leftError = $this->processOpExpression($node->left, $leftType, $node->getOperatorSigil());
		$rightError = $this->processOpExpression($node->right, $rightType, $node->getOperatorSigil());

		if ($leftError !== null) {
			$errors[] = $leftError;
		}

		if ($rightError !== null && $rightError !== $leftError) {
			$errors[] = $rightError;
		}

		if (!$isDirectCompareType && $errors === []) {
			return [];
		}

		if ($isDirectCompareType && $errors === []) {
			$errors[] = sprintf(
				'Cannot compare %s to %s',
				$leftType->describe(VerbosityLevel::typeOnly()),
				$rightType->describe(VerbosityLevel::typeOnly()),
			);
		}

		return array_map(static fn (string $message) => RuleErrorBuilder::message($message)->build(), $errors);
	}

	private function processOpExpression(Expr $expression, Type $expressionType, string $sigil): ?string
	{
		return null;
	}

	private function isEnumWithValue(Type $type): bool
	{
		return false;
	}

}
