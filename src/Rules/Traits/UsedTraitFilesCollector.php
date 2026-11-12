<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\CollectorWithPaths;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use function array_keys;
use function array_map;

/**
 * The files of the traits analysed as a part of a class: the ones it uses, also through other traits,
 * but not the ones of its parents, whose errors are reported in the parent. Registered regardless of the level,
 * also with customRulesetUsed: it decides which unmatched ignoreErrors entries are reported in a partial analysis.
 *
 * @implements CollectorWithPaths<InClassNode, non-empty-list<string>>
 */
#[AutowiredService]
final class UsedTraitFilesCollector implements CollectorWithPaths
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		$files = [];
		$visited = [];
		$this->collectTraitFiles($node->getClassReflection(), $files, $visited);
		if ($files === []) {
			return null;
		}

		return array_keys($files);
	}

	/**
	 * @param non-empty-list<string> $data
	 * @param callable(string): string $transformPath
	 * @return non-empty-list<string>
	 */
	public static function transformCollectedDataPaths($data, callable $transformPath)
	{
		return array_map($transformPath, $data);
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
