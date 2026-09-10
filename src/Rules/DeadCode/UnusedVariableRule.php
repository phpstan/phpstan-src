<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\VerbosityLevel;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<VariableWritesNode>
 */
final class UnusedVariableRule implements Rule
{

	public function __construct()
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
		foreach ($node->getWrites() as $write) {
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

			if (!$node->isRead($write)) {
				// A variable that is never read at all (an unused variable) is a stronger
				// finding than a single dead store to a variable the body does read.
				$unusedVariable = !isset($namesWithReadWrite[$name]) && !$node->isVariableEverRead($name);
				$errors[] = RuleErrorBuilder::message($this->getMessage($write->getKind(), $name, $unusedVariable))
					->identifier($this->getIdentifier($write->getKind(), $unusedVariable))
					->line($write->getVariable()->getStartLine())
					->build();
				continue;
			}

			$redundantType = $node->getRedundantType($write);
			if ($redundantType === null) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Variable $%s is assigned value %s but it already has that value.',
				$name,
				$redundantType->describe(VerbosityLevel::value()),
			))
				->identifier('assign.redundant')
				->line($write->getVariable()->getStartLine())
				->build();
		}

		return $errors;
	}

	/**
	 * @param VariableWrite::KIND_* $kind
	 */
	private function getMessage(int $kind, string $variableName, bool $unusedVariable): string
	{
		switch ($kind) {
			case VariableWrite::KIND_ASSIGN:
			case VariableWrite::KIND_READ_MODIFY_WRITE:
			case VariableWrite::KIND_ARRAY_DIM_WRITE:
			case VariableWrite::KIND_LIST_ITEM:
				if ($unusedVariable) {
					return sprintf('Variable $%s is never read.', $variableName);
				}

				return sprintf('Value assigned to variable $%s is never read.', $variableName);
			case VariableWrite::KIND_PRE_INC:
			case VariableWrite::KIND_POST_INC:
				return sprintf('Value of variable $%s after ++ is never read.', $variableName);
			case VariableWrite::KIND_PRE_DEC:
			case VariableWrite::KIND_POST_DEC:
				return sprintf('Value of variable $%s after -- is never read.', $variableName);
			case VariableWrite::KIND_FOREACH_VALUE:
				return sprintf('Foreach value variable $%s is never read.', $variableName);
			case VariableWrite::KIND_FOREACH_KEY:
				return sprintf('Foreach key variable $%s is never read.', $variableName);
			case VariableWrite::KIND_CATCH:
				return sprintf('Catch variable $%s is never read.', $variableName);
		}

		throw new ShouldNotHappenException(sprintf('Unhandled variable write kind %d', $kind));
	}

	/**
	 * @param VariableWrite::KIND_* $kind
	 * @return 'variable.unused'|'assign.unused'|'preInc.unused'|'postInc.unused'|'preDec.unused'|'postDec.unused'|'foreach.unusedValue'|'foreach.unusedKey'|'catch.unusedVariable'
	 */
	private function getIdentifier(int $kind, bool $unusedVariable): string
	{
		switch ($kind) {
			case VariableWrite::KIND_ASSIGN:
			case VariableWrite::KIND_READ_MODIFY_WRITE:
			case VariableWrite::KIND_ARRAY_DIM_WRITE:
			case VariableWrite::KIND_LIST_ITEM:
				if ($unusedVariable) {
					return 'variable.unused';
				}

				return 'assign.unused';
			case VariableWrite::KIND_PRE_INC:
				return 'preInc.unused';
			case VariableWrite::KIND_POST_INC:
				return 'postInc.unused';
			case VariableWrite::KIND_PRE_DEC:
				return 'preDec.unused';
			case VariableWrite::KIND_POST_DEC:
				return 'postDec.unused';
			case VariableWrite::KIND_FOREACH_VALUE:
				return 'foreach.unusedValue';
			case VariableWrite::KIND_FOREACH_KEY:
				return 'foreach.unusedKey';
			case VariableWrite::KIND_CATCH:
				return 'catch.unusedVariable';
		}

		throw new ShouldNotHappenException(sprintf('Unhandled variable write kind %d', $kind));
	}

}
