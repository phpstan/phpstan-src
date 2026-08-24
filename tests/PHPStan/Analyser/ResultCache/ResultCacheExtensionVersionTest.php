<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Command\Output;
use PHPStan\Dependency\ExportedNodeFetcher;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\File\FileHelper;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;
use PHPStan\Testing\PHPStanTestCase;

class ResultCacheExtensionVersionTest extends PHPStanTestCase
{

	public function testExtensionVersionsArePartOfMetadata(): void
	{
		$v1Meta = $this->getMeta(new ExtensionVersionProvider([
			__DIR__ . '/../../Reflection/BetterReflection/SourceStubber/data/ext-ds-v1-platform',
		]));
		$v2Meta = $this->getMeta(new ExtensionVersionProvider([
			__DIR__ . '/../../Reflection/BetterReflection/SourceStubber/data/ext-ds-v2-platform',
		]));

		$this->assertSame(['ds' => 1], $v1Meta['extensionVersions']);
		$this->assertSame(['ds' => 2], $v2Meta['extensionVersions']);
	}

	/**
	 * @return mixed[]
	 */
	private function getMeta(ExtensionVersionProvider $extensionVersionProvider): array
	{
		$container = self::getContainer();
		$manager = new ResultCacheManager(
			resultCacheMetaExtensions: $container->getExtensionsCollection(ResultCacheMetaExtension::class),
			exportedNodeFetcher: $container->getByType(ExportedNodeFetcher::class),
			scanFileFinder: $container->getService('fileFinderScan'),
			stubFilesProvider: $this->createStub(StubFilesProvider::class),
			fileHelper: $container->getByType(FileHelper::class),
			packageDependencyResolver: $container->getByType(PackageDependencyResolver::class),
			extensionVersionProvider: $extensionVersionProvider,
			cacheFilePath: '',
			analysedPaths: [],
			analysedPathsFromConfig: [],
			composerAutoloaderProjectPaths: [],
			usedLevel: '8',
			cliAutoloadFile: null,
			bootstrapFiles: [],
			scanFiles: [],
			scanDirectories: [],
			fileReplacements: [],
			checkDependenciesOfProjectExtensionFiles: false,
			parametersNotInvalidatingCache: [],
			skipResultCacheIfOlderThanDays: 7,
		);

		return $manager->restore([], true, false, null, $this->createStub(Output::class))->getMeta();
	}

}
