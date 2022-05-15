<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;

class StubSourceLocatorFactory
{

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		private Parser $php8Parser,
		private PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		private OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		private Container $container,
		private array $stubFiles,
	)
	{
	}

	public function create(): SourceLocator
	{
		$locators = [];
		$astPhp8Locator = new Locator($this->php8Parser);
		$stubFiles = $this->stubFiles;
		$extensions = $this->container->getServicesByTag(StubFilesExtension::EXTENSION_TAG);
		foreach ($extensions as $extension) {
			foreach ($extension->getFiles() as $extensionFile) {
				$stubFiles[] = $extensionFile;
			}
		}

		foreach ($stubFiles as $stubFile) {
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($stubFile);
		}

		$locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
