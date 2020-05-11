<?php declare(strict_types = 1);

namespace PHPStan\Testing;

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
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Reflection\DirectClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DirectDynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DirectOperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Parser\FunctionCallStatementFinder;
use PHPStan\Parser\Parser;
use PHPStan\Parser\PhpParserDecorator;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\BetterReflection\BetterReflectionProvider;
use PHPStan\Reflection\BetterReflection\BetterReflectionProviderFactory;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\PhpDefect\PhpDefectClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderFactory;
use PHPStan\Reflection\Runtime\RuntimeReflectionProvider;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

	/** @var array<string, Container> */
	private static $containers = [];

	/** @var DirectClassReflectionExtensionRegistryProvider|null */
	private $classReflectionExtensionRegistryProvider;

	public static function getContainer(): Container
	{
		$additionalConfigFiles = static::getAdditionalConfigFiles();
		$cacheKey = sha1(implode("\n", $additionalConfigFiles));

		if (!isset(self::$containers[$cacheKey])) {
			$tmpDir = sys_get_temp_dir() . '/phpstan-tests';
			if (!@mkdir($tmpDir, 0777) && !is_dir($tmpDir)) {
				self::fail(sprintf('Cannot create temp directory %s', $tmpDir));
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
		$parser = self::getContainer()->getService('directParser');
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
		$runtimeReflectionProvider = $this->createRuntimeReflectionProvider();
		$parser = $this->getParser();
		$phpParser = new PhpParserDecorator($parser);
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);
		$phpDocNodeResolver = self::getContainer()->getByType(PhpDocNodeResolver::class);
		$cache = new Cache(new MemoryCacheStorage());
		$currentWorkingDirectory = $this->getCurrentWorkingDirectory();
		$fileHelper = new FileHelper($currentWorkingDirectory);
		$currentWorkingDirectory = $fileHelper->normalizePath($currentWorkingDirectory, '/');
		$fileHelper = new FileHelper($currentWorkingDirectory);
		$relativePathHelper = new SimpleRelativePathHelper($currentWorkingDirectory);
		$anonymousClassNameHelper = new AnonymousClassNameHelper($fileHelper, $relativePathHelper);
		$setterReflectionProviderProvider = new ReflectionProvider\SetterReflectionProviderProvider();
		$fileTypeMapper = new FileTypeMapper($setterReflectionProviderProvider, $parser, $phpDocStringResolver, $phpDocNodeResolver, $cache, $anonymousClassNameHelper);
		$functionCallStatementFinder = new FunctionCallStatementFinder();
		$functionReflectionFactory = $this->getFunctionReflectionFactory(
			$functionCallStatementFinder,
			$cache
		);

		$reflectionProviderFactory = new ReflectionProviderFactory(
			$runtimeReflectionProvider,
			$phpParser,
			new PhpStormStubsSourceStubber($phpParser),
			new class(
				$this->getClassReflectionExtensionRegistryProvider(),
				self::getContainer(),
				$fileTypeMapper,
				$functionReflectionFactory,
				$relativePathHelper,
				$anonymousClassNameHelper,
				$parser,
				$fileHelper
			) implements BetterReflectionProviderFactory {

				/** @var \PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider */
				private $classReflectionExtensionRegistryProvider;

				/** @var Container */
				private $container;

				/** @var FileTypeMapper */
				private $fileTypeMapper;

				/** @var FunctionReflectionFactory */
				private $functionReflectionFactory;

				/** @var \PHPStan\File\RelativePathHelper */
				private $relativePathHelper;

				/** @var AnonymousClassNameHelper */
				private $anonymousClassNameHelper;

				/** @var Parser */
				private $parser;

				/** @var \PHPStan\File\FileHelper */
				private $fileHelper;

				public function __construct(
					ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider,
					Container $container,
					FileTypeMapper $fileTypeMapper,
					FunctionReflectionFactory $functionReflectionFactory,
					RelativePathHelper $relativePathHelper,
					AnonymousClassNameHelper $anonymousClassNameHelper,
					Parser $parser,
					FileHelper $fileHelper
				)
				{
					$this->classReflectionExtensionRegistryProvider = $classReflectionExtensionRegistryProvider;
					$this->container = $container;
					$this->fileTypeMapper = $fileTypeMapper;
					$this->functionReflectionFactory = $functionReflectionFactory;
					$this->relativePathHelper = $relativePathHelper;
					$this->anonymousClassNameHelper = $anonymousClassNameHelper;
					$this->parser = $parser;
					$this->fileHelper = $fileHelper;
				}

				public function create(
					FunctionReflector $functionReflector,
					ClassReflector $classReflector,
					ConstantReflector $constantReflector
				): BetterReflectionProvider
				{
					return new BetterReflectionProvider(
						$this->classReflectionExtensionRegistryProvider,
						$classReflector,
						$this->fileTypeMapper,
						$this->container->getByType(NativeFunctionReflectionProvider::class),
						$this->container->getByType(StubPhpDocProvider::class),
						$this->functionReflectionFactory,
						$this->relativePathHelper,
						$this->anonymousClassNameHelper,
						$this->container->getByType(Standard::class),
						$this->parser,
						$this->fileHelper,
						$functionReflector,
						$constantReflector
					);
				}

			},
			$this->createMock(ReflectionProvider::class),
			false,
			false
		);

		$reflectionProvider = $reflectionProviderFactory->create();
		$setterReflectionProviderProvider->setReflectionProvider($reflectionProvider);

		return $reflectionProvider;
	}

	private function createRuntimeReflectionProvider(): RuntimeReflectionProvider
	{
		$functionCallStatementFinder = new FunctionCallStatementFinder();
		$parser = $this->getParser();
		$cache = new Cache(new MemoryCacheStorage());
		$methodReflectionFactory = new class($parser, $functionCallStatementFinder, $cache) implements PhpMethodReflectionFactory {

			/** @var \PHPStan\Parser\Parser */
			private $parser;

			/** @var \PHPStan\Parser\FunctionCallStatementFinder */
			private $functionCallStatementFinder;

			/** @var \PHPStan\Cache\Cache */
			private $cache;

			/** @var ReflectionProvider */
			public $reflectionProvider;

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
					$stubPhpDocString
				);
			}

		};
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);
		$phpDocNodeResolver = self::getContainer()->getByType(PhpDocNodeResolver::class);
		$currentWorkingDirectory = $this->getCurrentWorkingDirectory();
		$fileHelper = new FileHelper($currentWorkingDirectory);
		$relativePathHelper = new SimpleRelativePathHelper($currentWorkingDirectory);
		$anonymousClassNameHelper = new AnonymousClassNameHelper(new FileHelper($currentWorkingDirectory), new SimpleRelativePathHelper($fileHelper->normalizePath($currentWorkingDirectory, '/')));
		$setterReflectionProvider = new ReflectionProvider\SetterReflectionProviderProvider();
		$fileTypeMapper = new FileTypeMapper($setterReflectionProvider, $parser, $phpDocStringResolver, $phpDocNodeResolver, $cache, $anonymousClassNameHelper);
		$phpDocInheritanceResolver = new PhpDocInheritanceResolver($fileTypeMapper);
		$annotationsMethodsClassReflectionExtension = new AnnotationsMethodsClassReflectionExtension($fileTypeMapper);
		$annotationsPropertiesClassReflectionExtension = new AnnotationsPropertiesClassReflectionExtension($fileTypeMapper);
		$signatureMapProvider = self::getContainer()->getByType(SignatureMapProvider::class);
		$classReflectionExtensionRegistryProvider = $this->getClassReflectionExtensionRegistryProvider();
		$functionReflectionFactory = $this->getFunctionReflectionFactory(
			$functionCallStatementFinder,
			$cache
		);
		$reflectionProvider = new RuntimeReflectionProvider(
			$classReflectionExtensionRegistryProvider,
			$functionReflectionFactory,
			$fileTypeMapper,
			self::getContainer()->getByType(NativeFunctionReflectionProvider::class),
			self::getContainer()->getByType(Standard::class),
			$anonymousClassNameHelper,
			self::getContainer()->getByType(Parser::class),
			$fileHelper,
			$relativePathHelper,
			self::getContainer()->getByType(StubPhpDocProvider::class)
		);
		$methodReflectionFactory->reflectionProvider = $reflectionProvider;
		$phpExtension = new PhpClassReflectionExtension(self::getContainer()->getByType(ScopeFactory::class), self::getContainer()->getByType(NodeScopeResolver::class), $methodReflectionFactory, $phpDocInheritanceResolver, $annotationsMethodsClassReflectionExtension, $annotationsPropertiesClassReflectionExtension, $signatureMapProvider, $parser, self::getContainer()->getByType(StubPhpDocProvider::class), $reflectionProvider, true, []);
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension($phpExtension);
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension(new PhpDefectClassReflectionExtension(self::getContainer()->getByType(TypeStringResolver::class), $annotationsPropertiesClassReflectionExtension));
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension(new UniversalObjectCratesClassReflectionExtension([\stdClass::class]));
		$classReflectionExtensionRegistryProvider->addPropertiesClassReflectionExtension($annotationsPropertiesClassReflectionExtension);
		$classReflectionExtensionRegistryProvider->addMethodsClassReflectionExtension($phpExtension);
		$classReflectionExtensionRegistryProvider->addMethodsClassReflectionExtension($annotationsMethodsClassReflectionExtension);

		$setterReflectionProvider->setReflectionProvider($reflectionProvider);

		return $reflectionProvider;
	}

	private function getFunctionReflectionFactory(
		FunctionCallStatementFinder $functionCallStatementFinder,
		Cache $cache
	): FunctionReflectionFactory
	{
		return new class($this->getParser(), $functionCallStatementFinder, $cache) implements FunctionReflectionFactory {

			/** @var \PHPStan\Parser\Parser */
			private $parser;

			/** @var \PHPStan\Parser\FunctionCallStatementFinder */
			private $functionCallStatementFinder;

			/** @var \PHPStan\Cache\Cache */
			private $cache;

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
					$filename
				);
			}

		};
	}

	private function getClassReflectionExtensionRegistryProvider(): DirectClassReflectionExtensionRegistryProvider
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
