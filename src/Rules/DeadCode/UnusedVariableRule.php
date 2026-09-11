<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\VerbosityLevel;
use function in_array;
use function is_int;
use function sprintf;
use function str_starts_with;
use function ucfirst;

/**
 * @implements Rule<VariableWritesNode>
 */
final class UnusedVariableRule implements Rule
{

	public function __construct(private ExprPrinter $exprPrinter)
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

		$namesWithReadWrite = [];
		$writesById = [];
		foreach ($node->getWrites() as $write) {
			$writesById[$write->getId()] = $write;
			if (!$node->isRead($write)) {
				continue;
			}
			$namesWithReadWrite[$write->getVariableName()] = true;
		}

		$errors = [];
		foreach ($node->getWrites() as $write) {
			$name = $write->getVariableName();
			if ($node->isUntracked($name)) {
				continue;
			}
			if (str_starts_with($name, '_')) {
				continue;
			}
			if (in_array($write->getKind(), [VariableWrite::KIND_PARAMETER, VariableWrite::KIND_CLOSURE_USE], true)) {
				// reported by UnusedConstructorParametersRule and UnusedClosureUsesRule
				continue;
			}
			if (
				$write->getKind() === VariableWrite::KIND_CATCH
				&& !$scope->getPhpVersion()->supportsNoncapturingCatches()->yes()
			) {
				continue;
			}

			$redundantType = $node->getRedundantType($write);
			if ($redundantType !== null && $node->isUsed($write)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Variable $%s is assigned value %s but it already has that value.',
					$name,
					$redundantType->describe(VerbosityLevel::value()),
				))
					->identifier('assign.redundant')
					->line($write->getNode()->getStartLine())
					->build();
				continue;
			}

			if ($node->isUsed($write)) {
				continue;
			}

			$unusedVariable = !isset($namesWithReadWrite[$name]) && !$node->isVariableEverRead($name);
			$parentId = $write->getParentId();
			if ($parentId !== null) {
				$parent = $writesById[$parentId] ?? null;
				if ($parent === null || !$node->isUsed($parent) || $write->getOffset() === null) {
					continue;
				}
				$offset = $write->getOffset();
				$offsetType = is_int($offset) ? new ConstantIntegerType($offset) : new ConstantStringType($offset);
				$message = sprintf(
					'Offset %s of array assigned to variable $%s %s.',
					$offsetType->describe(VerbosityLevel::value()),
					$name,
					$node->isRead($write) ? 'only flows into values that are never used' : 'is never read',
				);
				$identifier = $node->isRead($write) ? 'array.unusedOffsetFlow' : 'array.unusedOffset';
			} elseif ($write->isOffsetWrite()) {
				$target = $write->getNode();
				if (!$target instanceof Node\Expr) {
					throw new ShouldNotHappenException();
				}
				$message = $this->getMessage($write->getKind(), $this->exprPrinter->printExpr($target), false, $node->isRead($write));
				$identifier = $this->getIdentifier($write->getKind(), false, $node->isRead($write));
			} else {
				$message = $this->getMessage($write->getKind(), 'variable $' . $name, $unusedVariable, $node->isRead($write));
				$identifier = $this->getIdentifier($write->getKind(), $unusedVariable, $node->isRead($write));
			}
			$errors[] = RuleErrorBuilder::message($message)
				->identifier($identifier)
				->line($write->getNode()->getStartLine())
				->build();
		}

		return $errors;
	}

	/**
	 * @param VariableWrite::KIND_* $kind
	 */
	private function getMessage(int $kind, string $target, bool $unusedVariable, bool $read): string
	{
		// "never read": nothing looks at the written value; "only flows into
		// values that are never used": something reads it, but only to compute
		// values that never reach a sink themselves
		$outcome = $read ? 'only flows into values that are never used' : 'is never read';
		switch ($kind) {
			case VariableWrite::KIND_ASSIGN:
			case VariableWrite::KIND_READ_MODIFY_WRITE:
			case VariableWrite::KIND_ARRAY_DIM_WRITE:
			case VariableWrite::KIND_LIST_ITEM:
				if ($unusedVariable) {
					return sprintf('%s is never read.', ucfirst($target));
				}

				return sprintf('Value assigned to %s %s.', $target, $outcome);
			case VariableWrite::KIND_PRE_INC:
			case VariableWrite::KIND_POST_INC:
				return sprintf('Value of %s after ++ %s.', $target, $outcome);
			case VariableWrite::KIND_PRE_DEC:
			case VariableWrite::KIND_POST_DEC:
				return sprintf('Value of %s after -- %s.', $target, $outcome);
			case VariableWrite::KIND_FOREACH_VALUE:
				return sprintf('Foreach value %s %s.', $target, $outcome);
			case VariableWrite::KIND_FOREACH_KEY:
				return sprintf('Foreach key %s %s.', $target, $outcome);
			case VariableWrite::KIND_CATCH:
				return sprintf('Catch %s %s.', $target, $outcome);
		}

		throw new ShouldNotHappenException(sprintf('Unhandled variable write kind %d', $kind));
	}

	/**
	 * @param VariableWrite::KIND_* $kind
	 * @return 'variable.unused'|'assign.unused'|'assign.unusedFlow'|'preInc.unused'|'preInc.unusedFlow'|'postInc.unused'|'postInc.unusedFlow'|'preDec.unused'|'preDec.unusedFlow'|'postDec.unused'|'postDec.unusedFlow'|'foreach.unusedValue'|'foreach.unusedValueFlow'|'foreach.unusedKey'|'foreach.unusedKeyFlow'|'catch.unusedVariable'|'catch.unusedVariableFlow'
	 */
	private function getIdentifier(int $kind, bool $unusedVariable, bool $read): string
	{
		switch ($kind) {
			case VariableWrite::KIND_ASSIGN:
			case VariableWrite::KIND_READ_MODIFY_WRITE:
			case VariableWrite::KIND_ARRAY_DIM_WRITE:
			case VariableWrite::KIND_LIST_ITEM:
				if ($unusedVariable) {
					return 'variable.unused';
				}

				return $read ? 'assign.unusedFlow' : 'assign.unused';
			case VariableWrite::KIND_PRE_INC:
				return $read ? 'preInc.unusedFlow' : 'preInc.unused';
			case VariableWrite::KIND_POST_INC:
				return $read ? 'postInc.unusedFlow' : 'postInc.unused';
			case VariableWrite::KIND_PRE_DEC:
				return $read ? 'preDec.unusedFlow' : 'preDec.unused';
			case VariableWrite::KIND_POST_DEC:
				return $read ? 'postDec.unusedFlow' : 'postDec.unused';
			case VariableWrite::KIND_FOREACH_VALUE:
				return $read ? 'foreach.unusedValueFlow' : 'foreach.unusedValue';
			case VariableWrite::KIND_FOREACH_KEY:
				return $read ? 'foreach.unusedKeyFlow' : 'foreach.unusedKey';
			case VariableWrite::KIND_CATCH:
				return $read ? 'catch.unusedVariableFlow' : 'catch.unusedVariable';
		}

		throw new ShouldNotHappenException(sprintf('Unhandled variable write kind %d', $kind));
	}

}
