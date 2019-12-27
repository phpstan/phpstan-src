<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PhpParser\Parser;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BetterReflection\BetterReflectionProviderFactory;
use PHPStan\Reflection\ReflectionProvider;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;

class ReflectionProviderFactory
{

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	/** @var \PhpParser\Parser */
	private $parser;

	/** @var \PHPStan\Reflection\BetterReflection\BetterReflectionProviderFactory */
	private $betterReflectionProviderFactory;

	/** @var \PHPStan\Reflection\ReflectionProvider */
	private $phpParserReflectionProvider;

	/** @var bool */
	private $enableStaticReflectionForPhpParser;

	/** @var bool */
	private $enablePhpStormStubs;

	/** @var string[] */
	private $universalObjectCratesClasses;

	/**
	 * @param \PHPStan\Broker\Broker $broker
	 * @param \PhpParser\Parser $parser
	 * @param \PHPStan\Reflection\BetterReflection\BetterReflectionProviderFactory $betterReflectionProviderFactory
	 * @param \PHPStan\Reflection\ReflectionProvider $phpParserReflectionProvider
	 * @param bool $enableStaticReflectionForPhpParser
	 * @param bool $enablePhpStormStubs
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		Broker $broker,
		Parser $parser,
		BetterReflectionProviderFactory $betterReflectionProviderFactory,
		ReflectionProvider $phpParserReflectionProvider,
		bool $enableStaticReflectionForPhpParser,
		bool $enablePhpStormStubs,
		array $universalObjectCratesClasses
	)
	{
		$this->broker = $broker;
		$this->parser = $parser;
		$this->betterReflectionProviderFactory = $betterReflectionProviderFactory;
		$this->phpParserReflectionProvider = $phpParserReflectionProvider;
		$this->enableStaticReflectionForPhpParser = $enableStaticReflectionForPhpParser;
		$this->enablePhpStormStubs = $enablePhpStormStubs;
		$this->universalObjectCratesClasses = $universalObjectCratesClasses;
	}

	public function create(): ReflectionProvider
	{
		$providers = [];

		if ($this->enableStaticReflectionForPhpParser) {
			$providers[] = $this->phpParserReflectionProvider;
		}

		$providers[] = $this->broker;

		if ($this->enablePhpStormStubs) {
			$providers[] = $this->createPhpStormStubsReflectionProvider();
		}

		if (count($providers) === 1) {
			return $providers[0];
		}

		return new ChainReflectionProvider($providers, $this->universalObjectCratesClasses);
	}

	private function createPhpStormStubsReflectionProvider(): ReflectionProvider
	{
		/** @var FunctionReflector $functionReflector */
		$functionReflector = null;
		$astLocator = new Locator($this->parser, static function () use (&$functionReflector): FunctionReflector {
			return $functionReflector;
		});
		$sourceLocator = new PhpInternalSourceLocator(
			$astLocator,
			new PhpStormStubsSourceStubber($this->parser)
		);
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
