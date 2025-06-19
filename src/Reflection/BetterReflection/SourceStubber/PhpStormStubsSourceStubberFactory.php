<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;

#[AutowiredService]
final class PhpStormStubsSourceStubberFactory
{

	public function __construct(
		#[AutowiredParameter(ref: '@php8PhpParser')]
		private Parser $phpParser,
		private Printer $printer,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function create(): PhpStormStubsSourceStubber
	{
		return new PhpStormStubsSourceStubber($this->phpParser, $this->printer, $this->phpVersion->getVersionId());
	}

}
