<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<FileNode>
 */
final class NoPhpCodeRule implements Rule
{

	public function getNodeType(): string
	{
		return FileNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->getNodes()) !== 1) {
			return [];
		}

		$html = $node->getNodes()[0];
		if (!$html instanceof Node\Stmt\InlineHTML) {
			return [];
		}

		return [
			RuleErrorBuilder::message('The example does not contain any PHP code. Did you forget the opening <?php tag?')
				->identifier('phpstanPlayground.noPhp')
				->build(),
		];
	}

}
