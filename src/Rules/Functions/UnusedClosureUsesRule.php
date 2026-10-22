<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function is_string;
use function sprintf;

/**
 * @implements Rule<VariableWritesNode>
 */
#[RegisteredRule(level: 1)]
final class UnusedClosureUsesRule implements Rule
{

	public function __construct(
		#[AutowiredParameter(ref: '%featureToggles.reportPreciseLineForUnusedFunctionParameter%')]
		private bool $reportExactLine,
		#[AutowiredParameter(ref: '%featureToggles.unusedParameters%')]
		private bool $reportUnusedFlow,
	)
	{
	}

	public function getNodeType(): string
	{
		return VariableWritesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionLike = $node->getFunctionLike();
		if (!$functionLike instanceof Node\Expr\Closure) {
			return [];
		}
		if ($node->isOpaque()) {
			return [];
		}

		$errors = [];
		foreach ($functionLike->uses as $use) {
			if (!is_string($use->var->name)) {
				continue;
			}
			$message = 'Anonymous function has an unused use $%s.';
			$identifier = 'closure.unusedUse';
			$write = $node->getWriteForNode($use->var);
			if ($write !== null) {
				// a by-value use imports a value - it is unused unless that
				// value is read on some path (overwriting it first is not a use)
				if ($node->isUsed($write) || $node->areAllVariableNamesReferenced()) {
					continue;
				}
				if ($node->isRead($write)) {
					// read, but only into values that never reach a sink - a
					// newer finding than the rule, so bleeding edge only
					if (!$this->reportUnusedFlow || $node->flowsIntoNeverReadWrite($write)) {
						continue;
					}
					$message = 'Anonymous function has a use $%s that only flows into values that are never used.';
					$identifier = 'closure.unusedUseFlow';
				}
			} elseif ($node->isVariableReferenced($use->var->name)) {
				// a by-ref use aliases the outer variable - any mention counts
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf($message, $use->var->name))
				->identifier($identifier);
			if ($this->reportExactLine) {
				$errorBuilder->line($use->var->getStartLine());
			}
			$errors[] = $errorBuilder->build();
		}

		return $errors;
	}

}
