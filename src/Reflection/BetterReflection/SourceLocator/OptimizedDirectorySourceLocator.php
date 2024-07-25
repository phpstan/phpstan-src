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
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_values;
use function count;
use function current;
use function in_array;
use function ltrim;
use function php_strip_whitespace;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function strtolower;

/**
 * @deprecated Use NewOptimizedDirectorySourceLocator
 */
final class OptimizedDirectorySourceLocator implements SourceLocator
{

	private PhpFileCleaner $cleaner;

	private string $extraTypes;

	/** @var array<string, string>|null */
	private ?array $classToFile = null;

	/** @var array<string, string>|null */
	private ?array $constantToFile = null;

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
		$this->extraTypes = $this->phpVersion->supportsEnums() ? '|enum' : '';

		$this->cleaner = new PhpFileCleaner();
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$className = strtolower($identifier->getName());
			$file = $this->findFileByClass($className);
			if ($file === null) {
				return null;
			}

			$fetchedClassNodes = $this->fileNodesFetcher->fetchNodes($file)->getClassNodes();

			if (!array_key_exists($className, $fetchedClassNodes)) {
				return null;
			}

			/** @var FetchedNode<Node\Stmt\ClassLike> $fetchedClassNode */
			$fetchedClassNode = current($fetchedClassNodes[$className]);

