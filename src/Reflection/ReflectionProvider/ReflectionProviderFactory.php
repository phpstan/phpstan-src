<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PhpParser\Parser;
use PHPStan\Reflection\BetterReflection\BetterReflectionProviderFactory;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\Runtime\RuntimeReflectionProvider;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;

class ReflectionProviderFactory
{

	/** @var \PHPStan\Reflection\Runtime\RuntimeReflectionProvider */
	private $runtimeReflectionProvider;

	/** @var \PhpParser\Parser */
	private $parser;

	/** @var PhpStormStubsSourceStubber */
	private $phpStormStubsSourceStubber;

	/** @var \PHPStan\Reflection\BetterReflection\BetterReflectionProviderFactory */
	private $betterReflectionProviderFactory;

	/** @var \PHPStan\Reflection\ReflectionProvider */
	private $phpParserReflectionProvider;

	/** @var bool */
	private $enableStaticReflectionForPhpParser;

	public function __construct(
		RuntimeReflectionProvider $runtimeReflectionProvider,
		Parser $parser,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		BetterReflectionProviderFactory $betterReflectionProviderFactory,
		ReflectionProvider $phpParserReflectionProvider,
		bool $enableStaticReflectionForPhpParser
	)
	{
		$this->runtimeReflectionProvider = $runtimeReflectionProvider;
		$this->parser = $parser;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->betterReflectionProviderFactory = $betterReflectionProviderFactory;
		$this->phpParserReflectionProvider = $phpParserReflectionProvider;
		$this->enableStaticReflectionForPhpParser = $enableStaticReflectionForPhpParser;
	}

	public function create(): ReflectionProvider
	{
		$providers = [];

		if ($this->enableStaticReflectionForPhpParser) {
			$providers[] = $this->phpParserReflectionProvider;
		}

		$providers[] = $this->runtimeReflectionProvider;
		$providers[] = $this->createPhpStormStubsReflectionProvider();

		return new ChainReflectionProvider($providers);
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
		$classReflector = new ClassReflector($sourceLocator);
		$functionReflector = new FunctionReflector($sourceLocator, $classReflector);
		$constantReflector = new ConstantReflector($sourceLocator, $classReflector);

		return $this->betterReflectionProviderFactory->create(
			$functionReflector,
			$classReflector,
			$constantReflector
		);
	}

}
