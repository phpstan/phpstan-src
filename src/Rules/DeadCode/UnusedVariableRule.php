<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<VariableWritesNode>
 */
final class UnusedVariableRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
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
			if ($node->isRead($write)) {
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

			$errors[] = RuleErrorBuilder::message(sprintf('Value assigned to variable $%s is never read.', $name))
				->identifier('variable.unused')
				->line($write->getVariable()->getStartLine())
				->build();
		}

		return $errors;
	}

}
