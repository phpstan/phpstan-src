<?php declare(strict_types = 1);

namespace IgnoreErrorsPartialAnalysisRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function str_starts_with;

/**
 * @implements Collector<Node\Stmt\Class_, int>
 */
class DeadCodeCollector implements Collector
{

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		if ($node->name === null || !str_starts_with($node->name->toString(), 'Dead')) {
			return null;
		}

		return $node->getStartLine();
	}

}

/**
 * Like a dead code rule, it knows the usages only when the whole project is analysed.
 *
 * @implements Rule<CollectedDataNode>
 */
class DeadCodeRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->isOnlyFilesAnalysis()) {
			return [];
		}

		$errors = [];
		foreach ($node->get(DeadCodeCollector::class) as $file => $lines) {
			foreach ($lines as $line) {
				$errors[] = RuleErrorBuilder::message('Class is never used.')
					->identifier('e2e.deadCode')
					->file($file)
					->line($line)
					->build();
			}
		}

		return $errors;
	}

}
