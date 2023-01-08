<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\DirectInternalScopeFactory;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\ConstantReflector;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\Broker\Broker;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProvider\DirectReflectionProviderProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\Constant\OversizedArrayBuilder;
use PHPStan\Type\TypeAliasResolver;
use PHPStan\Type\UsefulTypeAliasResolver;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use function array_merge;
use function count;
use function implode;
use function is_dir;
use function mkdir;
use function rtrim;
use function sha1;
use function sprintf;
use function sys_get_temp_dir;
use const DIRECTORY_SEPARATOR;
use const PHP_VERSION_ID;

/** @api */
abstract class PHPStanTestCase extends TestCase
{

	/** @deprecated */
	public static bool $useStaticReflectionProvider = true;

	/** @var array<string, Container> */
	private static array $containers = [];

	/** @api */
	public static function getContainer(): Container
	{
		$additionalConfigFiles = static::getAdditionalConfigFiles();
		$additionalConfigFiles[] = __DIR__ . '/TestCase.neon';
		$cacheKey = sha1(implode("\n", $additionalConfigFiles));

		if (!isset(self::$containers[$cacheKey])) {
			$tmpDir = sys_get_temp_dir() . '/phpstan-tests';
			if (!@mkdir($tmpDir, 0777) && !is_dir($tmpDir)) {
				self::fail(sprintf('Cannot create temp directory %s', $tmpDir));
			}

			$rootDir = __DIR__ . '/../..';
			$fileHelper = new FileHelper($rootDir);
			$rootDir = $fileHelper->normalizePath($rootDir, '/');
			$containerFactory = new ContainerFactory($rootDir);
			$container = $containerFactory->create($tmpDir, array_merge([
				$containerFactory->getConfigDirectory() . '/config.level8.neon',
			], $additionalConfigFiles), []);
			self::$containers[$cacheKey] = $container;

			foreach ($container->getParameter('bootstrapFiles') as $bootstrapFile) {
				(static function (string $file) use ($container): void {
					require_once $file;
				})($bootstrapFile);
			}

			if (PHP_VERSION_ID >= 80000) {
				require_once __DIR__ . '/../../stubs/runtime/Enum/UnitEnum.php';
				require_once __DIR__ . '/../../stubs/runtime/Enum/BackedEnum.php';
				require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnum.php';
				require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnumUnitCase.php';
				require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnumBackedCase.php';
			}
		} else {
			ContainerFactory::postInitializeContainer(self::$containers[$cacheKey]);
		}

		return self::$containers[$cacheKey];
	}

	/**
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array
	{
		return [];
	}

	public function getParser(): Parser
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('defaultAnalysisParser');
		return $parser;
	}

	/**
	 * @api
	 * @deprecated Use createReflectionProvider() instead
	 */
	public function createBroker(): Broker
	{
		return self::getContainer()->getByType(Broker::class);
	}

	/** @api */
	public function createReflectionProvider(): ReflectionProvider
	{
		return self::getContainer()->getByType(ReflectionProvider::class);
	}

	public static function getReflector(): Reflector
	{
		return self::getContainer()->getService('betterReflectionReflector');
	}

	/**
	 * @deprecated Use getReflector() instead.
	 * @return array{ClassReflector, FunctionReflector, ConstantReflector}
	 */
	public static function getReflectors(): array
	{
		return [
			self::getContainer()->getService('betterReflectionClassReflector'),
			self::getContainer()->getService('betterReflectionFunctionReflector'),
			self::getContainer()->getService('betterReflectionConstantReflector'),
		];
	}

	public function getClassReflectionExtensionRegistryProvider(): ClassReflectionExtensionRegistryProvider
	{
		return self::getContainer()->getByType(ClassReflectionExtensionRegistryProvider::class);
	}

	/**
	 * @param string[] $dynamicConstantNames
	 */
	public function createScopeFactory(ReflectionProvider $reflectionProvider, TypeSpecifier $typeSpecifier, array $dynamicConstantNames = []): ScopeFactory
	{
		$container = self::getContainer();

		if (count($dynamicConstantNames) === 0) {
			$dynamicConstantNames = $container->getParameter('dynamicConstantNames');
		}

		$reflectionProviderProvider = new DirectReflectionProviderProvider($reflectionProvider);
		$constantResolver = new ConstantResolver($reflectionProviderProvider, $dynamicConstantNames);

		return new ScopeFactory(
			new DirectInternalScopeFactory(
				MutatingScope::class,
				$reflectionProvider,
				new InitializerExprTypeResolver($constantResolver, $reflectionProviderProvider, new PhpVersion(PHP_VERSION_ID), $container->getByType(OperatorTypeSpecifyingExtensionRegistryProvider::class), new OversizedArrayBuilder(), $container->getParameter('usePathConstantsAsConstantString')),
				$container->getByType(DynamicReturnTypeExtensionRegistryProvider::class),
				$container->getByType(ExprPrinter::class),
				$typeSpecifier,
				new PropertyReflectionFinder(),
				$this->getParser(),
				$container->getByType(NodeScopeResolver::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				$container->getByType(PhpVersion::class),
				$container->getParameter('featureToggles')['explicitMixedInUnknownGenericNew'],
				$constantResolver,
			),
			$container->getParameter('featureToggles')['explicitMixedForGlobalVariables'],
		);
	}

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public function createTypeAliasResolver(array $globalTypeAliases, ReflectionProvider $reflectionProvider): TypeAliasResolver
	{
		$container = self::getContainer();

		return new UsefulTypeAliasResolver(
			$globalTypeAliases,
			$container->getByType(TypeStringResolver::class),
			$container->getByType(TypeNodeResolver::class),
			$reflectionProvider,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return true;
	}

	public function getFileHelper(): FileHelper
	{
		return self::getContainer()->getByType(FileHelper::class);
	}

	/**
	 * Provides a DIRECTORY_SEPARATOR agnostic assertion helper, to compare file paths.
	 *
	 */
	protected function assertSamePaths(string $expected, string $actual, string $message = ''): void
	{
		$expected = $this->getFileHelper()->normalizePath($expected);
		$actual = $this->getFileHelper()->normalizePath($actual);

		$this->assertSame($expected, $actual, $message);
	}

	/**
	 * @param Error[]|string[] $errors
	 */
	protected function assertNoErrors(array $errors): void
	{
		try {
			$this->assertCount(0, $errors);
		} catch (ExpectationFailedException $e) {
			$messages = [];
			foreach ($errors as $error) {
				if ($error instanceof Error) {
					$messages[] = sprintf("- %s\n  in %s on line %d\n", rtrim($error->getMessage(), '.'), $error->getFile(), $error->getLine());
				} else {
					$messages[] = $error;
				}
			}

			$this->fail($e->getMessage() . "\n\nEmitted errors:\n" . implode("\n", $messages));
		}
	}

	protected function skipIfNotOnWindows(): void
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			return;
		}

		self::markTestSkipped();
	}

	protected function skipIfNotOnUnix(): void
	{
		if (DIRECTORY_SEPARATOR === '/') {
			return;
		}

		self::markTestSkipped();
	}

}
