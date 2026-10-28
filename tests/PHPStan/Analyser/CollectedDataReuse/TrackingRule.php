<?php declare(strict_types = 1);

namespace PHPStan\Analyser\CollectedDataReuse;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\ResultCache\FileResultCacheValueExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function file_get_contents;
use function glob;
use function sprintf;

/**
 * Reads two files and the files in a directory that are not analysed, and declares them - the
 * second file through trackValueDependency() with the extension behind trackFileDependency().
 *
 * @implements Rule<CollectedDataNode>
 */
final class TrackingRule implements Rule
{

	public int $runs = 0;

	public function __construct(private string $file, private string $directory, private string $valueFile)
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

		// a rule that does not ask for collected data runs every time
		$node->get(FirstCollector::class);
		$scope->trackFileDependency($this->file);
		$scope->trackDirectoryDependency($this->directory, '*.txt');
		$scope->trackValueDependency(FileResultCacheValueExtension::class, $this->valueFile);

		$contents = file_get_contents($this->file);
		$files = glob($this->directory . '/*.txt');
		$value = file_get_contents($this->valueFile);

		return [
			RuleErrorBuilder::message(sprintf('File says %s, directory has %d files, value is %s.', $contents === false ? 'nothing' : $contents, $files === false ? 0 : count($files), $value === false ? 'nothing' : $value))
				->file(__FILE__)
				->line(1)
				->identifier('tests.tracking')
				->build(),
		];
	}

}
