<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\File\FileFinder;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function array_key_exists;

class OptimizedDirectorySourceLocator implements SourceLocator
{

	private \PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher $fileNodesFetcher;

	private \PHPStan\File\FileFinder $fileFinder;

	private string $directory;

	/** @var array<string, string>|null */
	private ?array $classToFile = null;

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\ClassLike>> */
	private array $classNodes = [];

	/** @var array<string, \Roave\BetterReflection\SourceLocator\Located\LocatedSource> */
	private array $locatedSourcesByFile;

	public function __construct(
		FileNodesFetcher $fileNodesFetcher,
		FileFinder $fileFinder,
		string $directory
	)
	{
		$this->fileNodesFetcher = $fileNodesFetcher;
		$this->fileFinder = $fileFinder;
		$this->directory = $directory;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$className = $identifier->getName();
			if (array_key_exists($className, $this->classNodes)) {
				return $this->nodeToReflection($reflector, $this->classNodes[$className]);
			}

			$file = $this->findFileByClass($className);
			if ($file === null) {
				return null;
			}

			$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
			$locatedSource = $fetchedNodesResult->getLocatedSource();
			$this->locatedSourcesByFile[$file] = $locatedSource;
			foreach ($fetchedNodesResult->getClassNodes() as $identifierName => $fetchedClassNode) {
				$this->classNodes[$identifierName] = $fetchedClassNode;
			}

			if (!array_key_exists($className, $this->classNodes)) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			return $this->nodeToReflection($reflector, $this->classNodes[$className]);
		}

		return null;
	}

	/**
	 * @param Reflector $reflector
	 * @param FetchedNode<\PhpParser\Node\Stmt\ClassLike> $fetchedNode
	 * @return Reflection
	 */
	private function nodeToReflection(Reflector $reflector, FetchedNode $fetchedNode): Reflection
	{
		$nodeToReflection = new NodeToReflection();
		$classReflection = $nodeToReflection->__invoke(
			$reflector,
			$fetchedNode->getNode(),
			$this->locatedSourcesByFile[$fetchedNode->getFileName()],
			$fetchedNode->getNamespace()
		);

		if (!$classReflection instanceof ReflectionClass) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $classReflection;
	}

	private function findFileByClass(string $className): ?string
	{
		if ($this->classToFile === null) {
			$fileFinderResult = $this->fileFinder->findFiles([$this->directory]);
			$classToFile = [];
			foreach ($fileFinderResult->getFiles() as $file) {
				$classesInFile = $this->findClasses($file);
				foreach ($classesInFile as $classInFile) {
					$classToFile[$classInFile] = $file;
				}
			}

			$this->classToFile = $classToFile;
		}

		if (!array_key_exists($className, $this->classToFile)) {
			return null;
		}

		return $this->classToFile[$className];
	}

	/**
	 * Inspired by Composer\Autoload\ClassMapGenerator::findClasses()
	 * @link https://github.com/composer/composer/blob/45d3e133a4691eccb12e9cd6f9dfd76eddc1906d/src/Composer/Autoload/ClassMapGenerator.php#L216
	 *
	 * @param string $file
	 * @return string[]
	 */
	private function findClasses(string $file): array
	{
		$contents = @php_strip_whitespace($file);
		if ($contents === '') {
			return [];
		}

		if (!preg_match('{\b(?:class|interface|trait)\s}i', $contents)) {
			return [];
		}

		// strip heredocs/nowdocs
		$contents = preg_replace('{<<<[ \t]*([\'"]?)(\w+)\\1(?:\r\n|\n|\r)(?:.*?)(?:\r\n|\n|\r)(?:\s*)\\2(?=\s+|[;,.)])}s', 'null', $contents);
		// strip strings
		$contents = preg_replace('{"[^"\\\\]*+(\\\\.[^"\\\\]*+)*+"|\'[^\'\\\\]*+(\\\\.[^\'\\\\]*+)*+\'}s', 'null', $contents);
		// strip leading non-php code if needed
		if (substr($contents, 0, 2) !== '<?') {
			$contents = preg_replace('{^.+?<\?}s', '<?', $contents, 1, $replacements);
			if ($replacements === 0) {
				return [];
			}
		}
		// strip non-php blocks in the file
		$contents = preg_replace('{\?>(?:[^<]++|<(?!\?))*+<\?}s', '?><?', $contents);
		// strip trailing non-php code if needed
		$pos = strrpos($contents, '?>');
		if ($pos !== false && strpos(substr($contents, $pos), '<?') === false) {
			$contents = substr($contents, 0, $pos);
		}
		// strip comments if short open tags are in the file
		if (preg_match('{(<\?)(?!(php|hh))}i', $contents)) {
			$contents = preg_replace('{//.* | /\*(?:[^*]++|\*(?!/))*\*/}x', '', $contents);
		}

		preg_match_all('{
            (?:
                 \b(?<![\$:>])(?P<type>class|interface|trait) \s++ (?P<name>[a-zA-Z_\x7f-\xff:][a-zA-Z0-9_\x7f-\xff:\-]*+)
               | \b(?<![\$:>])(?P<ns>namespace) (?P<nsname>\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\s*+\\\\\s*+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+)? \s*+ [\{;]
            )
        }ix', $contents, $matches);

		$classes = [];
		$namespace = '';

		for ($i = 0, $len = count($matches['type']); $i < $len; $i++) {
			if (!empty($matches['ns'][$i])) { // phpcs:disable
				$namespace = str_replace([' ', "\t", "\r", "\n"], '', $matches['nsname'][$i]) . '\\';
			} else {
				$name = $matches['name'][$i];
				// skip anon classes extending/implementing
				if ($name === 'extends' || $name === 'implements') {
					continue;
				}
				if ($name[0] === ':') {
					// This is an XHP class, https://github.com/facebook/xhp
					$name = 'xhp' . substr(str_replace(['-', ':'], ['_', '__'], $name), 1);
				} elseif ($matches['type'][$i] === 'enum') {
					// In Hack, something like:
					//   enum Foo: int { HERP = '123'; }
					// The regex above captures the colon, which isn't part of
					// the class name.
					$name = rtrim($name, ':');
				}
				$classes[] = ltrim($namespace . $name, '\\');
			}
		}

		return $classes;
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return []; // todo
	}

}
