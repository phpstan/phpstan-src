<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Override;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPStan\PhpDoc\RequireGenericsInBleedingEdgeOnly;
use PHPStan\ShouldNotHappenException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function array_merge;
use function array_unique;
use function array_values;
use function file_get_contents;
use function sort;
use function sprintf;
use function str_contains;

/**
 * Appends every class marked with #[RequireGenericsInBleedingEdgeOnly] in PHPStan's own stub files
 * to the featureToggles.skipCheckGenericClasses parameter, so that a class that has just become
 * generic does not start requiring type arguments before the next major version.
 *
 * The classes are not collected by composer-attribute-collector like the rest of PHPStan's
 * attributes are: the classes live in .stub files, which are neither part of the class map nor
 * loadable, and the collector reads the attributes of a class through runtime reflection - which
 * for an internal class such as ReflectionObject reports the attributes of the real PHP class,
 * never the ones written in a stub. The stub files are read here instead; parsing is limited to
 * the files that mention the attribute at all.
 */
#[ContainerExtension(name: 'skipCheckGenericClasses')]
final class SkipCheckGenericClassesExtension extends CompilerExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		$stubFiles = self::findStubFiles();

		// the compiled container holds the result of reading the stub files,
		// so it has to be rebuilt when any of them changes
		$this->compiler->addDependencies($stubFiles);

		$builder = $this->getContainerBuilder();
		if ((bool) $builder->parameters['featureToggles']['bleedingEdge']) {
			return;
		}

		$builder->parameters['featureToggles']['skipCheckGenericClasses'] = array_values(array_unique(array_merge(
			$builder->parameters['featureToggles']['skipCheckGenericClasses'],
			self::findClasses($stubFiles),
		)));
	}

	/**
	 * @param list<string> $stubFiles
	 * @return list<string>
	 */
	private static function findClasses(array $stubFiles): array
	{
		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$nodeFinder = new NodeFinder();
		$classes = [];

		foreach ($stubFiles as $stubFile) {
			$contents = file_get_contents($stubFile);
			if ($contents === false) {
				throw new ShouldNotHappenException(sprintf('Could not read stub file %s.', $stubFile));
			}

			if (!str_contains($contents, RequireGenericsInBleedingEdgeOnly::class)) {
				continue;
			}

			$traverser = new NodeTraverser(new NameResolver());
			$nodes = $traverser->traverse($parser->parse($contents) ?? []);

			foreach ($nodeFinder->findInstanceOf($nodes, ClassLike::class) as $classLike) {
				$className = $classLike->namespacedName;
				if ($className === null) {
					continue;
				}

				foreach ($classLike->attrGroups as $attrGroup) {
					foreach ($attrGroup->attrs as $attr) {
						if ($attr->name->toString() !== RequireGenericsInBleedingEdgeOnly::class) {
							continue;
						}

						$classes[] = $className->toString();
					}
				}
			}
		}

		sort($classes);

		return $classes;
	}

	/**
	 * @return list<string>
	 */
	private static function findStubFiles(): array
	{
		$files = [];
		$directoryIterator = new RecursiveDirectoryIterator(__DIR__ . '/../../stubs', RecursiveDirectoryIterator::SKIP_DOTS);
		foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
			if ($file->getExtension() !== 'stub') {
				continue;
			}

			$files[] = $file->getPathname();
		}

		sort($files);

		return $files;
	}

}
