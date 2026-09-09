<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;
use function file_get_contents;
use function is_dir;
use function is_string;
use function str_ends_with;
use function strtolower;

/**
 * Which methods some class in the scanned code base overrides — the
 * closed-world half of InlineCallCollector: a non-final method nothing
 * overrides is as safe to inline as a final one, as long as the code base is
 * the whole world (PHPStan's own phar, where extensions subclassing
 * PHPStan's classes are the accepted exception: they still work, they just
 * see the parent's inlined bodies at PHPStan's own call sites).
 *
 * Conservative: a method declared by a class (or by a trait it uses) counts
 * as overriding it on every ancestor, whether or not that ancestor declares
 * it.
 */
final class OverridesScanner
{

	/** @var array<string, array{file: string, parent: string|null, traits: list<string>, properties: array<string, true>}> lc class => facts */
	private array $classes = [];

	/**
	 * The classes below $lcClass (transitively) that declare $property
	 * themselves — a private property redeclared in a subclass is a distinct
	 * property, and a caller reading the parent's, now public, one on a
	 * subclass instance would hit the subclass's private one instead.
	 *
	 * @return list<string> lc class names
	 */
	public function redeclaringDescendants(string $lcClass, string $property): array
	{
		$result = [];
		foreach ($this->classes as $name => $facts) {
			if ($name === $lcClass || !isset($facts['properties'][$property])) {
				continue;
			}
			$ancestor = $facts['parent'];
			$depth = 0;
			while ($ancestor !== null && $depth++ < 64) {
				if ($ancestor === $lcClass) {
					$result[] = $name;
					break;
				}
				$ancestor = $this->classes[$ancestor]['parent'] ?? null;
			}
		}

		return $result;
	}

	/**
	 * The file where $property of $lcClass is written: the class's own file,
	 * or the file of the trait (transitively) that declares it.
	 */
	public function propertyFile(string $lcClass, string $property): ?string
	{
		$facts = $this->classes[$lcClass] ?? null;
		if ($facts === null) {
			return null;
		}
		if (isset($facts['properties'][$property])) {
			return $facts['file'];
		}
		foreach ($facts['traits'] as $trait) {
			$file = $this->propertyFile($trait, $property);
			if ($file !== null) {
				return $file;
			}
		}

		return null;
	}

	/**
	 * @param list<string> $directories
	 * @return array<string, true> keys "lowercase\fqcn::lowercasemethod"
	 */
	public function scan(array $directories): array
	{
		$parser = (new ParserFactory())->createForHostVersion();
		$finder = new NodeFinder();
		$traverser = new NodeTraverser(new NameResolver());

		/** @var array<string, string|null> $parents lc class => lc parent */
		$parents = [];
		/** @var array<string, list<string>> $traits lc class => lc traits */
		$traits = [];
		/** @var array<string, list<string>> $methods lc class => lc methods (own) */
		$methods = [];

		foreach ($directories as $directory) {
			if (!is_dir($directory)) {
				continue;
			}
			/** @var SplFileInfo $file */
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
				if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
					continue;
				}
				$contents = file_get_contents($file->getPathname());
				if ($contents === false) {
					continue;
				}
				try {
					$stmts = $parser->parse($contents);
				} catch (Throwable) {
					continue;
				}
				if ($stmts === null) {
					continue;
				}
				$stmts = $traverser->traverse($stmts);
				foreach ($finder->findInstanceOf($stmts, Node\Stmt\ClassLike::class) as $classLike) {
					if (!isset($classLike->namespacedName) || $classLike instanceof Node\Stmt\Interface_) {
						continue;
					}
					$name = strtolower($classLike->namespacedName->toString());
					$parents[$name] = $classLike instanceof Node\Stmt\Class_ && $classLike->extends !== null
						? strtolower($classLike->extends->toString())
						: null;
					$traits[$name] = [];
					foreach ($finder->findInstanceOf($classLike->stmts, Node\Stmt\TraitUse::class) as $traitUse) {
						foreach ($traitUse->traits as $trait) {
							$traits[$name][] = strtolower($trait->toString());
						}
					}
					$methods[$name] = [];
					foreach ($classLike->getMethods() as $method) {
						$methods[$name][] = strtolower($method->name->toString());
					}
					$properties = [];
					foreach ($classLike->getProperties() as $property) {
						foreach ($property->props as $prop) {
							$properties[$prop->name->toString()] = true;
						}
					}
					$constructor = $classLike->getMethod('__construct');
					if ($constructor !== null) {
						foreach ($constructor->params as $param) {
							if (!$param->isPromoted() || !($param->var instanceof Node\Expr\Variable) || !is_string($param->var->name)) {
								continue;
							}

							$properties[$param->var->name] = true;
						}
					}
					$this->classes[$name] = [
						'file' => $file->getPathname(),
						'parent' => $parents[$name],
						'traits' => $traits[$name],
						'properties' => $properties,
					];
				}
			}
		}

		$overrides = [];
		foreach ($parents as $class => $parent) {
			$declared = $this->declaredMethods($class, $traits, $methods, []);
			$ancestor = $parent;
			$depth = 0;
			while ($ancestor !== null && $depth++ < 64) {
				foreach ($declared as $method) {
					$overrides[$ancestor . '::' . $method] = true;
				}
				$ancestor = $parents[$ancestor] ?? null;
			}
		}

		return $overrides;
	}

	/**
	 * @param array<string, list<string>> $traits
	 * @param array<string, list<string>> $methods
	 * @param array<string, true> $seen
	 * @return list<string>
	 */
	private function declaredMethods(string $class, array $traits, array $methods, array $seen): array
	{
		if (isset($seen[$class])) {
			return [];
		}
		$seen[$class] = true;
		$result = $methods[$class] ?? [];
		foreach ($traits[$class] ?? [] as $trait) {
			foreach ($this->declaredMethods($trait, $traits, $methods, $seen) as $method) {
				$result[] = $method;
			}
		}

		return $result;
	}

}
