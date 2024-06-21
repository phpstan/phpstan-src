<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterOutTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Symfony\Component\Finder\Finder;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function fclose;
use function fgets;
use function fopen;
use function in_array;
use function is_dir;
use function is_string;
use function preg_match;
use function sprintf;
use function stripos;
use function strpos;
use function strtolower;
use function version_compare;
use const PHP_VERSION;

/** @api */
abstract class TypeInferenceTestCase extends PHPStanTestCase
{

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
		$reflectionProvider = self::createReflectionProvider();
		$typeSpecifier = self::getContainer()->getService('typeSpecifier');
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$resolver = new NodeScopeResolver(
			$reflectionProvider,
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
			self::getReflector(),
			self::getClassReflectionExtensionRegistryProvider(),
			self::getContainer()->getByType(ParameterOutTypeExtensionProvider::class),
			self::getParser(),
			self::getContainer()->getByType(FileTypeMapper::class),
			self::getContainer()->getByType(StubPhpDocProvider::class),
			self::getContainer()->getByType(PhpVersion::class),
			self::getContainer()->getByType(SignatureMapProvider::class),
			self::getContainer()->getByType(PhpDocInheritanceResolver::class),
			self::getContainer()->getByType(FileHelper::class),
			$typeSpecifier,
			self::getContainer()->getByType(DynamicThrowTypeExtensionProvider::class),
			self::getContainer()->getByType(ReadWritePropertiesExtensionProvider::class),
			self::getContainer()->getByType(ParameterClosureTypeExtensionProvider::class),
			self::createScopeFactory($reflectionProvider, $typeSpecifier),
			self::getContainer()->getParameter('polluteScopeWithLoopInitialAssignments'),
			self::getContainer()->getParameter('polluteScopeWithAlwaysIterableForeach'),
			static::getEarlyTerminatingMethodCalls(),
			static::getEarlyTerminatingFunctionCalls(),
			self::getContainer()->getParameter('universalObjectCratesClasses'),
			self::getContainer()->getParameter('exceptions')['implicitThrows'],
			self::getContainer()->getParameter('treatPhpDocTypesAsCertain'),
			self::getContainer()->getParameter('featureToggles')['detectDeadTypeInMultiCatch'],
			self::getContainer()->getParameter('featureToggles')['paramOutType'],
		);
		$resolver->setAnalysedFiles(array_map(static fn (string $file): string => $fileHelper->normalizePath($file), array_merge([$file], static::getAdditionalAnalysedFiles())));

		$scopeFactory = self::createScopeFactory($reflectionProvider, $typeSpecifier, $dynamicConstantNames);
		$scope = $scopeFactory->create(ScopeContext::create($file));

