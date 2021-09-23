<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Analyser\DirectScopeFactory;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\ConstantReflector;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\Broker\Broker;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\Parser\CachedParser;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
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

	/**
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
