<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Collectors\CollectorWithPaths;
use PHPStan\ShouldNotHappenException;

/**
 * Identifies the collected data of what is reported in the analysed file depends on: a file that has to be
 * analysed too, or null for the whole project. UnmatchedIgnoredErrorJudge does not report the ignoreErrors
 * entries of the file as unmatched in a partial analysis that misses them. What the collected data
 * of the declarations in a file depends on is AnalysedDeclarationDependencyCollector data. It is not registered as a collector,
 * the data is emitted with CollectedDataEmitter.
 *
 * @implements CollectorWithPaths<never, string|null>
 */
final class AnalysedFileDependencyCollector implements CollectorWithPaths
{

	public function getNodeType(): string
	{
		throw new ShouldNotHappenException();
	}

	public function processNode(Node $node, Scope $scope)
	{
		throw new ShouldNotHappenException();
	}

	/**
	 * @param string|null $data
	 * @param callable(string): string $transformPath
	 * @return string|null
	 */
	public static function transformCollectedDataPaths($data, callable $transformPath)
	{
		return $data === null ? null : $transformPath($data);
	}

}
