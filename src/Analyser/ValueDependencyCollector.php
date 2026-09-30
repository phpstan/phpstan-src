<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function sprintf;

/**
 * Collects the values declared through DependencyEmitter::valueDependency() while a file is being
 * analysed, and what each value was at that moment - the value the analysis saw.
 *
 * A value is identified by its extension and key: the same one declared again, by the same rule
 * or another one, or by another file, is the same dependency, recorded once. Two kinds of
 * dependents are recorded for it:
 *
 * - "analysis": the file being analysed. Its analysis read the value, so a change of it has to
 *   re-analyse this file.
 * - "declarations": the file of the scope, when the dependency is declared outside the walk of
 *   the analysed file, on a scope without a node callback. PHPStan steps outside of it to infer
 *   what a file declares - the type of a private property without a type is inferred from the
 *   constructor of its class, on a scope of the class's file, whenever the property is first
 *   needed, during the analysis of whichever file that is. The inferred type is cached and reused
 *   by the files analysed after it, so it is what the scope's file declares that depends on the
 *   value: a change of it re-analyses the scope's file and every file depending on it.
 *
 * Outside the analysis of a file - in the rules for CollectedDataNode, which run again on every
 * run anyway - nothing is collected.
 *
 * @phpstan-type ValueDependencies = array{
 *     values: array<string, array{string, string, string}>,
 *     dependents: array<string, array{analysis: list<string>, declarations: list<string>}>,
 * }
 */
#[AutowiredService]
final class ValueDependencyCollector
{

	private ?string $analysedFile = null;

	/** @var array<string, array{string, string, string}> id => [extension class, key, value] */
	private array $values = [];

	/** @var array<string, array{analysis: array<string, true>, declarations: array<string, true>}> dependent file => ids */
	private array $dependents = [];

	/**
	 * @param ExtensionsCollection<ResultCacheValueExtension> $valueExtensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: ResultCacheValueExtension::class)]
		private ExtensionsCollection $valueExtensions,
	)
	{
	}

	public static function getId(string $extensionClass, string $key): string
	{
		return $extensionClass . "\0" . $key;
	}

	public function startFile(string $analysedFile): void
	{
		$this->analysedFile = $analysedFile;
		$this->values = [];
		$this->dependents = [$analysedFile => ['analysis' => [], 'declarations' => []]];
	}

	/**
	 * @param class-string<ResultCacheValueExtension> $extensionClass
	 */
	public function record(string $extensionClass, string $key, Scope $scope, bool $insideWalk): void
	{
		if ($this->analysedFile === null) {
			return;
		}

		$id = self::getId($extensionClass, $key);
		if (!array_key_exists($id, $this->values)) {
			$this->values[$id] = [$extensionClass, $key, $this->getRegisteredExtension($extensionClass)->getValue($key)];
		}

		$this->dependents[$this->analysedFile]['analysis'][$id] = true;
		if ($insideWalk) {
			return;
		}

		$scopeFile = $scope->getFile();
		if (!array_key_exists($scopeFile, $this->dependents)) {
			$this->dependents[$scopeFile] = ['analysis' => [], 'declarations' => []];
		}
		$this->dependents[$scopeFile]['declarations'][$id] = true;
	}

	/**
	 * @return ValueDependencies always with an entry for the analysed file
	 */
	public function finishFile(): array
	{
		$dependents = [];
		foreach ($this->dependents as $dependentFile => ['analysis' => $analysis, 'declarations' => $declarations]) {
			$dependents[$dependentFile] = [
				'analysis' => array_keys($analysis),
				'declarations' => array_keys($declarations),
			];
		}

		$values = $this->values;
		$this->analysedFile = null;
		$this->values = [];
		$this->dependents = [];

		return ['values' => $values, 'dependents' => $dependents];
	}

	/**
	 * The dependencies recorded during the analysis of one file can be about another file too,
	 * so the results of several files are merged, not overwritten. A value seen first stays.
	 *
	 * @param ValueDependencies $dependencies
	 * @param ValueDependencies $newDependencies
	 * @return ValueDependencies
	 */
	public static function merge(array $dependencies, array $newDependencies): array
	{
		$values = $dependencies['values'] + $newDependencies['values'];
		$dependents = $dependencies['dependents'];
		foreach ($newDependencies['dependents'] as $dependentFile => ['analysis' => $analysis, 'declarations' => $declarations]) {
			$dependents[$dependentFile] = [
				'analysis' => array_values(array_unique(array_merge($dependents[$dependentFile]['analysis'] ?? [], $analysis))),
				'declarations' => array_values(array_unique(array_merge($dependents[$dependentFile]['declarations'] ?? [], $declarations))),
			];
		}

		return ['values' => $values, 'dependents' => $dependents];
	}

	public function getExtension(string $extensionClass): ?ResultCacheValueExtension
	{
		foreach ($this->valueExtensions->getAll() as $extension) {
			if ($extension instanceof $extensionClass) {
				return $extension;
			}
		}

		return null;
	}

	private function getRegisteredExtension(string $extensionClass): ResultCacheValueExtension
	{
		$extension = $this->getExtension($extensionClass);
		if ($extension === null) {
			throw new ShouldNotHappenException(sprintf(
				'%s is not registered as a result cache value extension. Register it with the %s service tag.',
				$extensionClass,
				ResultCacheValueExtension::EXTENSION_TAG,
			));
		}

		return $extension;
	}

}
