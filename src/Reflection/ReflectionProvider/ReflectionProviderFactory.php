<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PhpParser\Parser;
use PHPStan\Reflection\BetterReflection\BetterReflectionProviderFactory;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingClassReflector;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingConstantReflector;
use PHPStan\Reflection\BetterReflection\Reflector\MemoizingFunctionReflector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\Runtime\RuntimeReflectionProvider;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;

class ReflectionProviderFactory
{

	private \PHPStan\Reflection\Runtime\RuntimeReflectionProvider $runtimeReflectionProvider;

	private \PhpParser\Parser $parser;

	private PhpStormStubsSourceStubber $phpStormStubsSourceStubber;

	private \PHPStan\Reflection\BetterReflection\BetterReflectionProviderFactory $betterReflectionProviderFactory;

	private \PHPStan\Reflection\ReflectionProvider $phpParserReflectionProvider;

	private bool $enableStaticReflectionForPhpParser;

	private bool $disableRuntimeReflectionProvider;

	public function __construct(
		RuntimeReflectionProvider $runtimeReflectionProvider,
		Parser $parser,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		BetterReflectionProviderFactory $betterReflectionProviderFactory,
		ReflectionProvider $phpParserReflectionProvider,
		bool $enableStaticReflectionForPhpParser,
		bool $disableRuntimeReflectionProvider
	)
	{
		$this->runtimeReflectionProvider = $runtimeReflectionProvider;
		$this->parser = $parser;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->betterReflectionProviderFactory = $betterReflectionProviderFactory;
		$this->phpParserReflectionProvider = $phpParserReflectionProvider;
		$this->enableStaticReflectionForPhpParser = $enableStaticReflectionForPhpParser;
		$this->disableRuntimeReflectionProvider = $disableRuntimeReflectionProvider;
	}

	public function create(): ReflectionProvider
	{
		$providers = [];

		if ($this->enableStaticReflectionForPhpParser) {
			$providers[] = $this->phpParserReflectionProvider;
		}

		if (!$this->disableRuntimeReflectionProvider) {
			$providers[] = $this->runtimeReflectionProvider;
		}

		$providers[] = $this->createPhpStormStubsReflectionProvider();

		return new MemoizingReflectionProvider(new ChainReflectionProvider($providers));
	}

	private function createPhpStormStubsReflectionProvider(): ReflectionProvider
	{
		/** @var FunctionReflector $functionReflector */
		$functionReflector = null;
		$astLocator = new Locator($this->parser, static function () use (&$functionReflector): FunctionReflector {
			return $functionReflector;
		});
		$sourceLocator = new MemoizingSourceLocator(new PhpInternalSourceLocator(
			$astLocator,
			$this->phpStormStubsSourceStubber
		));
		$classReflector = new MemoizingClassReflector($sourceLocator);
		$functionReflector = new MemoizingFunctionReflector($sourceLocator, $classReflector);
		$constantReflector = new MemoizingConstantReflector($sourceLocator, $classReflector);

		return $this->betterReflectionProviderFactory->create(
			$functionReflector,
			$classReflector,
			$constantReflector
		);
	}

}
