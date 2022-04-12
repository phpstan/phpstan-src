<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node\Arg;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use ReflectionFunction;
use function array_key_exists;
use function array_keys;
use function class_exists;
use function constant;
use function count;
use function defined;
use function function_exists;
use function interface_exists;
use function is_file;
use function is_string;
use function restore_error_handler;
use function set_error_handler;
use function spl_autoload_functions;
use function strtolower;
use function trait_exists;
use const PHP_VERSION_ID;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 *
 * Modified code from Roave/BetterReflection, Copyright (c) 2017 Roave, LLC.
 */
class AutoloadSourceLocator implements SourceLocator
{

	/** @var array{classes: array<string, string>, functions: array<string, string>, constants: array<string, string>} */
	private array $presentSymbols = [
		'classes' => [],
		'functions' => [],
		'constants' => [],
	];

	/** @var array<string, true> */
	private array $scannedFiles = [];

	/** @var array<string, int> */
	private array $startLineByClass = [];

	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		private FileHelper $fileHelper,
		private FileExcluder $fileExcluder,
		private bool $disableRuntimeReflectionProvider,
	)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isFunction()) {
			$functionName = $identifier->getName();
			$loweredFunctionName = strtolower($functionName);
			if (array_key_exists($loweredFunctionName, $this->presentSymbols['functions'])) {
				return $this->findReflection($reflector, $this->presentSymbols['functions'][$loweredFunctionName], $identifier, null);
			}
			if (!function_exists($functionName)) {
				return null;
			}

			$reflection = new ReflectionFunction($functionName);
			$reflectionFileName = $reflection->getFileName();

			if (!is_string($reflectionFileName)) {
				return null;
			}
			if (!is_file($reflectionFileName)) {
				return null;
			}

			return $this->findReflection($reflector, $reflectionFileName, $identifier, null);
		}

		if ($identifier->isConstant()) {
			$constantName = $identifier->getName();
			if (array_key_exists($constantName, $this->presentSymbols['constants'])) {
				return $this->findReflection($reflector, $this->presentSymbols['constants'][$constantName], $identifier, null);
			}

			if (!defined($constantName)) {
				return null;
			}

			$reflection = ReflectionConstant::createFromNode(
				$reflector,
				new FuncCall(new Name('define'), [
					new Arg(new String_($constantName)),
					new Arg(new String_('')), // not actually used
				]),
				new LocatedSource('', $constantName, null),
				null,
				null,
			);
			$reflection->populateValue(@constant($constantName));

			return $reflection;
		}

		if (!$identifier->isClass()) {
			return null;
		}

		$loweredClassName = strtolower($identifier->getName());
		if (array_key_exists($loweredClassName, $this->presentSymbols['classes'])) {
			$startLine = null;
			if (array_key_exists($loweredClassName, $this->startLineByClass)) {
				$startLine = $this->startLineByClass[$loweredClassName];
			} else {
				$reflection = $this->getReflectionClass($identifier->getName());
				if ($reflection !== null && $reflection->getStartLine() !== false) {
					$startLine = $reflection->getStartLine();
				}
			}
			return $this->findReflection($reflector, $this->presentSymbols['classes'][$loweredClassName], $identifier, $startLine);
		}
		$locateResult = $this->locateClassByName($identifier->getName());
		if ($locateResult === null) {
			return null;
		}
		[$potentiallyLocatedFile, $className, $startLine] = $locateResult;
		if ($startLine !== null) {
			$this->startLineByClass[strtolower($className)] = $startLine;
		}

		return $this->findReflection($reflector, $potentiallyLocatedFile, new Identifier($className, $identifier->getType()), $startLine);
	}

	private function findReflection(Reflector $reflector, string $file, Identifier $identifier, ?int $startLine): ?Reflection
	{
		$result = $this->fileNodesFetcher->fetchNodes($file);
		if (!array_key_exists($file, $this->scannedFiles)) {
			foreach (array_keys($result->getClassNodes()) as $className) {
				if (array_key_exists($className, $this->presentSymbols['classes'])) {
					continue;
				}
				$this->presentSymbols['classes'][$className] = $file;
			}
			foreach (array_keys($result->getFunctionNodes()) as $functionName) {
				if (array_key_exists($functionName, $this->presentSymbols['functions'])) {
					continue;
				}
				$this->presentSymbols['functions'][$functionName] = $file;
			}
			foreach ($result->getConstantNodes() as $stmtConst) {
				if ($stmtConst->getNode() instanceof FuncCall) {
					/** @var String_ $nameNode */
					$nameNode = $stmtConst->getNode()->getArgs()[0]->value;
					if (array_key_exists($nameNode->value, $this->presentSymbols['constants'])) {
						continue;
					}
					$this->presentSymbols['constants'][$nameNode->value] = $file;
					continue;
				}

				/** @var Const_ $const */
				foreach ($stmtConst->getNode()->consts as $const) {
					$constName = $const->namespacedName;
					if ($constName === null) {
						continue;
					}
					if (array_key_exists($constName->toString(), $this->presentSymbols['constants'])) {
						continue;
					}
					$this->presentSymbols['constants'][$constName->toString()] = $file;
				}
			}
			$this->scannedFiles[$file] = true;
		}

		$nodeToReflection = new NodeToReflection();
		if ($identifier->isClass()) {
			$identifierName = strtolower($identifier->getName());
			if (!array_key_exists($identifierName, $result->getClassNodes())) {
				return null;
			}

			$classNodesCount = count($result->getClassNodes()[$identifierName]);
			foreach ($result->getClassNodes()[$identifierName] as $classNode) {
				if ($classNodesCount > 1 && $startLine !== null) {
					if (count($classNode->getNode()->attrGroups) > 0 && PHP_VERSION_ID < 80000) {
						$startLine--;
					}
					if ($startLine !== $classNode->getNode()->getStartLine()) {
						continue;
					}
				}

				return $nodeToReflection->__invoke(
					$reflector,
					$classNode->getNode(),
					$classNode->getLocatedSource(),
					$classNode->getNamespace(),
				);
			}

			return null;
		}
		if ($identifier->isFunction()) {
			$identifierName = strtolower($identifier->getName());
			if (!array_key_exists($identifierName, $result->getFunctionNodes())) {
				return null;
			}

			return $nodeToReflection->__invoke(
				$reflector,
				$result->getFunctionNodes()[$identifierName]->getNode(),
				$result->getFunctionNodes()[$identifierName]->getLocatedSource(),
				$result->getFunctionNodes()[$identifierName]->getNamespace(),
			);
		}

		if ($identifier->isConstant()) {
			$nodeToReflection = new NodeToReflection();
			foreach ($result->getConstantNodes() as $stmtConst) {
				if ($stmtConst->getNode() instanceof FuncCall) {
					$constantReflection = $nodeToReflection->__invoke(
						$reflector,
						$stmtConst->getNode(),
						$stmtConst->getLocatedSource(),
						$stmtConst->getNamespace(),
					);
					if (!$constantReflection instanceof ReflectionConstant) {
						throw new ShouldNotHappenException();
					}
					if ($constantReflection->getName() !== $identifier->getName()) {
						continue;
					}

					return $constantReflection;
				}

				foreach (array_keys($stmtConst->getNode()->consts) as $i) {
					$constantReflection = $nodeToReflection->__invoke(
						$reflector,
						$stmtConst->getNode(),
						$stmtConst->getLocatedSource(),
						$stmtConst->getNamespace(),
						$i,
					);
					if (!$constantReflection instanceof ReflectionConstant) {
						throw new ShouldNotHappenException();
					}
					if ($constantReflection->getName() !== $identifier->getName()) {
						continue;
					}

					return $constantReflection;
				}
			}
		}

		return null;
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

	/**
	 * @return ReflectionClass<object>|null
	 */
	private function getReflectionClass(string $className): ?ReflectionClass
	{
		if (class_exists($className, !$this->disableRuntimeReflectionProvider) || interface_exists($className, !$this->disableRuntimeReflectionProvider) || trait_exists($className, !$this->disableRuntimeReflectionProvider)) {
			$reflection = new ReflectionClass($className);
			$filename = $reflection->getFileName();

			if (!is_string($filename)) {
				return null;
			}

			if (!is_file($filename)) {
				return null;
			}

			return $reflection;
		}

		return null;
	}

	/**
	 * Attempt to locate a class by name.
	 *
	 * If class already exists, simply use internal reflection API to get the
	 * filename and store it.
	 *
	 * If class does not exist, we make an assumption that whatever autoloaders
	 * that are registered will be loading a file. We then override the file://
	 * protocol stream wrapper to "capture" the filename we expect the class to
	 * be in, and then restore it. Note that class_exists will cause an error
	 * that it cannot find the file, so we squelch the errors by overriding the
	 * error handler temporarily.
	 *
	 * @return array{string, string, int|null}|null
	 */
	private function locateClassByName(string $className): ?array
	{
		$reflection = $this->getReflectionClass($className);
		if ($reflection !== null) {
			$filename = $reflection->getFileName();
			if (!is_string($filename)) {
				return null;
			}

			return [$filename, $reflection->getName(), $reflection->getStartLine() !== false ? $reflection->getStartLine() : null];
		}

		if (!$this->disableRuntimeReflectionProvider) {
			return null;
		}

		$this->silenceErrors();

		try {
			/** @var array{string, string, null}|null */
			$result = FileReadTrapStreamWrapper::withStreamWrapperOverride(
				static function () use ($className): ?array {
					$functions = spl_autoload_functions();
					if ($functions === false) {
						return null;
					}

					foreach ($functions as $preExistingAutoloader) {
						$preExistingAutoloader($className);

						/**
						 * This static variable is populated by the side-effect of the stream wrapper
						 * trying to read the file path when `include()` is used by an autoloader.
						 *
						 * This will not be `null` when the autoloader tried to read a file.
						 */
						if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
							return [FileReadTrapStreamWrapper::$autoloadLocatedFile, $className, null];
						}
					}

					return null;
				},
			);
			if ($result === null) {
				return null;
			}
			if ($this->fileExcluder->isExcludedFromAnalysing($this->fileHelper->normalizePath($result[0]))) {
				return null;
			}

			return $result;
		} finally {
			restore_error_handler();
		}
	}

	private function silenceErrors(): void
	{
		set_error_handler(static fn (): bool => true);
	}

}
