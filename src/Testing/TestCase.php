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
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Broker\Broker;
use PHPStan\Broker\BrokerFactory;
use PHPStan\Cache\Cache;
use PHPStan\Cache\MemoryCacheStorage;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\Reflection\DirectClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DirectDynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DirectOperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Parser\CachedParser;
use PHPStan\Parser\FunctionCallStatementFinder;
use PHPStan\Parser\Parser;
use PHPStan\Parser\PhpParserDecorator;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\BetterReflection\BetterReflectionProvider;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingClassReflector;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingConstantReflector;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingFunctionReflector;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\Mixin\MixinMethodsClassReflectionExtension;
use PHPStan\Reflection\Mixin\MixinPropertiesClassReflectionExtension;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProvider\ClassBlacklistReflectionProvider;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderFactory;
use PHPStan\Reflection\Runtime\RuntimeReflectionProvider;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Php\SimpleXMLElementClassPropertyReflectionExtension;
use PHPStan\Type\Type;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

	/** @var bool */
	public static $useStaticReflectionProvider = false;

	/** @var array<string, Container> */
	private static array $containers = [];

	private ?DirectClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider = null;

	/** @var array{ClassReflector, FunctionReflector, ConstantReflector}|null */
	private static $reflectors;

	/** @var PhpStormStubsSourceStubber|null */
	private static $phpStormStubsSourceStubber;

	public static function getContainer(): Container
	{
		$additionalConfigFiles = static::getAdditionalConfigFiles();
		$cacheKey = sha1(implode("\n", $additionalConfigFiles));

		if (!isset(self::$containers[$cacheKey])) {
			$tmpDir = sys_get_temp_dir() . '/phpstan-tests';
			if (!@mkdir($tmpDir, 0777) && !is_dir($tmpDir)) {
				self::fail(sprintf('Cannot create temp directory %s', $tmpDir));
			}

			if (self::$useStaticReflectionProvider) {
				$additionalConfigFiles[] = __DIR__ . '/TestCase-staticReflection.neon';
			}

			$rootDir = __DIR__ . '/../..';
			$containerFactory = new ContainerFactory($rootDir);
			self::$containers[$cacheKey] = $containerFactory->create($tmpDir, array_merge([
				$containerFactory->getConfigDirectory() . '/config.level8.neon',
			], $additionalConfigFiles), []);
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
	 * @param \PHPStan\Type\DynamicMethodReturnTypeExtension[] $dynamicMethodReturnTypeExtensions
	 * @param \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[] $dynamicStaticMethodReturnTypeExtensions
	 * @return \PHPStan\Broker\Broker
	 */
	public function createBroker(
		array $dynamicMethodReturnTypeExtensions = [],
		array $dynamicStaticMethodReturnTypeExtensions = []
	): Broker
	{
		$dynamicReturnTypeExtensionRegistryProvider = new DirectDynamicReturnTypeExtensionRegistryProvider(
			array_merge(self::getContainer()->getServicesByTag(BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG), $dynamicMethodReturnTypeExtensions, $this->getDynamicMethodReturnTypeExtensions()),
			array_merge(self::getContainer()->getServicesByTag(BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG), $dynamicStaticMethodReturnTypeExtensions, $this->getDynamicStaticMethodReturnTypeExtensions()),
			array_merge(self::getContainer()->getServicesByTag(BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG), $this->getDynamicFunctionReturnTypeExtensions())
		);
		$operatorTypeSpecifyingExtensionRegistryProvider = new DirectOperatorTypeSpecifyingExtensionRegistryProvider(
			$this->getOperatorTypeSpecifyingExtensions()
		);
		$reflectionProvider = $this->createReflectionProvider();
		$broker = new Broker(
			$reflectionProvider,
			$dynamicReturnTypeExtensionRegistryProvider,
			$operatorTypeSpecifyingExtensionRegistryProvider,
			self::getContainer()->getParameter('universalObjectCratesClasses')
		);
		$dynamicReturnTypeExtensionRegistryProvider->setBroker($broker);
		$dynamicReturnTypeExtensionRegistryProvider->setReflectionProvider($reflectionProvider);
		$operatorTypeSpecifyingExtensionRegistryProvider->setBroker($broker);
		$this->getClassReflectionExtensionRegistryProvider()->setBroker($broker);

		return $broker;
	}

	public function createReflectionProvider(): ReflectionProvider
	{
		$staticReflectionProvider = $this->createStaticReflectionProvider();
		return $this->createReflectionProviderByParameters(
			$this->createRuntimeReflectionProvider($staticReflectionProvider),
			$staticReflectionProvider,
			self::$useStaticReflectionProvider
		);
	}

	private function createReflectionProviderByParameters(
		ReflectionProvider $runtimeReflectionProvider,
		ReflectionProvider $staticReflectionProvider,
		bool $disableRuntimeReflectionProvider
	): ReflectionProvider
	{
		$setterReflectionProviderProvider = new ReflectionProvider\SetterReflectionProviderProvider();
		$reflectionProviderFactory = new ReflectionProviderFactory(
			$runtimeReflectionProvider,
			$staticReflectionProvider,
			$disableRuntimeReflectionProvider
		);
		$reflectionProvider = $reflectionProviderFactory->create();
		$setterReflectionProviderProvider->setReflectionProvider($reflectionProvider);

		return $reflectionProvider;
	}

	private static function getPhpStormStubsSourceStubber(): PhpStormStubsSourceStubber
	{
		if (self::$phpStormStubsSourceStubber === null) {
			self::$phpStormStubsSourceStubber = self::getContainer()->getByType(PhpStormStubsSourceStubber::class);
		}

		return self::$phpStormStubsSourceStubber;
	}

	private function createRuntimeReflectionProvider(ReflectionProvider $actualReflectionProvider): ReflectionProvider
	{
		$functionCallStatementFinder = new FunctionCallStatementFinder();
		$parser = $this->getParser();
		$cache = new Cache(new MemoryCacheStorage());
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);
		$phpDocNodeResolver = self::getContainer()->getByType(PhpDocNodeResolver::class);
		$currentWorkingDirectory = $this->getCurrentWorkingDirectory();
		$fileHelper = new FileHelper($currentWorkingDirectory);
		$anonymousClassNameHelper = new AnonymousClassNameHelper(new FileHelper($currentWorkingDirectory), new SimpleRelativePathHelper($fileHelper->normalizePath($currentWorkingDirectory, '/')));
		$setterReflectionProviderProvider = new ReflectionProvider\SetterReflectionProviderProvider();
		$fileTypeMapper = new FileTypeMapper($setterReflectionProviderProvider, $parser, $phpDocStringResolver, $phpDocNodeResolver, $cache, $anonymousClassNameHelper);
		$classReflectionExtensionRegistryProvider = $this->getClassReflectionExtensionRegistryProvider();
		$functionReflectionFactory = $this->getFunctionReflectionFactory(
			$functionCallStatementFinder,
			$cache
		);
		$reflectionProvider = new ClassBlacklistReflectionProvider(
			new RuntimeReflectionProvider(
				$setterReflectionProviderProvider,
				$classReflectionExtensionRegistryProvider,
				$functionReflectionFactory,
				$fileTypeMapper,
				self::getContainer()->getByType(PhpVersion::class),
				self::getContainer()->getByType(NativeFunctionReflectionProvider::class),
				self::getContainer()->getByType(StubPhpDocProvider::class),
				self::getContainer()->getByType(PhpStormStubsSourceStubber::class)
			),
			self::getPhpStormStubsSourceStubber(),
			[
				'#^PhpParser\\\\#',
				'#^PHPStan\\\\#',
				'#^Hoa\\\\#',
			],
			null
		);
		$this->setUpReflectionProvider(
			$actualReflectionProvider,
			$setterReflectionProviderProvider,
			$classReflectionExtensionRegistryProvider,
			$functionCallStatementFinder,
			$parser,
			$cache,
			$fileTypeMapper
		);

		return $reflectionProvider;
	}

	private function setUpReflectionProvider(
		ReflectionProvider $actualReflectionProvider,
		ReflectionProvider\SetterReflectionProviderProvider $setterReflectionProviderProvider,
		DirectClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider,
		FunctionCallStatementFinder $functionCallStatementFinder,
		\PHPStan\Parser\Parser $parser,
		Cache $cache,
		FileTypeMapper $fileTypeMapper
	): void
	{
		$methodReflectionFactory = new class($parser, $functionCallStatementFinder, $cache) implements PhpMethodReflectionFactory {

			private \PHPStan\Parser\Parser $parser;

			private \PHPStan\Parser\FunctionCallStatementFinder $functionCallStatementFinder;

			private \PHPStan\Cache\Cache $cache;

			public ReflectionProvider $reflectionProvider;

			public function __construct(
				Parser $parser,
				FunctionCallStatementFinder $functionCallStatementFinder,
				Cache $cache
			)
			{
				$this->parser = $parser;
				$this->functionCallStatementFinder = $functionCallStatementFinder;
				$this->cache = $cache;
			}

			/**
			 * @param ClassReflection $declaringClass
			 * @param ClassReflection|null $declaringTrait
			 * @param \PHPStan\Reflection\Php\BuiltinMethodReflection $reflection
			 * @param TemplateTypeMap $templateTypeMap
			 * @param Type[] $phpDocParameterTypes
			 * @param Type|null $phpDocReturnType
			 * @param Type|null $phpDocThrowType
			 * @param string|null $deprecatedDescription
			 * @param bool $isDeprecated
			 * @param bool $isInternal
			 * @param bool $isFinal
			 * @param string|null $stubPhpDocString
			 * @return PhpMethodReflection
			 */
			public function create(
				ClassReflection $declaringClass,
				?ClassReflection $declaringTrait,
				\PHPStan\Reflection\Php\BuiltinMethodReflection $reflection,
				TemplateTypeMap $templateTypeMap,
				array $phpDocParameterTypes,
				?Type $phpDocReturnType,
				?Type $phpDocThrowType,
				?string $deprecatedDescription,
				bool $isDeprecated,
				bool $isInternal,
				bool $isFinal,
				bool $isPure,
				?string $stubPhpDocString
			): PhpMethodReflection
			{
				return new PhpMethodReflection(
					$declaringClass,
					$declaringTrait,
					$reflection,
					$this->reflectionProvider,
					$this->parser,
					$this->functionCallStatementFinder,
					$this->cache,
					$templateTypeMap,
					$phpDocParameterTypes,
					$phpDocReturnType,
					$phpDocThrowType,
					$deprecatedDescription,
					$isDeprecated,
					$isInternal,
					$isFinal,
					$isPure,
					$stubPhpDocString
				);
			}

		};
		$phpDocInheritanceResolver = new PhpDocInheritanceResolver($fileTypeMapper);
		$annotationsMethodsClassReflectionExtension = new AnnotationsMethodsClassReflectionExtension();
		$annotationsPropertiesClassReflectionExtension = new AnnotationsPropertiesClassReflectionExtension();
		$signatureMapProvider = self::getContainer()->getByType(SignatureMapProvider::class);
		$methodReflectionFactory->reflectionProvider = $actualReflectionProvider;
		$phpExtension = new PhpClassReflectionExtension(self::getContainer()->getByType(ScopeFactory::class), self::getContainer()->getByType(NodeScopeResolver::class), $methodReflectionFactory, $phpDocInheritanceResolver, $annotationsMethodsClassReflectionExtension, $annotationsPropertiesClassReflectionExtension, $signatureMapProvider, $parser, self::getContainer()->getByType(StubPhpDocProvider::class), $actualReflectionProvider, $fileTypeMapper, true, []);
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension($phpExtension);
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension(new UniversalObjectCratesClassReflectionExtension([\stdClass::class]));
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension(new MixinPropertiesClassReflectionExtension([]));
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension(new SimpleXMLElementClassPropertyReflectionExtension());
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension($annotationsPropertiesClassReflectionExtension);
		$classReflectionExtensionRegistryProvider->addMethodsClassReflectionExtension($phpExtension);
		$classReflectionExtensionRegistryProvider->addMethodsClassReflectionExtension(new MixinMethodsClassReflectionExtension([]));
		$classReflectionExtensionRegistryProvider->addMethodsClassReflectionExtension($annotationsMethodsClassReflectionExtension);

		$setterReflectionProviderProvider->setReflectionProvider($actualReflectionProvider);
	}

	private function createStaticReflectionProvider(): ReflectionProvider
	{
		$parser = $this->getParser();
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);
		$phpDocNodeResolver = self::getContainer()->getByType(PhpDocNodeResolver::class);
		$currentWorkingDirectory = $this->getCurrentWorkingDirectory();
		$cache = new Cache(new MemoryCacheStorage());
		$fileHelper = new FileHelper($currentWorkingDirectory);
		$relativePathHelper = new SimpleRelativePathHelper($currentWorkingDirectory);
		$anonymousClassNameHelper = new AnonymousClassNameHelper($fileHelper, new SimpleRelativePathHelper($fileHelper->normalizePath($currentWorkingDirectory, '/')));
		$setterReflectionProviderProvider = new ReflectionProvider\SetterReflectionProviderProvider();
		$fileTypeMapper = new FileTypeMapper($setterReflectionProviderProvider, $parser, $phpDocStringResolver, $phpDocNodeResolver, $cache, $anonymousClassNameHelper);
		$functionCallStatementFinder = new FunctionCallStatementFinder();
		$functionReflectionFactory = $this->getFunctionReflectionFactory(
			$functionCallStatementFinder,
			$cache
		);

		[$classReflector, $functionReflector, $constantReflector] = self::getReflectors();

		$classReflectionExtensionRegistryProvider = $this->getClassReflectionExtensionRegistryProvider();

		$reflectionProvider = new BetterReflectionProvider(
			$setterReflectionProviderProvider,
			$classReflectionExtensionRegistryProvider,
			$classReflector,
			$fileTypeMapper,
			self::getContainer()->getByType(PhpVersion::class),
			self::getContainer()->getByType(NativeFunctionReflectionProvider::class),
			self::getContainer()->getByType(StubPhpDocProvider::class),
			$functionReflectionFactory,
			$relativePathHelper,
			$anonymousClassNameHelper,
			self::getContainer()->getByType(Standard::class),
			$fileHelper,
			$functionReflector,
			$constantReflector
		);

		$this->setUpReflectionProvider(
			$reflectionProvider,
			$setterReflectionProviderProvider,
			$classReflectionExtensionRegistryProvider,
			$functionCallStatementFinder,
			$parser,
			$cache,
			$fileTypeMapper
		);

		return $reflectionProvider;
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
		$reflectionSourceStubber = new ReflectionSourceStubber();
		$locators[] = new PhpInternalSourceLocator($astLocator, self::getPhpStormStubsSourceStubber());
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

	private function getFunctionReflectionFactory(
		FunctionCallStatementFinder $functionCallStatementFinder,
		Cache $cache
	): FunctionReflectionFactory
	{
		return new class($this->getParser(), $functionCallStatementFinder, $cache) implements FunctionReflectionFactory {

			private \PHPStan\Parser\Parser $parser;

			private \PHPStan\Parser\FunctionCallStatementFinder $functionCallStatementFinder;

			private \PHPStan\Cache\Cache $cache;

			public function __construct(
				Parser $parser,
				FunctionCallStatementFinder $functionCallStatementFinder,
				Cache $cache
			)
			{
				$this->parser = $parser;
				$this->functionCallStatementFinder = $functionCallStatementFinder;
				$this->cache = $cache;
			}

			/**
			 * @param \ReflectionFunction $function
			 * @param TemplateTypeMap $templateTypeMap
			 * @param Type[] $phpDocParameterTypes
			 * @param Type|null $phpDocReturnType
			 * @param Type|null $phpDocThrowType
			 * @param string|null $deprecatedDescription
			 * @param bool $isDeprecated
			 * @param bool $isInternal
			 * @param bool $isFinal
			 * @param string|false $filename
			 * @return PhpFunctionReflection
			 */
			public function create(
				\ReflectionFunction $function,
				TemplateTypeMap $templateTypeMap,
				array $phpDocParameterTypes,
				?Type $phpDocReturnType,
				?Type $phpDocThrowType,
				?string $deprecatedDescription,
				bool $isDeprecated,
				bool $isInternal,
				bool $isFinal,
				bool $isPure,
				$filename
			): PhpFunctionReflection
			{
				return new PhpFunctionReflection(
					$function,
					$this->parser,
					$this->functionCallStatementFinder,
					$this->cache,
					$templateTypeMap,
					$phpDocParameterTypes,
					$phpDocReturnType,
					$phpDocThrowType,
					$deprecatedDescription,
					$isDeprecated,
					$isInternal,
					$isFinal,
					$isPure,
					$filename
				);
			}

		};
	}

	public function getClassReflectionExtensionRegistryProvider(): DirectClassReflectionExtensionRegistryProvider
	{
		if ($this->classReflectionExtensionRegistryProvider === null) {
			$this->classReflectionExtensionRegistryProvider = new DirectClassReflectionExtensionRegistryProvider([], []);
		}

		return $this->classReflectionExtensionRegistryProvider;
	}

	public function createScopeFactory(Broker $broker, TypeSpecifier $typeSpecifier): ScopeFactory
	{
		$container = self::getContainer();

		return new DirectScopeFactory(
			MutatingScope::class,
			$broker,
			$broker->getDynamicReturnTypeExtensionRegistryProvider(),
			$broker->getOperatorTypeSpecifyingExtensionRegistryProvider(),
			new \PhpParser\PrettyPrinter\Standard(),
			$typeSpecifier,
			new PropertyReflectionFinder(),
			$this->getParser(),
			$this->shouldTreatPhpDocTypesAsCertain(),
			$container
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return true;
	}

	public function getCurrentWorkingDirectory(): string
	{
		return $this->getFileHelper()->normalizePath(__DIR__ . '/../..');
	}

	/**
	 * @return \PHPStan\Type\DynamicMethodReturnTypeExtension[]
	 */
	public function getDynamicMethodReturnTypeExtensions(): array
	{
		return [];
	}

	/**
	 * @return \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[]
	 */
	public function getDynamicStaticMethodReturnTypeExtensions(): array
	{
		return [];
	}

	/**
	 * @return \PHPStan\Type\DynamicFunctionReturnTypeExtension[]
	 */
	public function getDynamicFunctionReturnTypeExtensions(): array
	{
		return [];
	}

	/**
	 * @return \PHPStan\Type\OperatorTypeSpecifyingExtension[]
	 */
	public function getOperatorTypeSpecifyingExtensions(): array
	{
		return [];
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
			self::getContainer()->getServicesByTag(TypeSpecifierFactory::FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG),
			$methodTypeSpecifyingExtensions,
			$staticMethodTypeSpecifyingExtensions
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