			return $this->nodeToReflection($reflector, $fetchedClassNode);
		}

		if ($identifier->isFunction()) {
			$functionName = strtolower($identifier->getName());
			$files = $this->findFilesByFunction($functionName);

			$fetchedFunctionNode = null;
			foreach ($files as $file) {
				$fetchedFunctionNodes = $this->fileNodesFetcher->fetchNodes($file)->getFunctionNodes();

				if (!array_key_exists($functionName, $fetchedFunctionNodes)) {
					continue;
				}

				/** @var FetchedNode<Node\Stmt\Function_> $fetchedFunctionNode */
				$fetchedFunctionNode = current($fetchedFunctionNodes[$functionName]);
			}

			if ($fetchedFunctionNode === null) {
				return null;
			}

			return $this->nodeToReflection($reflector, $fetchedFunctionNode);
		}

		if ($identifier->isConstant()) {
			$constantName = ConstantNameHelper::normalize($identifier->getName());
			$file = $this->findFileByConstant($constantName);

			if ($file === null) {
				return null;
			}

			$fetchedConstantNodes = $this->fileNodesFetcher->fetchNodes($file)->getConstantNodes();

			if (!array_key_exists($constantName, $fetchedConstantNodes)) {
				return null;
			}

			/** @var FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall> $fetchedConstantNode */
			$fetchedConstantNode = current($fetchedConstantNodes[$constantName]);

			return $this->nodeToReflection(
				$reflector,
				$fetchedConstantNode,
				$this->findConstantPositionInConstNode($fetchedConstantNode->getNode(), $constantName),
			);
		}

		return null;
	}

	/**
	 * @param FetchedNode<Node\Stmt\ClassLike>|FetchedNode<Node\Stmt\Function_>|FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall> $fetchedNode
	 */
	private function nodeToReflection(Reflector $reflector, FetchedNode $fetchedNode, ?int $positionInNode = null): Reflection
	{
		$nodeToReflection = new NodeToReflection();
		return $nodeToReflection->__invoke(
			$reflector,
			$fetchedNode->getNode(),
			$fetchedNode->getLocatedSource(),
			$fetchedNode->getNamespace(),
			$positionInNode,
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

	private function findFileByConstant(string $constantName): ?string
	{
		if ($this->constantToFile === null) {
			$this->init();
			if ($this->constantToFile === null) {
				throw new ShouldNotHappenException();
			}
		}

		if (!array_key_exists($constantName, $this->constantToFile)) {
			return null;
		}

		return $this->constantToFile[$constantName];
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
		$constantToFile = [];
		$functionToFiles = [];
		foreach ($this->files as $file) {
			$symbols = $this->findSymbols($file);
			foreach ($symbols['classes'] as $classInFile) {
				$classToFile[$classInFile] = $file;
			}
			foreach ($symbols['constants'] as $constantInFile) {
				$constantToFile[$constantInFile] = $file;
			}
			foreach ($symbols['functions'] as $functionInFile) {
				if (!array_key_exists($functionInFile, $functionToFiles)) {
					$functionToFiles[$functionInFile] = [];
				}
				$functionToFiles[$functionInFile][] = $file;
			}
		}

		$this->classToFile = $classToFile;
		$this->functionToFiles = $functionToFiles;
		$this->constantToFile = $constantToFile;
	}

	/**
	 * Inspired by Composer\Autoload\ClassMapGenerator::findClasses()
	 * @link https://github.com/composer/composer/blob/45d3e133a4691eccb12e9cd6f9dfd76eddc1906d/src/Composer/Autoload/ClassMapGenerator.php#L216
	 *
	 * @return array{classes: string[], functions: string[], constants: string[]}
	 */
	private function findSymbols(string $file): array
	{
		$contents = @php_strip_whitespace($file);
		if ($contents === '') {
			return ['classes' => [], 'functions' => [], 'constants' => []];
		}

		$matchResults = (bool) preg_match_all(sprintf('{\b(?:(?:class|interface|trait|const|function%s)\s)|(?:define\s*\()}i', $this->extraTypes), $contents, $matches);
		if (!$matchResults) {
			return ['classes' => [], 'functions' => [], 'constants' => []];
		}

		$contents = $this->cleaner->clean($contents, count($matches[0]));

		preg_match_all(sprintf('{
			(?:
				\b(?<![\$:>])(?:
					(?: (?P<type>class|interface|trait%s) \s++ (?P<name>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) )
					| (?: (?P<function>function) \s++ (?:&\s*)? (?P<fname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) \s*+ [&\(] )
					| (?: (?P<constant>const) \s++ (?P<cname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) \s*+ [^;] )
					| (?: (?:\\\)? (?P<define>define) \s*+ \( \s*+ [\'"] (?P<dname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:[\\\\]{1,2}[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+) )
					| (?: (?P<ns>namespace) (?P<nsname>\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\s*+\\\\\s*+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+)? \s*+ [\{;] )
				)
			)
		}ix', $this->extraTypes), $contents, $matches);

		$classes = [];
		$functions = [];
		$constants = [];
		$namespace = '';

		for ($i = 0, $len = count($matches['type']); $i < $len; $i++) {
			if (isset($matches['ns'][$i]) && $matches['ns'][$i] !== '') {
				$namespace = preg_replace('~\s+~', '', strtolower($matches['nsname'][$i])) . '\\';
				continue;
			}

			if ($matches['function'][$i] !== '') {
				$functions[] = strtolower(ltrim($namespace . $matches['fname'][$i], '\\'));
				continue;
			}

			if ($matches['constant'][$i] !== '') {
				$constants[] = ConstantNameHelper::normalize(ltrim($namespace . $matches['cname'][$i], '\\'));
			}

			if ($matches['define'][$i] !== '') {
				$constants[] = ConstantNameHelper::normalize($matches['dname'][$i]);
				continue;
			}

			$name = $matches['name'][$i];

			// skip anon classes extending/implementing
			if (in_array($name, ['extends', 'implements'], true)) {
				continue;
			}

			$classes[] = strtolower(ltrim($namespace . $name, '\\'));
		}

		return [
			'classes' => $classes,
			'functions' => $functions,
			'constants' => $constants,
		];
	}

	/**
	 * @return list<Reflection>
	 */
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		if ($this->classToFile === null || $this->functionToFiles === null || $this->constantToFile === null) {
			$this->init();
			if ($this->classToFile === null || $this->functionToFiles === null || $this->constantToFile === null) {
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
		} elseif ($identifierType->isConstant()) {
			foreach ($this->constantToFile as $file) {
				$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
				foreach ($fetchedNodesResult->getConstantNodes() as $identifierName => $fetchedConstantNodes) {
					foreach ($fetchedConstantNodes as $fetchedConstantNode) {
						$reflections[$identifierName] = $this->nodeToReflection(
							$reflector,
							$fetchedConstantNode,
							$this->findConstantPositionInConstNode($fetchedConstantNode->getNode(), $identifierName),
						);
					}
				}
			}
		}

		return array_values($reflections);
	}

	private function findConstantPositionInConstNode(Node\Stmt\Const_|Node\Expr\FuncCall $constantNode, string $constantName): ?int
	{
		if ($constantNode instanceof Node\Expr\FuncCall) {
			return null;
		}

		/** @var int $position */
		foreach ($constantNode->consts as $position => $const) {
			if ($const->namespacedName === null) {
				throw new ShouldNotHappenException();
			}

			if (ConstantNameHelper::normalize($const->namespacedName->toString()) === $constantName) {
				return $position;
			}
		}

		throw new ShouldNotHappenException();
	}

}