		$resolver->processNodes(
			self::getParser()->parseFile($file),
			$scope,
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

			$this->assertSame(
				$expected,
				$actual,
				sprintf('Expected type %s, got type %s in %s on line %d.', $expected, $actual, $file, $args[2]),
			);
		} elseif ($assertType === 'variableCertainty') {
			$expectedCertainty = $args[0];
			$actualCertainty = $args[1];
			$variableName = $args[2];
			$this->assertTrue(
				$expectedCertainty->equals($actualCertainty),
				sprintf('Expected %s, actual certainty of variable $%s is %s in %s on line %d.', $expectedCertainty->describe(), $variableName, $actualCertainty->describe(), $file, $args[3]),
			);
		}
	}

	/**
	 * @api
	 * @return array<string, mixed[]>
	 */
	public static function gatherAssertTypes(string $file): array
	{
		$asserts = [];
		self::processFile($file, static function (Node $node, Scope $scope) use (&$asserts, $file): void {
			if (!$node instanceof Node\Expr\FuncCall) {
				return;
			}

			$nameNode = $node->name;
			if (!$nameNode instanceof Name) {
				return;
			}

			$functionName = $nameNode->toString();
			if (in_array(strtolower($functionName), ['asserttype', 'assertnativetype', 'assertvariablecertainty'], true)) {
				self::fail(sprintf(
					'Missing use statement for %s() on line %d.',
					$functionName,
					$node->getStartLine(),
				));
			} elseif ($functionName === 'PHPStan\\Testing\\assertType') {
				$expectedType = $scope->getType($node->getArgs()[0]->value);
				if (!$expectedType instanceof ConstantScalarType) {
					self::fail(sprintf('Expected type must be a literal string, %s given on line %d.', $expectedType->describe(VerbosityLevel::precise()), $node->getLine()));
				}
				$actualType = $scope->getType($node->getArgs()[1]->value);
				$assert = ['type', $file, $expectedType->getValue(), $actualType->describe(VerbosityLevel::precise()), $node->getStartLine()];
			} elseif ($functionName === 'PHPStan\\Testing\\assertNativeType') {
				$expectedType = $scope->getType($node->getArgs()[0]->value);
				if (!$expectedType instanceof ConstantScalarType) {
					self::fail(sprintf('Expected type must be a literal string, %s given on line %d.', $expectedType->describe(VerbosityLevel::precise()), $node->getLine()));
				}

				$actualType = $scope->getNativeType($node->getArgs()[1]->value);
				$assert = ['type', $file, $expectedType->getValue(), $actualType->describe(VerbosityLevel::precise()), $node->getStartLine()];
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
				if (!$variable instanceof Node\Expr\Variable) {
					self::fail(sprintf('ERROR: Invalid assertVariableCertainty call.'));
				}
				if (!is_string($variable->name)) {
					self::fail(sprintf('ERROR: Invalid assertVariableCertainty call.'));
				}

				$actualCertaintyValue = $scope->hasVariableType($variable->name);
				$assert = ['variableCertainty', $file, $expectedertaintyValue, $actualCertaintyValue, $variable->name, $node->getStartLine()];
			} else {
				$correctFunction = null;

				$assertFunctions = [
					'assertType' => 'PHPStan\\Testing\\assertType',
					'assertNativeType' => 'PHPStan\\Testing\\assertNativeType',
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
					'Function %s imported with wrong namespace %s called on line %d.',
					$correctFunction,
					$functionName,
					$node->getStartLine(),
				));
			}

			if (count($node->getArgs()) !== 2) {
				self::fail(sprintf(
					'ERROR: Wrong %s() call on line %d.',
					$functionName,
					$node->getStartLine(),
				));
			}

			$asserts[$file . ':' . $node->getStartLine()] = $assert;
		});

		if (count($asserts) === 0) {
			self::fail(sprintf('File %s does not contain any asserts', $file));
		}

		return $asserts;
	}

	/**
	 * @api
	 * @return array<string, mixed[]>
	 */
	public static function gatherAssertTypesFromDirectory(string $directory): array
	{
		if (!is_dir($directory)) {
			self::fail(sprintf('Directory %s does not exist.', $directory));
		}

		$finder = new Finder();
		$finder->followLinks();
		$asserts = [];
		foreach ($finder->files()->name('*.php')->in($directory) as $fileInfo) {
			$path = $fileInfo->getPathname();
			if (self::isFileLintSkipped($path)) {
				continue;
			}
			foreach (self::gatherAssertTypes($path) as $key => $assert) {
				$asserts[$key] = $assert;
			}
		}

		return $asserts;
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
			if (strpos($firstLine, '#!') === 0) {
				$firstLine = fgets($f);
				if ($firstLine === false) {
					return false;
				}
			}

			@fclose($f);

			if (preg_match('~<?php\\s*\\/\\/\s*lint\s*([^\d\s]+)\s*([^\s]+)\s*~i', $firstLine, $m) === 1) {
				if (!array_key_exists(1, $m) || !array_key_exists(2, $m)) {
					throw new ShouldNotHappenException();
				}
				return version_compare(PHP_VERSION, $m[2], $m[1]) === false;
			}
		}

		return false;
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
