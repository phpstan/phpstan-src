<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use RuntimeException;
use function count;
use function is_array;
use function is_int;

/**
 * The ruleScope section of the result cache file: the node classes each analysed file passed to the
 * rules, and the node types of each project rule.
 *
 * The node types of each file name the same few node classes again and again, so the classes are
 * named once in a table and each file refers to them by position, as the dependency graph does
 * with paths.
 */
final class RuleScopeCodec
{

	/**
	 * @param array<string, list<string>> $nodeTypes
	 * @param array<string, list<string>> $ruleNodeTypes
	 * @return array{nodeClasses: list<string>, nodeTypes: array<string, list<int>>, ruleNodeTypes: array<string, list<string>>}
	 */
	public static function encode(array $nodeTypes, array $ruleNodeTypes): array
	{
		$ids = [];
		$nodeClasses = [];
		$encodedNodeTypes = [];
		foreach ($nodeTypes as $file => $fileNodeTypes) {
			$fileNodeTypeIds = [];
			foreach ($fileNodeTypes as $nodeType) {
				if (!isset($ids[$nodeType])) {
					$ids[$nodeType] = count($nodeClasses);
					$nodeClasses[] = $nodeType;
				}
				$fileNodeTypeIds[] = $ids[$nodeType];
			}
			$encodedNodeTypes[$file] = $fileNodeTypeIds;
		}

		return ['nodeClasses' => $nodeClasses, 'nodeTypes' => $encodedNodeTypes, 'ruleNodeTypes' => $ruleNodeTypes];
	}

	/**
	 * @param mixed $ruleScope
	 * @return array{nodeTypes: array<string, list<string>>, ruleNodeTypes: array<string, list<string>>}
	 * @throws RuntimeException when the section is malformed
	 */
	public static function decode($ruleScope): array
	{
		if (
			!is_array($ruleScope)
			|| !is_array($ruleScope['nodeClasses'] ?? null)
			|| !is_array($ruleScope['nodeTypes'] ?? null)
			|| !is_array($ruleScope['ruleNodeTypes'] ?? null)
		) {
			throw new RuntimeException('The rule scope is malformed.');
		}

		$nodeClasses = $ruleScope['nodeClasses'];
		$nodeTypes = [];
		foreach ($ruleScope['nodeTypes'] as $file => $fileNodeTypeIds) {
			if (!is_array($fileNodeTypeIds)) {
				throw new RuntimeException('The rule scope is malformed.');
			}

			$fileNodeTypes = [];
			foreach ($fileNodeTypeIds as $nodeTypeId) {
				if (!is_int($nodeTypeId) || !isset($nodeClasses[$nodeTypeId])) {
					throw new RuntimeException('The rule scope is malformed.');
				}
				$fileNodeTypes[] = $nodeClasses[$nodeTypeId];
			}
			$nodeTypes[$file] = $fileNodeTypes;
		}

		return ['nodeTypes' => $nodeTypes, 'ruleNodeTypes' => $ruleScope['ruleNodeTypes']];
	}

}
