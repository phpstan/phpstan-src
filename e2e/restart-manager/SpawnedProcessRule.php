<?php declare(strict_types = 1);

namespace RestartManagerE2E;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Spawns a PHP process the way an extension compiling its own DI container
 * does. It runs in the main process after every worker has ended, so a worker
 * that took the composed ini with it leaves the spawned process with neither
 * the ini nor the scan dir - and without the extensions loaded from them.
 *
 * @implements Rule<CollectedDataNode>
 */
final class SpawnedProcessRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('echo json_encode(get_loaded_extensions());'));
		$spawnedExtensions = json_decode((string) $output, true);
		if (!is_array($spawnedExtensions)) {
			return [RuleErrorBuilder::message(sprintf('The spawned PHP process failed: %s', $output))->identifier('restartManager.spawn')->build()];
		}

		$missing = array_diff(get_loaded_extensions(), $spawnedExtensions);
		if ($missing === []) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'The spawned PHP process is missing extensions %s (PHPRC=%s, %s)',
				implode(', ', $missing),
				(string) getenv('PHPRC'),
				is_file((string) getenv('PHPRC')) ? 'exists' : 'does not exist',
			))->identifier('restartManager.spawn')->build(),
		];
	}

}

/**
 * CollectedDataNode rules only run when something was collected.
 *
 * @implements Collector<FileNode, true>
 */
final class FileCollector implements Collector
{

	public function getNodeType(): string
	{
		return FileNode::class;
	}

	public function processNode(Node $node, Scope $scope): bool
	{
		return true;
	}

}
