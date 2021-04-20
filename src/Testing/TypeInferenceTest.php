<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Analyser\DirectScopeFactory;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Broker\Broker;
use PHPStan\Cache\Cache;
use PHPStan\File\FileHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\Reflection\ReflectionProvider\DirectReflectionProviderProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\FileTypeMapper;

abstract class TypeInferenceTest extends \PHPStan\Testing\TestCase
{

	/** @var bool */
	protected $polluteCatchScopeWithTryAssignments = true;

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
			self::getContainer()->getByType(PhpVersion::class),
			$phpDocInheritanceResolver,
			$fileHelper,
			$typeSpecifier,
			true,
			$this->polluteCatchScopeWithTryAssignments,
			true,
			[
				\EarlyTermination\Foo::class => [
					'doFoo',
					'doBar',
				],
			],
			['baz'],
			true,
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

	/** @return string[] */
	protected function getAdditionalAnalysedFiles(): array
	{
		return [];
	}

}
