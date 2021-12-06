<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Php\PhpVersion;

class PhpStormStubsSourceStubberFactory
{

	private Parser $phpParser;

	private PhpVersion $phpVersion;

	public function __construct(Parser $phpParser, PhpVersion $phpVersion)
	{
		$this->phpParser = $phpParser;
		$this->phpVersion = $phpVersion;
	}

	public function create(): PhpStormStubsSourceStubber
	{
		return new PhpStormStubsSourceStubber($this->phpParser, $this->phpVersion->getVersionId());
	}

}
