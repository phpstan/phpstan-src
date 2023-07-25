<?php declare(strict_types = 1);

namespace PHPStan\Rules\Ignore;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<FileNode>
 */
class IgnoreParseErrorRule implements Rule
{

	public function getNodeType(): string
	{
		return FileNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$nodes = $node->getNodes();
		if (count($nodes) === 0) {
			return [];
		}

		$firstNode = $nodes[0];
		$parseErrors = $firstNode->getAttribute('linesToIgnoreParseErrors', []);
		$errors = [];
		foreach ($parseErrors as $line => $lineParseErrors) {
			foreach ($lineParseErrors as $parseError) {
				$errors[] = RuleErrorBuilder::message(sprintf('Parse error in @phpstan-ignore: %s', $parseError))
					->line($line)
					->identifier('phpstan.ignore.parseError')
					->nonIgnorable()
					->build();
			}
		}

		return $errors;
	}

}
