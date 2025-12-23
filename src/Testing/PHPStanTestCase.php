<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\DirectInternalScopeFactoryFactory;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\AttributeReflectionFactory;
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
use function hash;
use function implode;
use function rtrim;
use function sprintf;
use function sys_get_temp_dir;
use const DIRECTORY_SEPARATOR;
use const PHP_VERSION_ID;

/** @api */
abstract class PHPStanTestCase extends TestCase
{

	/** @var array<string, Container> */
	private static array $containers = [];

	/** @api */
	public static function getContainer(): Container
	{
		$additionalConfigFiles = [__DIR__ . '/TestCase.neon'];
		foreach (static::getAdditionalConfigFiles() as $configFile) {
			$additionalConfigFiles[] = $configFile;
		}
		$cacheKey = hash('sha256', implode("\n", $additionalConfigFiles));

		if (!isset(self::$containers[$cacheKey])) {
			$tmpDir = sys_get_temp_dir() . '/phpstan-tests';
			try {
				DirectoryCreator::ensureDirectoryExists($tmpDir, 0777);
			} catch (DirectoryCreatorException $e) {
				self::fail($e->getMessage());
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

	public static function getParser(): Parser
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('defaultAnalysisParser');
		return $parser;
	}

	/** @api */
	public static function createReflectionProvider(): ReflectionProvider
	{
		return self::getContainer()->getByType(ReflectionProvider::class);
	}

	public static function getReflector(): Reflector
	{
		return self::getContainer()->getService('betterReflectionReflector');
	}

	public static function getClassReflectionExtensionRegistryProvider(): ClassReflectionExtensionRegistryProvider
	{
		return self::getContainer()->getByType(ClassReflectionExtensionRegistryProvider::class);
	}

	/**
	 * @param string[] $dynamicConstantNames
	 */
	public static function createScopeFactory(ReflectionProvider $reflectionProvider, TypeSpecifier $typeSpecifier, array $dynamicConstantNames = []): ScopeFactory
	{
		$container = self::getContainer();

		if (count($dynamicConstantNames) === 0) {
			$dynamicConstantNames = $container->getParameter('dynamicConstantNames');
		}

		$reflectionProviderProvider = new DirectReflectionProviderProvider($reflectionProvider);
		$composerPhpVersionFactory = $container->getByType(ComposerPhpVersionFactory::class);
		$constantResolver = new ConstantResolver($reflectionProviderProvider, $dynamicConstantNames, null, $composerPhpVersionFactory, $container);

		$initializerExprTypeResolver = new InitializerExprTypeResolver(
			$constantResolver,
			$reflectionProviderProvider,
			$container->getByType(PhpVersion::class),
			$container->getByType(OperatorTypeSpecifyingExtensionRegistryProvider::class),
			new OversizedArrayBuilder(),
			$container->getParameter('usePathConstantsAsConstantString'),
		);

		return new ScopeFactory(
			new DirectInternalScopeFactoryFactory(
				$reflectionProvider,
				$initializerExprTypeResolver,
				$container->getByType(DynamicReturnTypeExtensionRegistryProvider::class),
				$container->getByType(ExpressionTypeResolverExtensionRegistryProvider::class),
				$container->getByType(ExprPrinter::class),
				$typeSpecifier,
				new PropertyReflectionFinder(),
				self::getParser(),
				$container->getByType(NodeScopeResolver::class),
				new RicherScopeGetTypeHelper($initializerExprTypeResolver, new PropertyReflectionFinder()),
				$container->getByType(PhpVersion::class),
				$container->getByType(AttributeReflectionFactory::class),
				$container->getParameter('phpVersion'),
				$constantResolver,
			),
		);
	}

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public static function createTypeAliasResolver(array $globalTypeAliases, ReflectionProvider $reflectionProvider): TypeAliasResolver
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

	public static function getFileHelper(): FileHelper
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
					$messages[] = sprintf("- %s\n  in %s on line %d\n", rtrim($error->getMessage(), '.'), $error->getFile(), $error->getLine() ?? 0);
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
