<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
use function array_unique;
use function array_values;

/**
 * Allows extensions to declare that the currently analyzed file depends on
 * an external (non-analyzed) file. When that external file changes, only
 * the dependent analyzed files are re-analyzed instead of the entire project.
 *
 * This is an alternative to ResultCacheMetaExtension for cases where
 * external data changes should not cause full cache invalidation.
 *
 * @api
 */
#[AutowiredService]
final class ExternalFileDependencyRegistrar
{

	/** @var list<string> */
	private array $currentFileDependencies = [];

	/**
	 * Register a dependency on an external file for the currently analyzed file.
	 */
	public function add(string $externalFilePath): void
	{
		$this->currentFileDependencies[] = $externalFilePath;
	}

	/**
	 * @return list<string>
	 * @internal Used by FileAnalyser after each file analysis
	 */
	public function getAndReset(): array
	{
		$deps = array_values(array_unique($this->currentFileDependencies));
		$this->currentFileDependencies = [];

		return $deps;
	}

}
