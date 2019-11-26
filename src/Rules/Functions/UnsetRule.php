<?php declare(strict_types=1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\IterableType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Unset_>
 */
class UnsetRule implements \PHPStan\Rules\Rule
{

	/** @var bool */
	private $checkMaybeUndefinedVariables;

	public function __construct(bool $checkMaybeUndefinedVariables)
	{
		$this->checkMaybeUndefinedVariables = $checkMaybeUndefinedVariables;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Unset_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Node\Stmt\Unset_) {
			return [];
		}

		/** @var Node\Stmt\Unset_ $node */
		$functionArguments = $node->vars;
		$messages = [];

		foreach ($functionArguments as $argument) {
			$this->canBeUnset($argument, $scope, $messages);
		}

		return $messages;
	}

	/**
	 * @param Node $node
	 * @param Scope $scope
	 * @param string[] $messages
	 */
	private function canBeUnset(Node $node, Scope $scope, array &$messages): void
	{
		if ($node instanceof Node\Expr\Variable && is_string($node->name)) {
			$scopeHasVariable = $scope->hasVariableType($node->name);

			if ($scopeHasVariable->no()) {
				$messages[] = RuleErrorBuilder::message(
					sprintf('Call to function unset() contains undefined variable $%s.', $node->name)
				)->line($node->getLine())->build();
			} elseif ($this->checkMaybeUndefinedVariables && $scopeHasVariable->maybe()) {
				$messages[] = RuleErrorBuilder::message(
					sprintf('Call to function unset() contains possibly undefined variable $%s.', $node->name)
				)->line($node->getLine())->build();
			}
		} elseif ($node instanceof Node\Expr\ArrayDimFetch && $node->dim !== null) {
			$type = $scope->getType($node->var);
			$dimType = $scope->getType($node->dim);

			$isInaccessibleIterable = $type instanceof IterableType && $type->getIterableKeyType()->isSuperTypeOf($dimType)->no();

			if ($isInaccessibleIterable || $type->hasOffsetValueType($dimType)->no()) {
				$messages[] = RuleErrorBuilder::message(
					sprintf(
						'Cannot unset offset %s on %s.',
						$dimType->describe(VerbosityLevel::value()),
						$type->describe(VerbosityLevel::value())
					)
				)->line($node->getLine())->build();
			}
		}
	}

}
