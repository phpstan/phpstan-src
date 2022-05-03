<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_merge;
use function array_values;
use function count;
use function ltrim;
use function php_strip_whitespace;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function strtolower;

class OptimizedDirectorySourceLocator implements SourceLocator
{

	private PhpFileCleaner $cleaner;

	private string $extraTypes;

	/** @var array<string, string>|null */
	private ?array $classToFile = null;

	/** @var array<string, array<int, string>>|null */
	private ?array $functionToFiles = null;

	/**
	 * @param string[] $files
	 */
	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		private PhpVersion $phpVersion,
		private array $files,
	)
	{
		$extraTypes = '';
		$extraTypesArray = [];
		if ($this->phpVersion->supportsEnums()) {
			$extraTypes = '|enum';
			$extraTypesArray[] = 'enum';
		}

		$this->extraTypes = $extraTypes;

		$this->cleaner = new PhpFileCleaner(array_merge(['class', 'interface', 'trait', 'function'], $extraTypesArray));
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$className = strtolower($identifier->getName());
			$file = $this->findFileByClass($className);
			if ($file === null) {
				return null;
			}

			$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
			$classNodes = [];
			foreach ($fetchedNodesResult->getClassNodes() as $identifierName => $fetchedClassNodes) {
				foreach ($fetchedClassNodes as $fetchedClassNode) {
					$classNodes[$identifierName] = $fetchedClassNode;
					break;
				}
			}

			if (!array_key_exists($className, $classNodes)) {
				return null;
			}

			return $this->nodeToReflection($reflector, $classNodes[$className]);
		}

		if ($identifier->isFunction()) {
			$functionName = strtolower($identifier->getName());
			$files = $this->findFilesByFunction($functionName);
			$functionNodes = [];
			foreach ($files as $file) {
				$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
				foreach ($fetchedNodesResult->getFunctionNodes() as $identifierName => $fetchedFunctionNodes) {
					foreach ($fetchedFunctionNodes as $fetchedFunctionNode) {
						$functionNodes[$identifierName] = $fetchedFunctionNode;
						continue 2;
					}
				}
			}

			if (!array_key_exists($functionName, $functionNodes)) {
				return null;
			}

			return $this->nodeToReflection($reflector, $functionNodes[$functionName]);
		}

		return null;
	}

	/**
	 * @param FetchedNode<Node\Stmt\ClassLike>|FetchedNode<Node\Stmt\Function_> $fetchedNode
	 */
	private function nodeToReflection(Reflector $reflector, FetchedNode $fetchedNode): Reflection
	{
		$nodeToReflection = new NodeToReflection();
		return $nodeToReflection->__invoke(
			$reflector,
			$fetchedNode->getNode(),
			$fetchedNode->getLocatedSource(),
			$fetchedNode->getNamespace(),
		);
	}

	private function findFileByClass(string $className): ?string
	{
		if ($this->classToFile === null) {
			$this->init();
			if ($this->classToFile === null) {
				throw new ShouldNotHappenException();
			}
		}

		if (!array_key_exists($className, $this->classToFile)) {
			return null;
		}

		return $this->classToFile[$className];
	}

	/**
	 * @return string[]
	 */
	private function findFilesByFunction(string $functionName): array
	{
		if ($this->functionToFiles === null) {
			$this->init();
			if ($this->functionToFiles === null) {
				throw new ShouldNotHappenException();
			}
		}

		if (!array_key_exists($functionName, $this->functionToFiles)) {
			return [];
		}

		return $this->functionToFiles[$functionName];
	}

	private function init(): void
	{
		$classToFile = [];
		$functionToFiles = [];
		foreach ($this->files as $file) {
			$symbols = $this->findSymbols($file);
			$classesInFile = $symbols['classes'];
			$functionsInFile = $symbols['functions'];
			foreach ($classesInFile as $classInFile) {
				$classToFile[$classInFile] = $file;
			}
			foreach ($functionsInFile as $functionInFile) {
				if (!array_key_exists($functionInFile, $functionToFiles)) {
					$functionToFiles[$functionInFile] = [];
				}
				$functionToFiles[$functionInFile][] = $file;
			}
		}

		$this->classToFile = $classToFile;
		$this->functionToFiles = $functionToFiles;
	}

	/**
	 * Inspired by Composer\Autoload\ClassMapGenerator::findClasses()
	 * @link https://github.com/composer/composer/blob/45d3e133a4691eccb12e9cd6f9dfd76eddc1906d/src/Composer/Autoload/ClassMapGenerator.php#L216
	 *
	 * @return array{classes: string[], functions: string[]}
	 */
	private function findSymbols(string $file): array
	{
		$contents = @php_strip_whitespace($file);
		if ($contents === '') {
			return ['classes' => [], 'functions' => []];
		}

		$matchResults = (bool) preg_match_all(sprintf('{\b(?:class|interface|trait|function%s)\s}i', $this->extraTypes), $contents, $matches);
		if (!$matchResults) {
			return ['classes' => [], 'functions' => []];
		}

		$contents = $this->cleaner->clean($contents, count($matches[0]));

		preg_match_all(sprintf('{
			(?:
				\b(?<![\$:>])(?:
					(?: (?P<type>class|interface|trait%s) \s++ (?P<name>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) )
					| (?: (?P<function>function) \s++ (?:&\s*)? (?P<fname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) )
					| (?: (?P<ns>namespace) (?P<nsname>\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\s*+\\\\\s*+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+)? \s*+ [\{;] )
				)
			)
		}ix', $this->extraTypes), $contents, $matches);

		$classes = [];
		$functions = [];
		$namespace = '';

		for ($i = 0, $len = count($matches['type']); $i < $len; $i++) {
			if (isset($matches['ns'][$i]) && $matches['ns'][$i] !== '') {
				$namespace = preg_replace('~\s+~', '', $matches['nsname'][$i]) . '\\';
			} else {
				$isFuction = $matches['function'][$i] !== '';
				$name = $matches[$isFuction ? 'fname' : 'name'][$i];

				// skip anon classes extending/implementing
				if ($name === 'extends' || $name === 'implements') {
					continue;
				}

				$namespacedName = strtolower(ltrim($namespace . $name, '\\'));

				if ($isFuction) {
					$functions[] = $namespacedName;
				} else {
					$classes[] = $namespacedName;
				}
			}
		}

		return [
			'classes' => $classes,
			'functions' => $functions,
		];
	}

	/**
	 * @return array<int, Reflection>
	 */
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		if ($this->classToFile === null || $this->functionToFiles === null) {
			$this->init();
			if ($this->classToFile === null || $this->functionToFiles === null) {
				throw new ShouldNotHappenException();
			}
		}

		$reflections = [];
		if ($identifierType->isClass()) {
			foreach ($this->classToFile as $file) {
				$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
				foreach ($fetchedNodesResult->getClassNodes() as $identifierName => $fetchedClassNodes) {
					foreach ($fetchedClassNodes as $fetchedClassNode) {
						$reflections[$identifierName] = $this->nodeToReflection($reflector, $fetchedClassNode);
						continue;
					}
				}
			}
		} elseif ($identifierType->isFunction()) {
			foreach ($this->functionToFiles as $files) {
				foreach ($files as $file) {
					$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
					foreach ($fetchedNodesResult->getFunctionNodes() as $identifierName => $fetchedFunctionNodes) {
						foreach ($fetchedFunctionNodes as $fetchedFunctionNode) {
							$reflections[$identifierName] = $this->nodeToReflection($reflector, $fetchedFunctionNode);
							continue 2;
						}
					}
				}
			}
		}

		return array_values($reflections);
	}

}
