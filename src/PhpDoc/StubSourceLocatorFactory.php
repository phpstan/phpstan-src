<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use function dirname;

class StubSourceLocatorFactory
{

	public function __construct(
		private Parser $php8Parser,
		private PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		private OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		private OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory,
		private StubFilesProvider $stubFilesProvider,
	)
	{
	}

	public function create(): SourceLocator
	{
		$locators = [];
		$astPhp8Locator = new Locator($this->php8Parser);
		foreach ($this->stubFilesProvider->getStubFiles() as $stubFile) {
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($stubFile);
		}

		$locators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(
			Psr4Mapping::fromArrayMappings([
				'PHPStan\\' => [dirname(__DIR__) . '/'],
			]),
		);

		$locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
