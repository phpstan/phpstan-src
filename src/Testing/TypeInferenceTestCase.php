<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Generator\GeneratorNodeScopeResolver;
use PHPStan\Analyser\Generator\GeneratorScopeFactory;
use PHPStan\Analyser\Generator\NodeHandler\VarAnnotationHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterClosureThisExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterOutTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\SystemAgnosticSimpleRelativePathHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Symfony\Component\Finder\Finder;
use function array_map;
use function array_merge;
use function count;
use function fclose;
use function fgets;
use function fopen;
use function getenv;
use function in_array;
use function is_dir;
use function is_string;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function stripos;
use function strtolower;
use function version_compare;
use const PHP_VERSION;

/** @api */
abstract class TypeInferenceTestCase extends PHPStanTestCase
{

	protected static function createNodeScopeResolver(): NodeScopeResolver|GeneratorNodeScopeResolver
	{
		$container = self::getContainer();
		$enableGnsr = getenv('PHPSTAN_GNSR');
		if ($enableGnsr === '1') {
			return new GeneratorNodeScopeResolver(
				$container->getByType(ExprPrinter::class),
				$container->getByType(VarAnnotationHelper::class),
				$container,
			);
		}

		$reflectionProvider = self::createReflectionProvider();
		$typeSpecifier = $container->getService('typeSpecifier');

		return new NodeScopeResolver(
			$reflectionProvider,
			$container->getByType(InitializerExprTypeResolver::class),
			self::getReflector(),
			$container->getByType(ClassReflectionFactory::class),
			$container->getByType(ParameterOutTypeExtensionProvider::class),
			self::getParser(),
			$container->getByType(FileTypeMapper::class),
			$container->getByType(PhpVersion::class),
			$container->getByType(PhpDocInheritanceResolver::class),
			$container->getByType(FileHelper::class),
			$typeSpecifier,
			$container->getByType(DynamicThrowTypeExtensionProvider::class),
			$container->getByType(ReadWritePropertiesExtensionProvider::class),
			$container->getByType(ParameterClosureThisExtensionProvider::class),
			$container->getByType(ParameterClosureTypeExtensionProvider::class),
			self::createScopeFactory($reflectionProvider, $typeSpecifier),
			$container->getParameter('polluteScopeWithLoopInitialAssignments'),
			$container->getParameter('polluteScopeWithAlwaysIterableForeach'),
			$container->getParameter('polluteScopeWithBlock'),
			static::getEarlyTerminatingMethodCalls(),
			static::getEarlyTerminatingFunctionCalls(),
			$container->getParameter('exceptions')['implicitThrows'],
			$container->getParameter('treatPhpDocTypesAsCertain'),
			$container->getParameter('narrowMethodScopeFromConstructor'),
		);
	}

	/**
	 * @param string[] $dynamicConstantNames
	 */
	protected static function createScope(
		string $file,
		array $dynamicConstantNames = [],
	): MutatingScope
	{
		$scopeFactory = self::createScopeFactory(self::createReflectionProvider(), self::getContainer()->getService('typeSpecifier'), $dynamicConstantNames);
		return $scopeFactory->create(ScopeContext::create($file));
	}

	/**
	 * @param callable(Node , Scope ): void $callback
	 * @param string[] $dynamicConstantNames
	 */
	public static function processFile(
		string $file,
		callable $callback,
		array $dynamicConstantNames = [],
	): void
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$resolver = static::createNodeScopeResolver();
		$resolver->setAnalysedFiles(array_map(static fn (string $file): string => $fileHelper->normalizePath($file), array_merge([$file], static::getAdditionalAnalysedFiles())));

