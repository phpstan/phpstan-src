<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\DirectScopeFactory;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Broker\Broker;
use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\ReflectionProvider\DirectReflectionProviderProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;

/** @api */
abstract class TypeInferenceTestCase extends \PHPStan\Testing\TestCase
{

	/**
	 * @param string $file
	 * @param callable(\PhpParser\Node, \PHPStan\Analyser\Scope): void $callback
	 * @param DynamicMethodReturnTypeExtension[] $dynamicMethodReturnTypeExtensions
	 * @param DynamicStaticMethodReturnTypeExtension[] $dynamicStaticMethodReturnTypeExtensions
	 * @param \PHPStan\Type\MethodTypeSpecifyingExtension[] $methodTypeSpecifyingExtensions
	 * @param \PHPStan\Type\StaticMethodTypeSpecifyingExtension[] $staticMethodTypeSpecifyingExtensions
	 * @param string[] $dynamicConstantNames
	 */
	public function processFile(
		string $file,
		callable $callback,
		array $dynamicMethodReturnTypeExtensions = [],
		array $dynamicStaticMethodReturnTypeExtensions = [],
		array $methodTypeSpecifyingExtensions = [],
		array $staticMethodTypeSpecifyingExtensions = [],
		array $dynamicConstantNames = []
	): void
	{
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);
		$phpDocNodeResolver = self::getContainer()->getByType(PhpDocNodeResolver::class);

		$printer = new \PhpParser\PrettyPrinter\Standard();
		$broker = $this->createBroker($dynamicMethodReturnTypeExtensions, $dynamicStaticMethodReturnTypeExtensions);
		Broker::registerInstance($broker);
		$typeSpecifier = $this->createTypeSpecifier($printer, $broker, $methodTypeSpecifyingExtensions, $staticMethodTypeSpecifyingExtensions);
		$currentWorkingDirectory = $this->getCurrentWorkingDirectory();
		$fileHelper = new FileHelper($currentWorkingDirectory);
		$fileTypeMapper = new FileTypeMapper(new DirectReflectionProviderProvider($broker), $this->getParser(), $phpDocStringResolver, $phpDocNodeResolver, $this->createMock(Cache::class), new AnonymousClassNameHelper($fileHelper, new SimpleRelativePathHelper($currentWorkingDirectory)));
		$phpDocInheritanceResolver = new PhpDocInheritanceResolver($fileTypeMapper);
		$resolver = new NodeScopeResolver(
			$broker,
			self::getReflectors()[0],
			$this->getClassReflectionExtensionRegistryProvider(),
			$this->getParser(),
			$fileTypeMapper,
			self::getContainer()->getByType(StubPhpDocProvider::class),
			self::getContainer()->getByType(PhpVersion::class),
			$phpDocInheritanceResolver,
			$fileHelper,
			$typeSpecifier,
			self::getContainer()->getByType(DynamicThrowTypeExtensionProvider::class),
			true,
			true,
			$this->getEarlyTerminatingMethodCalls(),
			$this->getEarlyTerminatingFunctionCalls(),
			true
		);
		$resolver->setAnalysedFiles(array_map(static function (string $file) use ($fileHelper): string {
			return $fileHelper->normalizePath($file);
		}, array_merge([$file], $this->getAdditionalAnalysedFiles())));

		$scopeFactory = $this->createScopeFactory($broker, $typeSpecifier);
		if (count($dynamicConstantNames) > 0) {
			$reflectionProperty = new \ReflectionProperty(DirectScopeFactory::class, 'dynamicConstantNames');
			$reflectionProperty->setAccessible(true);
			$reflectionProperty->setValue($scopeFactory, $dynamicConstantNames);
		}
		$scope = $scopeFactory->create(ScopeContext::create($file));

		$resolver->processNodes(
			$this->getParser()->parseFile($file),
			$scope,
			$callback
		);
	}

	/**
	 * @api
	 * @param string $assertType
	 * @param string $file
	 * @param mixed ...$args
	 */
	public function assertFileAsserts(
		string $assertType,
		string $file,
		...$args
	): void
	{
		if ($assertType === 'type') {
			$expectedType = $args[0];
			$expected = $expectedType->getValue();
			$actualType = $args[1];
			$actual = $actualType->describe(VerbosityLevel::precise());
			$this->assertSame(
				$expected,
				$actual,
				sprintf('Expected type %s, got type %s in %s on line %d.', $expected, $actual, $file, $args[2])
			);
		} elseif ($assertType === 'variableCertainty') {
			$expectedCertainty = $args[0];
			$actualCertainty = $args[1];
			$variableName = $args[2];
			$this->assertTrue(
				$expectedCertainty->equals($actualCertainty),
				sprintf('Expected %s, actual certainty of variable $%s is %s', $expectedCertainty->describe(), $variableName, $actualCertainty->describe())
			);
		}
	}

	/**
	 * @api
	 * @param string $file
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
				$expectedType = $scope->getType($node->args[0]->value);
				$actualType = $scope->getType($node->args[1]->value);
				$assert = ['type', $file, $expectedType, $actualType, $node->getLine()];
			} elseif ($functionName === 'PHPStan\\Testing\\assertNativeType') {
				$nativeScope = $scope->doNotTreatPhpDocTypesAsCertain();
				$expectedType = $nativeScope->getNativeType($node->args[0]->value);
				$actualType = $nativeScope->getNativeType($node->args[1]->value);
				$assert = ['type', $file, $expectedType, $actualType, $node->getLine()];
			} elseif ($functionName === 'PHPStan\\Testing\\assertVariableCertainty') {
				$certainty = $node->args[0]->value;
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
				$variable = $node->args[1]->value;
				if (!$variable instanceof Node\Expr\Variable) {
					$this->fail(sprintf('ERROR: Invalid assertVariableCertainty call.'));
				}
				if (!is_string($variable->name)) {
					$this->fail(sprintf('ERROR: Invalid assertVariableCertainty call.'));
				}

				$actualCertaintyValue = $scope->hasVariableType($variable->name);
				$assert = ['variableCertainty', $file, $expectedertaintyValue, $actualCertaintyValue, $variable->name];
			} else {
				return;
			}

			if (count($node->args) !== 2) {
				$this->fail(sprintf(
					'ERROR: Wrong %s() call on line %d.',
					$functionName,
					$node->getLine()
				));
			}

			$asserts[$file . ':' . $node->getLine()] = $assert;
		});

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
