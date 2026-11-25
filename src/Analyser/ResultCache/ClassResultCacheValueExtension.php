<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Dependency\ExportedNodeFetcher;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileContentHasher;
use PHPStan\Reflection\ReflectionProvider;
use function array_key_exists;
use function array_pop;
use function hash;
use function implode;
use function json_encode;
use function ksort;
use function ltrim;
use function sprintf;
use function strtolower;

/**
 * The value behind DependencyTracker::trackClassDependency(): what the class and its parents,
 * interfaces and traits declare - their exported nodes, the same that decide whether the files
 * referencing a class in code are analysed again - or that the class does not exist. A changed
 * signature or PHPDoc counts, an edited method body does not; so does the class being created,
 * deleted or moved to another file.
 *
 * The key is the class name as written, without a leading backslash - not lowercased, because the
 * autoloader-based lookup of a class that is not loaded yet follows the case of the name (PSR-4).
 */
#[AutowiredService]
final class ClassResultCacheValueExtension implements ResultCacheValueExtension
{

	/** @var array<string, array{string, array<string, string>}> file => [content hash, lowercase name => exported node JSON] */
	private array $exportedNodesByFile = [];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ExportedNodeFetcher $exportedNodeFetcher,
		private FileContentHasher $fileContentHasher,
	)
	{
	}

	public static function createKey(string $className): string
	{
		return ltrim($className, '\\');
	}

	public function getValue(string $key): string
	{
		if (!$this->reflectionProvider->hasClass($key)) {
			return ValueDependencyCollector::MISSING;
		}

		// The class and its ancestors are walked by name, from the native reflection only:
		// ClassReflection::getAncestors() resolves @extends and @implements tags, and so asks the
		// stub PHPDocs - running the StubFilesExtensions, which ResultCacheManager::restore() must not,
		// because they can rely on bootstrapFiles not executed yet.
		$declarations = [];
		$queue = [$key];
		while (($className = array_pop($queue)) !== null) {
			if (!$this->reflectionProvider->hasClass($className)) {
				continue;
			}

			$declaringClass = $this->reflectionProvider->getClass($className);
			$name = $declaringClass->getName();
			if (array_key_exists(strtolower($name), $declarations)) {
				continue;
			}

			// a built-in class is read from a stub, which one depends on the source locator that
			// reflected it first; it changes only with PHP, its extensions or PHPStan itself
			$fileName = $declaringClass->isBuiltin() ? null : $declaringClass->getFileName();
			$declarations[strtolower($name)] = $fileName === null
				? 'built-in'
				: sprintf('%s %s', $fileName, $this->getExportedNode($fileName, $name));

			$nativeReflection = $declaringClass->getNativeReflection();
			$parentClass = $nativeReflection->getParentClass();
			if ($parentClass !== false) {
				$queue[] = $parentClass->getName();
			}
			foreach ($nativeReflection->getInterfaceNames() as $interfaceName) {
				$queue[] = $interfaceName;
			}
			foreach ($nativeReflection->getTraitNames() as $traitName) {
				$queue[] = $traitName;
			}
		}

		ksort($declarations);

		return hash('sha256', implode("\n", $declarations));
	}

	public function keyToResultCache(string $key): string
	{
		return $key;
	}

	public function keyFromResultCache(string $storedKey): string
	{
		return $storedKey;
	}

	private function getExportedNode(string $fileName, string $className): string
	{
		$signature = $this->fileContentHasher->hash($fileName);
		if ($signature === false) {
			return 'missing file';
		}
		if (!array_key_exists($fileName, $this->exportedNodesByFile) || $this->exportedNodesByFile[$fileName][0] !== $signature) {
			$nodes = [];
			foreach ($this->exportedNodeFetcher->fetchNodes($fileName) as $node) {
				$nodes[strtolower($node->getName())] = json_encode($node) ?: '';
			}
			$this->exportedNodesByFile[$fileName] = [$signature, $nodes];
		}

		return $this->exportedNodesByFile[$fileName][1][strtolower($className)] ?? 'not exported';
	}

}