		$resolver->processNodes(
			self::getParser()->parseFile($file),
			$resolver instanceof NodeScopeResolver ? self::createScope($file, $dynamicConstantNames) : self::getContainer()->getByType(GeneratorScopeFactory::class)->create(ScopeContext::create($file)),
			$callback,
		);
	}

	/**
	 * @api
	 * @param mixed ...$args
	 */
	public function assertFileAsserts(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		if ($assertType === 'type') {
			if ($args[0] instanceof Type) {
				// backward compatibility
				$expectedType = $args[0];
				$this->assertInstanceOf(ConstantScalarType::class, $expectedType);
				$expected = $expectedType->getValue();
				$actualType = $args[1];
				$actual = $actualType->describe(VerbosityLevel::precise());
			} else {
				$expected = $args[0];
				$actual = $args[1];
			}

			$failureMessage = sprintf('Expected type %s, got type %s in %s on line %d.', $expected, $actual, $file, $args[2]);

			$delayedErrors = $args[3] ?? [];
			if (count($delayedErrors) > 0) {
				$failureMessage .= sprintf(
					"\n\nThis failure might be reported because of the following misconfiguration %s:\n\n",
					count($delayedErrors) === 1 ? 'issue' : 'issues',
				);
				foreach ($delayedErrors as $delayedError) {
					$failureMessage .= sprintf("* %s\n", $delayedError);
				}
			}

			$this->assertSame(
				$expected,
				$actual,
				$failureMessage,
			);
		} elseif ($assertType === 'superType') {
			$expected = $args[0];
			$actual = $args[1];
			$isCorrect = $args[2];

			$failureMessage = sprintf('Expected subtype of %s, got type %s in %s on line %d.', $expected, $actual, $file, $args[3]);

			$delayedErrors = $args[4] ?? [];
			if (count($delayedErrors) > 0) {
				$failureMessage .= sprintf(
					"\n\nThis failure might be reported because of the following misconfiguration %s:\n\n",
					count($delayedErrors) === 1 ? 'issue' : 'issues',
				);
				foreach ($delayedErrors as $delayedError) {
					$failureMessage .= sprintf("* %s\n", $delayedError);
				}
			}

			$this->assertTrue(
				$isCorrect,
				$failureMessage,
			);
		} elseif ($assertType === 'variableCertainty') {
			$expectedCertainty = $args[0];
			$actualCertainty = $args[1];
			$variableName = $args[2];

			$failureMessage = sprintf('Expected %s, actual certainty of %s is %s in %s on line %d.', $expectedCertainty->describe(), $variableName, $actualCertainty->describe(), $file, $args[3]);
			$delayedErrors = $args[4] ?? [];
			if (count($delayedErrors) > 0) {
				$failureMessage .= sprintf(
					"\n\nThis failure might be reported because of the following misconfiguration %s:\n\n",
					count($delayedErrors) === 1 ? 'issue' : 'issues',
				);
				foreach ($delayedErrors as $delayedError) {
					$failureMessage .= sprintf("* %s\n", $delayedError);
				}
			}

			$this->assertTrue(
				$expectedCertainty->equals($actualCertainty),
				$failureMessage,
			);
		}
	}

	/**
	 * @return array<string, (
	 *      array{0: 'type', 1: string, 2: int|float|string|bool|null, 3: string, 4: int, 5?: non-empty-list<non-falsy-string>}|
	 *      array{0: 'superType', 1: string, 2: string, 3: string, 4: bool, 5: int, 6?: non-empty-list<non-falsy-string>}|
	 *      array{0: 'variableCertainty', 1: string, 2: TrinaryLogic, 3: TrinaryLogic, 4: string, 5: int, 6?: non-empty-list<non-falsy-string>}
	 *  )>
	 *
	 * @api
	 */
	public static function gatherAssertTypes(string $file): array
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);

		$relativePathHelper = new SystemAgnosticSimpleRelativePathHelper($fileHelper);
		$reflectionProvider = self::getContainer()->getByType(ReflectionProvider::class);
		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);

		$file = $fileHelper->normalizePath($file);

		$asserts = [];
		$delayedErrors = [];
		self::processFile($file, static function (Node $node, Scope $scope) use (&$asserts, &$delayedErrors, $file, $relativePathHelper, $reflectionProvider, $typeStringResolver): void {
			if ($node instanceof InClassNode) {
				if (!$reflectionProvider->hasClass($node->getClassReflection()->getName())) {
					$delayedErrors[] = sprintf(
						'%s %s in %s not found in ReflectionProvider. Configure "autoload-dev" section in composer.json to include your tests directory.',
						$node->getClassReflection()->getClassTypeDescription(),
						$node->getClassReflection()->getName(),
						$file,
					);
				}
			} elseif ($node instanceof Node\Stmt\Trait_) {
				if ($node->namespacedName === null) {
					throw new ShouldNotHappenException();
				}
				if (!$reflectionProvider->hasClass($node->namespacedName->toString())) {
					$delayedErrors[] = sprintf('Trait %s not found in ReflectionProvider. Configure "autoload-dev" section in composer.json to include your tests directory.', $node->namespacedName->toString());
				}
			}
			if (!$node instanceof Node\Expr\FuncCall) {
				return;
			}

			$nameNode = $node->name;
			if (!$nameNode instanceof Name) {
				return;
			}

			$functionName = $nameNode->toString();
			if (in_array(strtolower($functionName), ['asserttype', 'assertnativetype', 'assertsupertype', 'assertvariablecertainty'], true)) {
				self::fail(sprintf(
					'Missing use statement for %s() in %s on line %d.',
					$functionName,
					$relativePathHelper->getRelativePath($file),
					$node->getStartLine(),
				));
			} elseif ($functionName === 'PHPStan\\Testing\\assertType') {
				$expectedType = $scope->getType($node->getArgs()[0]->value);
				if (!$expectedType instanceof ConstantScalarType) {
					self::fail(sprintf(
						'Expected type must be a literal string, %s given in %s on line %d.',
						$expectedType->describe(VerbosityLevel::precise()),
						$relativePathHelper->getRelativePath($file),
						$node->getStartLine(),
					));
				}
				$actualType = $scope->getType($node->getArgs()[1]->value);
				$assert = ['type', $file, $expectedType->getValue(), $actualType->describe(VerbosityLevel::precise()), $node->getStartLine()];
			} elseif ($functionName === 'PHPStan\\Testing\\assertNativeType') {
				$expectedType = $scope->getType($node->getArgs()[0]->value);
				if (!$expectedType instanceof ConstantScalarType) {
					self::fail(sprintf(
						'Expected type must be a literal string, %s given in %s on line %d.',
						$expectedType->describe(VerbosityLevel::precise()),
						$relativePathHelper->getRelativePath($file),
						$node->getStartLine(),
					));
				}

				$actualType = $scope->getNativeType($node->getArgs()[1]->value);
				$assert = ['type', $file, $expectedType->getValue(), $actualType->describe(VerbosityLevel::precise()), $node->getStartLine()];
			} elseif ($functionName === 'PHPStan\\Testing\\assertSuperType') {
				$expectedType = $scope->getType($node->getArgs()[0]->value);
				$expectedTypeStrings = $expectedType->getConstantStrings();
				if (count($expectedTypeStrings) !== 1) {
					self::fail(sprintf(
						'Expected super type must be a literal string, %s given in %s on line %d.',
						$expectedType->describe(VerbosityLevel::precise()),
						$relativePathHelper->getRelativePath($file),
						$node->getStartLine(),
					));
				}

				$actualType = $scope->getType($node->getArgs()[1]->value);
				$isCorrect = $typeStringResolver->resolve($expectedTypeStrings[0]->getValue())->isSuperTypeOf($actualType)->yes();

				$assert = ['superType', $file, $expectedTypeStrings[0]->getValue(), $actualType->describe(VerbosityLevel::precise()), $isCorrect, $node->getStartLine()];
			} elseif ($functionName === 'PHPStan\\Testing\\assertVariableCertainty') {
				$certainty = $node->getArgs()[0]->value;
				if (!$certainty instanceof StaticCall) {
					self::fail(sprintf('First argument of %s() must be TrinaryLogic call', $functionName));
				}
				if (!$certainty->class instanceof Node\Name) {
					self::fail(sprintf('ERROR: Invalid TrinaryLogic call.'));
				}

				if ($certainty->class->toString() !== 'PHPStan\\TrinaryLogic') {
					self::fail(sprintf('ERROR: Invalid TrinaryLogic call.'));
				}

				if (!$certainty->name instanceof Node\Identifier) {
					self::fail(sprintf('ERROR: Invalid TrinaryLogic call.'));
				}

				// @phpstan-ignore staticMethod.dynamicName
				$expectedertaintyValue = TrinaryLogic::{$certainty->name->toString()}();
				$variable = $node->getArgs()[1]->value;
				if ($variable instanceof Node\Expr\Variable && is_string($variable->name)) {
					$actualCertaintyValue = $scope->hasVariableType($variable->name);
					$variableDescription = sprintf('variable $%s', $variable->name);
				} elseif ($variable instanceof Node\Expr\ArrayDimFetch && $variable->dim !== null) {
					$offset = $scope->getType($variable->dim);
					$actualCertaintyValue = $scope->getType($variable->var)->hasOffsetValueType($offset);
					$variableDescription = sprintf('offset %s', $offset->describe(VerbosityLevel::precise()));
				} else {
					self::fail(sprintf('ERROR: Invalid assertVariableCertainty call.'));
				}

				$assert = ['variableCertainty', $file, $expectedertaintyValue, $actualCertaintyValue, $variableDescription, $node->getStartLine()];
			} else {
				$correctFunction = null;

				$assertFunctions = [
					'assertType' => 'PHPStan\\Testing\\assertType',
					'assertNativeType' => 'PHPStan\\Testing\\assertNativeType',
					'assertSuperType' => 'PHPStan\\Testing\\assertSuperType',
					'assertVariableCertainty' => 'PHPStan\\Testing\\assertVariableCertainty',
				];
				foreach ($assertFunctions as $assertFn => $fqFunctionName) {
					if (stripos($functionName, $assertFn) === false) {
						continue;
					}

					$correctFunction = $fqFunctionName;
				}

				if ($correctFunction === null) {
					return;
				}

				self::fail(sprintf(
					'Function %s imported with wrong namespace %s called in %s on line %d.',
					$correctFunction,
					$functionName,
					$relativePathHelper->getRelativePath($file),
					$node->getStartLine(),
				));
			}

			if (count($node->getArgs()) !== 2) {
				self::fail(sprintf(
					'ERROR: Wrong %s() call in %s on line %d.',
					$functionName,
					$relativePathHelper->getRelativePath($file),
					$node->getStartLine(),
				));
			}

			$asserts[$file . ':' . $node->getStartLine()] = $assert;
		});

		if (count($asserts) === 0) {
			self::fail(sprintf('File %s does not contain any asserts', $file));
		}

		if (count($delayedErrors) === 0) {
			return $asserts;
		}

		foreach ($asserts as $i => $assert) {
			$assert[] = $delayedErrors;
			$asserts[$i] = $assert;
		}

		return $asserts;
	}

	/**
	 * @api
	 * @return array<string, mixed[]>
	 */
	public static function gatherAssertTypesFromDirectory(string $directory): array
	{
		$asserts = [];
		foreach (self::findTestDataFilesFromDirectory($directory) as $path) {
			foreach (self::gatherAssertTypes($path) as $key => $assert) {
				$asserts[$key] = $assert;
			}
		}

		return $asserts;
	}

	/**
	 * @return list<string>
	 */
	public static function findTestDataFilesFromDirectory(string $directory): array
	{
		if (!is_dir($directory)) {
			self::fail(sprintf('Directory %s does not exist.', $directory));
		}

		$finder = new Finder();
		$finder->followLinks();
		$files = [];
		foreach ($finder->files()->name('*.php')->in($directory) as $fileInfo) {
			$path = $fileInfo->getPathname();
			if (self::isFileLintSkipped($path)) {
				continue;
			}
			$files[] = $path;
		}

		return $files;
	}

	/**
	 * From https://github.com/php-parallel-lint/PHP-Parallel-Lint/blob/0c2706086ac36dce31967cb36062ff8915fe03f7/bin/skip-linting.php
	 *
	 * Copyright (c) 2012, Jakub Onderka
	 */
	private static function isFileLintSkipped(string $file): bool
	{
		$f = @fopen($file, 'r');
		if ($f !== false) {
			$firstLine = fgets($f);
			if ($firstLine === false) {
				return false;
			}

			// ignore shebang line
			if (str_starts_with($firstLine, '#!')) {
				$firstLine = fgets($f);
				if ($firstLine === false) {
					return false;
				}
			}

			@fclose($f);

			if (preg_match('~<?php\\s*\\/\\/\s*lint\s*([^\d\s]+)\s*([^\s]+)\s*~i', $firstLine, $m) === 1) {
				return version_compare(PHP_VERSION, $m[2], $m[1]) === false;
			}
		}

		return false;
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/narrowMethodScopeFromConstructor.neon',
			],
		);
	}

	/** @return string[] */
	protected static function getAdditionalAnalysedFiles(): array
	{
		return [];
	}

	/** @return string[][] */
	protected static function getEarlyTerminatingMethodCalls(): array
	{
		return [];
	}

	/** @return string[] */
	protected static function getEarlyTerminatingFunctionCalls(): array
	{
		return [];
	}

}
