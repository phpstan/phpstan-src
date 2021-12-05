<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Php\PhpVersion;
use function array_key_exists;

class OptimizedDirectorySourceLocator implements SourceLocator
{

	private \PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher $fileNodesFetcher;

	private PhpVersion $phpVersion;

	private PhpFileCleaner $cleaner;

	/** @var string[] */
	private array $files;

	private string $extraTypes;

	/** @var array<string, string>|null */
	private ?array $classToFile = null;

	/** @var array<string, array<int, string>>|null */
	private ?array $functionToFiles = null;

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\ClassLike>> */
	private array $classNodes = [];

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>> */
	private array $functionNodes = [];

	/**
	 * @param string[] $files
	 */
	public function __construct(
		FileNodesFetcher $fileNodesFetcher,
		PhpVersion $phpVersion,
		array $files
	)
	{
		$this->fileNodesFetcher = $fileNodesFetcher;
		$this->phpVersion = $phpVersion;
		$this->files = $files;

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
			if (array_key_exists($className, $this->classNodes)) {
				return $this->nodeToReflection($reflector, $this->classNodes[$className]);
			}

			$file = $this->findFileByClass($className);
			if ($file === null) {
				return null;
			}

			$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
			foreach ($fetchedNodesResult->getClassNodes() as $identifierName => $fetchedClassNodes) {
				foreach ($fetchedClassNodes as $fetchedClassNode) {
					$this->classNodes[$identifierName] = $fetchedClassNode;
					break;
				}
			}

			if (!array_key_exists($className, $this->classNodes)) {
				return null;
			}

			return $this->nodeToReflection($reflector, $this->classNodes[$className]);
		}

		if ($identifier->isFunction()) {
			$functionName = strtolower($identifier->getName());
			if (array_key_exists($functionName, $this->functionNodes)) {
				return $this->nodeToReflection($reflector, $this->functionNodes[$functionName]);
			}

			$files = $this->findFilesByFunction($functionName);
			foreach ($files as $file) {
				$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
				foreach ($fetchedNodesResult->getFunctionNodes() as $identifierName => $fetchedFunctionNode) {
					$this->functionNodes[$identifierName] = $fetchedFunctionNode;
				}
			}

			if (!array_key_exists($functionName, $this->functionNodes)) {
				return null;
			}

			return $this->nodeToReflection($reflector, $this->functionNodes[$functionName]);
		}

		return null;
	}

	/**
	 * @param FetchedNode<\PhpParser\Node\Stmt\ClassLike>|FetchedNode<\PhpParser\Node\Stmt\Function_> $fetchedNode
	 */
	private function nodeToReflection(Reflector $reflector, FetchedNode $fetchedNode): Reflection
	{
		$nodeToReflection = new NodeToReflection();
		return $nodeToReflection->__invoke(
			$reflector,
			$fetchedNode->getNode(),
			$fetchedNode->getLocatedSource(),
			$fetchedNode->getNamespace()
		);
	}

	private function findFileByClass(string $className): ?string
	{
		if ($this->classToFile === null) {
			$this->init();
			if ($this->classToFile === null) {
				throw new \PHPStan\ShouldNotHappenException();
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
				throw new \PHPStan\ShouldNotHappenException();
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
                 \b(?<![\$:>])(?P<type>class|interface|trait|function%s) \s++ (?P<byref>&\s*)? (?P<name>[a-zA-Z_\x7f-\xff:][a-zA-Z0-9_\x7f-\xff:\-]*+)
               | \b(?<![\$:>])(?P<ns>namespace) (?P<nsname>\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\s*+\\\\\s*+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+)? \s*+ [\{;]
            )
        }ix', $this->extraTypes), $contents, $matches);

		$classes = [];
		$functions = [];
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
				$namespacedName = strtolower(ltrim($namespace . $name, '\\'));

				if ($matches['type'][$i] === 'function') {
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

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
