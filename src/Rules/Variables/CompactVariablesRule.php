<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
final class CompactVariablesRule implements Rule
{

	private bool $checkMaybeUndefinedVariables;

	public function __construct(bool $checkMaybeUndefinedVariables)
	{
		$this->checkMaybeUndefinedVariables = $checkMaybeUndefinedVariables;
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->name instanceof Node\Expr) {
			return [];
		}

		$functionName = strtolower($node->name->toString());

		if ($functionName !== 'compact') {
			return [];
		}

		$functionArguments = $node->getArgs();
		$messages = [];

		foreach ($functionArguments as $argument) {
			$argumentType = $scope->getType($argument->value);
			$constantStrings = $this->findConstantStrings($argumentType);
			foreach ($constantStrings as $constantString) {
				$variableName = $constantString->getValue();
				$scopeHasVariable = $scope->hasVariableType($variableName);

				if ($scopeHasVariable->no()) {
					$messages[] = RuleErrorBuilder::message(
						sprintf('Call to function compact() contains undefined variable $%s.', $variableName)
					)->line($argument->getLine())->build();
				} elseif ($this->checkMaybeUndefinedVariables && $scopeHasVariable->maybe()) {
					$messages[] = RuleErrorBuilder::message(
						sprintf('Call to function compact() contains possibly undefined variable $%s.', $variableName)
					)->line($argument->getLine())->build();
				}
			}
		}

		return $messages;
	}

	/**
	 * @return array<int, ConstantStringType>
	 */
	private function findConstantStrings(Type $type): array
	{
		if ($type instanceof ConstantStringType) {
			return [$type];
		}

		if ($type instanceof ConstantArrayType) {
			$result = [];
			foreach ($type->getValueTypes() as $valueType) {
				$constantStrings = $this->findConstantStrings($valueType);
				$result = array_merge($result, $constantStrings);
			}

			return $result;
		}

		return [];
	}

}
