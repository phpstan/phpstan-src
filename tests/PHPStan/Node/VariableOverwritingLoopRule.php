<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<VariableWritesNode>
 */
class VariableOverwritingLoopRule implements Rule
{

	public function getNodeType(): string
	{
		return VariableWritesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->getWrites() as $write) {
			$loop = $node->getVariableOverwritingLoop($write);
			if ($loop === null) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s overwrites $%s.',
				$loop instanceof Foreach_ ? 'Foreach' : 'For loop',
				$write->getVariableName(),
			))
				->identifier('tests.variableOverwritingLoop')
				->line($loop->getStartLine())
				->build();
		}

		return $errors;
	}

}
