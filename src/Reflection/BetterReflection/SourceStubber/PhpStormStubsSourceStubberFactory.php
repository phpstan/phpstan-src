<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;

class PhpStormStubsSourceStubberFactory
{

	public function __construct(private Parser $phpParser, private Printer $printer, private PhpVersion $phpVersion)
	{
	}

	public function create(): PhpStormStubsSourceStubber
	{
		return new PhpStormStubsSourceStubber($this->phpParser, $this->printer, $this->phpVersion->getVersionId());
	}

}
