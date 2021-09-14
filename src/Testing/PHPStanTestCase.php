<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use Composer\Autoload\ClassLoader;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\DirectScopeFactory;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierFactory;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\ConstantReflector;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\Parser\CachedParser;
use PHPStan\Parser\PhpParserDecorator;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingClassReflector;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingConstantReflector;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingFunctionReflector;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\TypeAliasResolver;

/** @api */
abstract class PHPStanTestCase extends \PHPUnit\Framework\TestCase
{

	/** @var bool */
	public static $useStaticReflectionProvider = false;

	/** @var array<string, Container> */
	private static array $containers = [];

	/** @var array{ClassReflector, FunctionReflector, ConstantReflector}|null */
	private static $reflectors;

	/** @var PhpStormStubsSourceStubber|null */
	private static $phpStormStubsSourceStubber;

	/** @api */
	public static function getContainer(): Container
	{
		$additionalConfigFiles = static::getAdditionalConfigFiles();
		$additionalConfigFiles[] = __DIR__ . '/TestCase.neon';
		if (self::$useStaticReflectionProvider) {
			$additionalConfigFiles[] = __DIR__ . '/TestCase-staticReflection.neon';
		}
		$cacheKey = sha1(implode("\n", $additionalConfigFiles));

		if (!isset(self::$containers[$cacheKey])) {
			$tmpDir = sys_get_temp_dir() . '/phpstan-tests';
			if (!@mkdir($tmpDir, 0777) && !is_dir($tmpDir)) {
				self::fail(sprintf('Cannot create temp directory %s', $tmpDir));
			}

			$rootDir = __DIR__ . '/../..';
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

	public function getParser(): \PHPStan\Parser\Parser
	{
		/** @var \PHPStan\Parser\Parser $parser */
		$parser = self::getContainer()->getByType(CachedParser::class);
		return $parser;
	}

	/**
	 * @api
	 */
	public function createBroker(): ReflectionProvider
	{
		return $this->createReflectionProvider();
	}

	/** @api */
	public function createReflectionProvider(): ReflectionProvider
	{
		return self::getContainer()->getByType(ReflectionProvider::class);
	}

	private static function getPhpStormStubsSourceStubber(): PhpStormStubsSourceStubber
	{
		if (self::$phpStormStubsSourceStubber === null) {
			self::$phpStormStubsSourceStubber = self::getContainer()->getByType(PhpStormStubsSourceStubber::class);
		}

		return self::$phpStormStubsSourceStubber;
	}

	/**
	 * @return array{ClassReflector, FunctionReflector, ConstantReflector}
	 */
	public static function getReflectors(): array
	{
		if (self::$reflectors !== null) {
			return self::$reflectors;
		}

		if (!class_exists(ClassLoader::class)) {
			self::fail('Composer ClassLoader is unknown');
		}

		$classLoaderReflection = new \ReflectionClass(ClassLoader::class);
		if ($classLoaderReflection->getFileName() === false) {
			self::fail('Unknown ClassLoader filename');
		}

		$composerProjectPath = dirname($classLoaderReflection->getFileName(), 3);
		if (!is_file($composerProjectPath . '/composer.json')) {
			self::fail(sprintf('composer.json not found in directory %s', $composerProjectPath));
		}

		$composerJsonAndInstalledJsonSourceLocatorMaker = self::getContainer()->getByType(ComposerJsonAndInstalledJsonSourceLocatorMaker::class);
		$composerSourceLocator = $composerJsonAndInstalledJsonSourceLocatorMaker->create($composerProjectPath);
		if ($composerSourceLocator === null) {
			self::fail('Could not create composer source locator');
		}

		// these need to be synced with TestCase-staticReflection.neon file and TestCaseSourceLocatorFactory

		$locators = [
			$composerSourceLocator,
		];

		$phpParser = new PhpParserDecorator(self::getContainer()->getByType(CachedParser::class));

		/** @var FunctionReflector $functionReflector */
		$functionReflector = null;
		$astLocator = new Locator($phpParser, static function () use (&$functionReflector): FunctionReflector {
			return $functionReflector;
		});
		$astPhp8Locator = new Locator(self::getContainer()->getService('php8PhpParser'), static function () use (&$functionReflector): FunctionReflector {
			return $functionReflector;
		});
		$reflectionSourceStubber = new ReflectionSourceStubber();
		$locators[] = new PhpInternalSourceLocator($astPhp8Locator, self::getPhpStormStubsSourceStubber());
		$locators[] = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class));
		$locators[] = new PhpInternalSourceLocator($astLocator, $reflectionSourceStubber);
		$locators[] = new EvaledCodeSourceLocator($astLocator, $reflectionSourceStubber);
		$sourceLocator = new MemoizingSourceLocator(new AggregateSourceLocator($locators));

		$classReflector = new MemoizingClassReflector($sourceLocator);
		$functionReflector = new MemoizingFunctionReflector($sourceLocator, $classReflector);
		$constantReflector = new MemoizingConstantReflector($sourceLocator, $classReflector);

		self::$reflectors = [$classReflector, $functionReflector, $constantReflector];

		return self::$reflectors;
	}

