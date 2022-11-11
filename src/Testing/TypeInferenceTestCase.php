<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function is_string;
use function sprintf;

/** @api */
abstract class TypeInferenceTestCase extends PHPStanTestCase
{

	/**
	 * @param callable(Node , Scope ): void $callback
	 * @param string[] $dynamicConstantNames
	 */
	public function processFile(
		string $file,
		callable $callback,
		array $dynamicConstantNames = [],
	): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$typeSpecifier = self::getContainer()->getService('typeSpecifier');
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$resolver = new NodeScopeResolver(
			$reflectionProvider,
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
			self::getReflector(),
			$this->getClassReflectionExtensionRegistryProvider(),
			$this->getParser(),
			self::getContainer()->getByType(FileTypeMapper::class),
			self::getContainer()->getByType(StubPhpDocProvider::class),
			self::getContainer()->getByType(PhpVersion::class),
			self::getContainer()->getByType(PhpDocInheritanceResolver::class),
			self::getContainer()->getByType(FileHelper::class),
			$typeSpecifier,
			self::getContainer()->getByType(DynamicThrowTypeExtensionProvider::class),
			self::getContainer()->getByType(ReadWritePropertiesExtensionProvider::class),
			true,
			true,
			$this->getEarlyTerminatingMethodCalls(),
			$this->getEarlyTerminatingFunctionCalls(),
			true,
		);
		$resolver->setAnalysedFiles(array_map(static fn (string $file): string => $fileHelper->normalizePath($file), array_merge([$file], $this->getAdditionalAnalysedFiles())));

		$scopeFactory = $this->createScopeFactory($reflectionProvider, $typeSpecifier, $dynamicConstantNames);
		$scope = $scopeFactory->create(ScopeContext::create($file));

		$resolver->processNodes(
			$this->getParser()->parseFile($file),
			$scope,
			$callback,
		);
	}

	/**
	 * @param callable(): array<string, array<mixed>> $assertionsCallback
	 * @api
	 */
	public function assertAssertions(callable $assertionsCallback): void
	{
		$assertions = $assertionsCallback();

		$errors = [];
		foreach ($assertions as $args) {
			$type = $args[0];
			$file = $args[1];
			if ($type === 'type') {
				$expectedType = $args[2];
				$this->assertInstanceOf(ConstantScalarType::class, $expectedType);
				$expected = $expectedType->getValue();
				$actual = $args[3]->describe(VerbosityLevel::precise());
				$line = $args[4];
				if ($expected !== $actual) {
					$errors[] = sprintf('Expected type %s, got type %s in %s on line %d.', $expected, $actual, $file, $line);
				}
			} elseif ($type === 'variableCertainty') {
				$expectedCertainty = $args[2];
				$actualCertainty = $args[3];
				$variableName = $args[4];
				$line = $args[5];
				if (!$expectedCertainty->equals($actualCertainty)) {
					$errors[] = sprintf('Expected %s, actual certainty of variable $%s is %s in %s on line %d.', $expectedCertainty->describe(), $variableName, $actualCertainty->describe(), $file, $line);
				}
			}
		}
		$this->assertEmpty($errors, implode("\n", $errors));
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
			$expectedType = $args[0];
			$this->assertInstanceOf(ConstantScalarType::class, $expectedType);
			$expected = $expectedType->getValue();
			$actualType = $args[1];
			$actual = $actualType->describe(VerbosityLevel::precise());
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
	 * @return iterable<string, array{callable(): array<string, array<mixed>>}>
	 */
	public function gatherAssertions(string $file): iterable
	{
		yield $file => [fn () => $this->gatherAssertTypes($file)];
	}

	/**
	 * @api
	 * @return array<string, mixed[]>
	 */
	public function gatherAssertTypes(string $file): array
	{
		$asserts = [];
		$this->processFile($file, function (Node $node, Scope $scope) use (&$asserts, $file): void {
			if (!$node instanceof Node\Expr\FuncCall) {
				return;
			}

			$nameNode = $node->name;
			if (!$nameNode instanceof Name) {
				return;
			}

			$functionName = $nameNode->toString();
			if ($functionName === 'PHPStan\\Testing\\assertType') {
				$expectedType = $scope->getType($node->getArgs()[0]->value);
				$actualType = $scope->getType($node->getArgs()[1]->value);
				$assert = ['type', $file, $expectedType, $actualType, $node->getLine()];
			} elseif ($functionName === 'PHPStan\\Testing\\assertNativeType') {
				$nativeScope = $scope->doNotTreatPhpDocTypesAsCertain();
				$expectedType = $nativeScope->getNativeType($node->getArgs()[0]->value);
				$actualType = $nativeScope->getNativeType($node->getArgs()[1]->value);
				$assert = ['type', $file, $expectedType, $actualType, $node->getLine()];
			} elseif ($functionName === 'PHPStan\\Testing\\assertVariableCertainty') {
				$certainty = $node->getArgs()[0]->value;
				if (!$certainty instanceof StaticCall) {
					$this->fail(sprintf('First argument of %s() must be TrinaryLogic call', $functionName));
				}
				if (!$certainty->class instanceof Node\Name) {
					$this->fail(sprintf('ERROR: Invalid TrinaryLogic call.'));
				}

				if ($certainty->class->toString() !== 'PHPStan\\TrinaryLogic') {
					$this->fail(sprintf('ERROR: Invalid TrinaryLogic call.'));
				}

				if (!$certainty->name instanceof Node\Identifier) {
					$this->fail(sprintf('ERROR: Invalid TrinaryLogic call.'));
				}

				// @phpstan-ignore-next-line
				$expectedertaintyValue = TrinaryLogic::{$certainty->name->toString()}();
				$variable = $node->getArgs()[1]->value;
				if (!$variable instanceof Node\Expr\Variable) {
					$this->fail(sprintf('ERROR: Invalid assertVariableCertainty call.'));
				}
				if (!is_string($variable->name)) {
					$this->fail(sprintf('ERROR: Invalid assertVariableCertainty call.'));
				}

				$actualCertaintyValue = $scope->hasVariableType($variable->name);
				$assert = ['variableCertainty', $file, $expectedertaintyValue, $actualCertaintyValue, $variable->name, $node->getLine()];
			} else {
				return;
			}

			if (count($node->getArgs()) !== 2) {
				$this->fail(sprintf(
					'ERROR: Wrong %s() call on line %d.',
					$functionName,
					$node->getLine(),
				));
			}

			$asserts[$file . ':' . $node->getLine()] = $assert;
		});

		if (count($asserts) === 0) {
			$this->fail(sprintf('File %s does not contain any asserts', $file));
		}

		return $asserts;
	}

	/** @return string[] */
	protected function getAdditionalAnalysedFiles(): array
	{
		return [];
	}

	/** @return string[][] */
	protected function getEarlyTerminatingMethodCalls(): array
	{
		return [];
	}

	/** @return string[] */
	protected function getEarlyTerminatingFunctionCalls(): array
	{
		return [];
	}

}
