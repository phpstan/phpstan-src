<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Php\PhpVersion;
use Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;

class PhpStormStubsSourceStubberFactory
{

	/** @var PhpVersion */
	private $phpVersion;

	/** @var PhpStormStubsSourceStubber */
	private $phpStormStubsSourceStubber;

	/** @var ReflectionSourceStubber */
	private $reflectionSourceStubber;

	public function __construct(
		PhpVersion $phpVersion,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		ReflectionSourceStubber $reflectionSourceStubber
	)
	{
		$this->phpVersion = $phpVersion;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->reflectionSourceStubber = $reflectionSourceStubber;
	}

	public function create(): SourceStubber
	{
		if (!$this->phpVersion->supportsNativeUnionTypes()) {
			return $this->phpStormStubsSourceStubber;
		}

		return new AggregateSourceStubber($this->reflectionSourceStubber, $this->phpStormStubsSourceStubber);
	}

}
