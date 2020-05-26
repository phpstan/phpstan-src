<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Parser\PhpParserDecorator;
use PHPStan\Testing\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use TestSingleFileSourceLocator\AFoo;

class AutoloadSourceLocatorTest extends TestCase
{

	public function testAutoloadEverythingInFile(): void
	{
		/** @var FunctionReflector $functionReflector */
		$functionReflector = null;
		$astLocator = new Locator(new PhpParserDecorator($this->getParser()), function () use (&$functionReflector): FunctionReflector {
			return $functionReflector;
		});
		$locator = new AutoloadSourceLocator($astLocator);
		$classReflector = new ClassReflector($locator);
		$functionReflector = new FunctionReflector($locator, $classReflector);
		$aFoo = $classReflector->reflect(AFoo::class);
		$this->assertSame('a.php', basename($aFoo->getFileName()));
	}

}
