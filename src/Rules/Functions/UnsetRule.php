<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Unset_>
 */
class UnsetRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Unset_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionArguments = $node->vars;
		$messages = [];

		foreach ($functionArguments as $argument) {
			$message = $this->canBeUnset($argument, $scope);

			if (!$message) {
				continue;
			}

			$messages[] = $message;
		}

		return $messages;
	}

	private function canBeUnset(Node $node, Scope $scope): ?string
	{
		if ($node instanceof Node\Expr\Variable && is_string($node->name)) {
			$scopeHasVariable = $scope->hasVariableType($node->name);

			if ($scopeHasVariable->no()) {
				return RuleErrorBuilder::message(
					sprintf('Call to function unset() contains undefined variable $%s.', $node->name)
				)->line($node->getLine())->build()->getMessage();
			}
		} elseif ($node instanceof Node\Expr\ArrayDimFetch && $node->dim !== null) {
			$type = $scope->getType($node->var);
			$dimType = $scope->getType($node->dim);

			$isOffsetAccessible = !$type->isOffsetAccessible()->no() && $type->getIterableKeyType()->isSuperTypeOf($dimType)->no();

			if ($isOffsetAccessible || $type->hasOffsetValueType($dimType)->no()) {
				return RuleErrorBuilder::message(
					sprintf(
						'Cannot unset offset %s on %s.',
						$dimType->describe(VerbosityLevel::value()),
						$type->describe(VerbosityLevel::value())
					)
				)->line($node->getLine())->build()->getMessage();
			}
		}

		return null;
	}

}
