<?php

declare(strict_types=1);

namespace ResultCacheE2E\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\ResultCacheDependencyCollector;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use RuntimeException;
use function array_key_exists;
use function file_put_contents;
use function is_array;
use function sprintf;
use const LOCK_EX;

/** @implements Rule<CollectedDataNode> */
final class ResultCacheDependencyDataRule implements Rule
{
	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$hashed = 0;
		$unhashed = 0;
		foreach ($node->get(ResultCacheDependencyCollector::class) as $records) {
			foreach ($records as $record) {
				if ($this->hasHash($record)) {
					$hashed++;
					continue;
				}

				$unhashed++;
			}
		}
		if (file_put_contents(
			__DIR__ . '/../tmp/collected-data.log',
			sprintf("hashed=%d unhashed=%d\n", $hashed, $unhashed),
			LOCK_EX,
		) === false) {
			throw new RuntimeException('Could not record the result-cache dependency data shape.');
		}

		return [];
	}

	private function hasHash(mixed $record): bool
	{
		return is_array($record) && array_key_exists('hash', $record);
	}
}
