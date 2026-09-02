<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use Override;
use PhpParser\Node;
use PHPStan\Analyser\ResultCache\ResultCacheDependencyExtension;
use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;

/**
 * Marker collector for per-file result-cache dependency records emitted through CollectedDataEmitter.
 * Do not register this class as a collector.
 *
 * Emitted records contain only the extension and dependency keys. ResultCacheManager calculates and
 * adds their hashes immediately before persisting the result cache.
 *
 * @phpstan-type ResultCacheDependencyData array{extensionKey: string, dependencyKey: string}
 * @implements Collector<never, ResultCacheDependencyData>
 */
final class ResultCacheDependencyCollector implements Collector
{

	/**
	 * @api
	 * @return ResultCacheDependencyData
	 */
	public static function createData(ResultCacheDependencyExtension $extension, string $dependencyKey): array
	{
		return [
			'extensionKey' => $extension->getKey(),
			'dependencyKey' => $dependencyKey,
		];
	}

	#[Override]
	public function getNodeType(): string
	{
		throw new ShouldNotHappenException();
	}

	#[Override]
	public function processNode(Node $node, Scope $scope): ?array
	{
		throw new ShouldNotHappenException();
	}

}
