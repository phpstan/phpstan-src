<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\VerbosityLevel;
use function is_int;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<VariableWritesNode>
 */
final class UnusedVariableRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
		private ExprPrinter $exprPrinter,
	)
	{
	}

	public function getNodeType(): string
	{
		return VariableWritesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->isOpaque()) {
			return [];
		}

		$errors = [];
		foreach ($node->getWrites() as $write) {
			$name = $write->getVariableName();
			if ($node->isUntracked($name)) {
				continue;
			}
			if ($node->isUsed($write)) {
				continue;
			}
			if (str_starts_with($name, '_')) {
				continue;
			}
			if (
				$write->getKind() === VariableWrite::KIND_CATCH
				&& !$this->phpVersion->supportsNoncapturingCatches()
			) {
				continue;
			}

			$parentId = $write->getParentId();
			if ($parentId !== null) {
				// an item of a literal array: reported on its own only when the
				// array as a whole is used, otherwise the assignment is reported
				$parent = $node->getWrite($parentId);
				if ($parent === null || !$node->isUsed($parent)) {
					continue;
				}
				$offset = $write->getOffset();
				if ($offset === null) {
					continue;
				}
				$offsetType = is_int($offset) ? new ConstantIntegerType($offset) : new ConstantStringType($offset);
				$message = sprintf('Offset %s of array assigned to variable $%s is never used.', $offsetType->describe(VerbosityLevel::value()), $name);
			} elseif ($write->isOffsetWrite()) {
				$target = $write->getNode();
				if (!$target instanceof Node\Expr) {
					continue;
				}
				$message = sprintf('Value assigned to %s is never used.', $this->exprPrinter->printExpr($target));
			} else {
				$message = sprintf('Value assigned to variable $%s is never used.', $name);
			}

			$errors[] = RuleErrorBuilder::message($message)
				->identifier('variable.unused')
				->line($write->getNode()->getStartLine())
				->build();
		}

		return $errors;
	}

}