	public function getClassReflectionExtensionRegistryProvider(): ClassReflectionExtensionRegistryProvider
	{
		return self::getContainer()->getByType(ClassReflectionExtensionRegistryProvider::class);
	}

	public function createScopeFactory(ReflectionProvider $reflectionProvider, TypeSpecifier $typeSpecifier): ScopeFactory
	{
		$container = self::getContainer();

		return new DirectScopeFactory(
			MutatingScope::class,
			$reflectionProvider,
			$container->getByType(DynamicReturnTypeExtensionRegistryProvider::class),
			$container->getByType(OperatorTypeSpecifyingExtensionRegistryProvider::class),
			new \PhpParser\PrettyPrinter\Standard(),
			$typeSpecifier,
			new PropertyReflectionFinder(),
			$this->getParser(),
			self::getContainer()->getByType(NodeScopeResolver::class),
			$this->shouldTreatPhpDocTypesAsCertain(),
			false,
			$container
		);
	}

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public function createTypeAliasResolver(array $globalTypeAliases, ReflectionProvider $reflectionProvider): TypeAliasResolver
	{
		$container = self::getContainer();

		return new TypeAliasResolver(
			$globalTypeAliases,
			$container->getByType(TypeStringResolver::class),
			$container->getByType(TypeNodeResolver::class),
			$reflectionProvider
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return true;
	}

	/**
	 * @param \PhpParser\PrettyPrinter\Standard $printer
	 * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
	 * @param \PHPStan\Type\MethodTypeSpecifyingExtension[] $methodTypeSpecifyingExtensions
	 * @param \PHPStan\Type\StaticMethodTypeSpecifyingExtension[] $staticMethodTypeSpecifyingExtensions
	 * @return \PHPStan\Analyser\TypeSpecifier
	 */
	public function createTypeSpecifier(
		Standard $printer,
		ReflectionProvider $reflectionProvider,
		array $methodTypeSpecifyingExtensions = [],
		array $staticMethodTypeSpecifyingExtensions = []
	): TypeSpecifier
	{
		return new TypeSpecifier(
			$printer,
			$reflectionProvider,
			true,
			self::getContainer()->getServicesByTag(TypeSpecifierFactory::FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG),
			array_merge($methodTypeSpecifyingExtensions, self::getContainer()->getServicesByTag(TypeSpecifierFactory::METHOD_TYPE_SPECIFYING_EXTENSION_TAG)),
			array_merge($staticMethodTypeSpecifyingExtensions, self::getContainer()->getServicesByTag(TypeSpecifierFactory::STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG))
		);
	}

	public function getFileHelper(): FileHelper
	{
		return self::getContainer()->getByType(FileHelper::class);
	}

	/**
	 * Provides a DIRECTORY_SEPARATOR agnostic assertion helper, to compare file paths.
	 *
	 * @param string $expected
	 * @param string $actual
	 * @param string $message
	 */
	protected function assertSamePaths(string $expected, string $actual, string $message = ''): void
	{
		$expected = $this->getFileHelper()->normalizePath($expected);
		$actual = $this->getFileHelper()->normalizePath($actual);

		$this->assertSame($expected, $actual, $message);
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
