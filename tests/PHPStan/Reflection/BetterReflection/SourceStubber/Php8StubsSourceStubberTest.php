<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPUnit\Framework\TestCase;

class Php8StubsSourceStubberTest extends TestCase
{

	public function testClass(): void
	{
		$reflection = $this->getReflector()->reflectClass(\Throwable::class);
		$this->assertSame(\Throwable::class, $reflection->getName());
	}

	public function testFunction(): void
	{
		$reflection = $this->getReflector()->reflectFunction('htmlspecialchars');
		$this->assertSame('htmlspecialchars', $reflection->getName());
	}

	private function getReflector(): Reflector
	{
		// memoizing parser screws things up so we need to create the universe from the start
		$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
			'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
		]));
		$astLocator = new Locator($parser);
		$sourceStubber = new Php8StubsSourceStubber();
		$phpInternalSourceLocator = new PhpInternalSourceLocator(
			$astLocator,
			$sourceStubber
		);
		return new DefaultReflector($phpInternalSourceLocator);
	}

}
