<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
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

		$declarations = [];
		$reflection = $this->reflectionProvider->getClass($key)->getNativeReflection()->getBetterReflection();
		foreach ($this->getDeclaringClasses($reflection) as $lowercaseName => $declaringClass) {
			// a built-in class is read from a stub, which one depends on the source locator that
			// reflected it first; it changes only with PHP, its extensions or PHPStan itself
			$fileName = $declaringClass->isInternal() ? null : $declaringClass->getFileName();
			$declarations[$lowercaseName] = $fileName === null
				? 'built-in'
				: sprintf('%s %s', $fileName, $this->getExportedNode($fileName, $declaringClass->getName()));
		}

		ksort($declarations);

		return hash('sha256', implode("\n", $declarations));
	}

	/**
	 * The class with its parents, interfaces and traits. ClassReflection::getAncestors() would also
	 * resolve their generic types from the class PHPDocs, reaching StubPhpDocProvider - which runs
	 * the StubFilesExtensions, while the result cache is restored before the bootstrapFiles they
	 * can rely on.
	 *
	 * @return array<string, ReflectionClass> lowercase name => class
	 */
	private function getDeclaringClasses(ReflectionClass $reflection): array
	{
		$classes = [];
		$queue = [$reflection];
		while ($queue !== []) {
			$class = array_pop($queue);
			$lowercaseName = strtolower($class->getName());
			if (array_key_exists($lowercaseName, $classes)) {
				continue;
			}

			$classes[$lowercaseName] = $class;
			foreach ($class->getImmediateInterfaces() as $interface) {
				$queue[] = $interface;
			}
			foreach ($class->getTraits() as $trait) {
				$queue[] = $trait;
			}
			$parentClass = $class->getParentClass();
			if ($parentClass === null) {
				continue;
			}

			$queue[] = $parentClass;
		}

		return $classes;
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
