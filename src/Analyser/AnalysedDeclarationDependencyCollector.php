<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Collectors\CollectorWithPaths;
use PHPStan\ShouldNotHappenException;

/**
 * Identifies the collected data of what is collected about the declarations in the analysed file depends on,
 * like whether a function is pure: a file that has to be analysed too. Unlike AnalysedFileDependencyCollector,
 * nothing reported in the file depends on it, only what is reported in the files depending on the file.
 * UnmatchedIgnoredErrorJudge follows it from the files reached through AnalysedFileDependencyCollector data.
 * It is not registered as a collector, the data is emitted with CollectedDataEmitter.
 *
 * @implements CollectorWithPaths<never, string>
 */
final class AnalysedDeclarationDependencyCollector implements CollectorWithPaths
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
	 * @param string $data
	 * @param callable(string): string $transformPath
	 * @return string
	 */
	public static function transformCollectedDataPaths($data, callable $transformPath)
	{
		return $transformPath($data);
	}

}
