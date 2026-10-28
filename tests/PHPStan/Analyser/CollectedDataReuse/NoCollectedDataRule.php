<?php declare(strict_types = 1);

namespace PHPStan\Analyser\CollectedDataReuse;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function file_get_contents;
use function sprintf;

/**
 * Asks only whether the analysis is files-only, and reads a file, like a rule that runs a tool over
 * the whole project. It tracks the file when told to.
 *
 * @implements Rule<CollectedDataNode>
 */
final class NoCollectedDataRule implements Rule
{

	public int $runs = 0;

	public function __construct(private string $file, private bool $track)
	{
	}

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$this->runs++;

		if ($node->isOnlyFilesAnalysis()) {
			return [];
		}

		if ($this->track) {
			$scope->trackFileDependency($this->file);
		}

		$contents = file_get_contents($this->file);

		return [
			RuleErrorBuilder::message(sprintf('Tool says %s.', $contents === false ? 'nothing' : $contents))
				->file(__FILE__)
				->line(1)
				->identifier('tests.noCollectedData')
				->build(),
		];
	}

}
