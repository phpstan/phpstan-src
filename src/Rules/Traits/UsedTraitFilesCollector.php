<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\AnalysedFileDependencyCollector;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use function array_keys;

/**
 * What is reported in the file of a class depends on the traits analysed as a part of it: the ones it uses,
 * also through other traits, but not the ones of its parents, whose errors are reported in the parent.
 * Registered regardless of the level, also with customRulesetUsed: it decides which unmatched ignoreErrors
 * entries are reported in a partial analysis. It collects nothing itself.
 *
 * @implements Collector<InClassNode, never>
 */
#[AutowiredService]
final class UsedTraitFilesCollector implements Collector
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope&CollectedDataEmitter $scope)
	{
		$files = [];
		$visited = [];
		$this->collectTraitFiles($node->getClassReflection(), $files, $visited);
		foreach (array_keys($files) as $file) {
			$scope->emitCollectedData(AnalysedFileDependencyCollector::class, $file);
		}

		return null;
	}

	/**
	 * @param array<string, true> $files
	 * @param array<string, true> $visited
	 */
	private function collectTraitFiles(ClassReflection $classReflection, array &$files, array &$visited): void
	{
		foreach ($classReflection->getTraits() as $trait) {
			if (isset($visited[$trait->getName()])) {
				continue;
			}

			$visited[$trait->getName()] = true;
			$fileName = $trait->getFileName();
			if ($fileName !== null) {
				$files[$fileName] = true;
			}

			$this->collectTraitFiles($trait, $files, $visited);
		}
	}

}
