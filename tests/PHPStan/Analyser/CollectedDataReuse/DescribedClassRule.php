<?php declare(strict_types = 1);

namespace PHPStan\Analyser\CollectedDataReuse;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function file_get_contents;
use function sprintf;

/**
 * Reads a file without declaring it, like a rule asking a class for a member that a class
 * reflection extension described from that file and declared through DeclarationDependencyTracker.
 *
 * @implements Rule<CollectedDataNode>
 */
final class DescribedClassRule implements Rule
{

	public int $runs = 0;

	public function __construct(private string $file)
	{
	}

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$this->runs++;

		$contents = file_get_contents($this->file);

		return [
			RuleErrorBuilder::message(sprintf('Class is described by %s.', $contents === false ? 'nothing' : $contents))
				->file(__FILE__)
				->line(1)
				->identifier('tests.describedClass')
				->build(),
		];
	}

}
