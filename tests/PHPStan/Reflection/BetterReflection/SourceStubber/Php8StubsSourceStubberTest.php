<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPUnit\Framework\TestCase;

class Php8StubsSourceStubberTest extends TestCase
{

	public function testClass(): void
	{
		/** @var ClassReflector $classReflector */
		[$classReflector] = $this->getReflectors();
		$reflection = $classReflector->reflect(\Throwable::class);
		$this->assertSame(\Throwable::class, $reflection->getName());
	}

	public function testFunction(): void
	{
		/** @var FunctionReflector $functionReflector */
		[, $functionReflector] = $this->getReflectors();
		$reflection = $functionReflector->reflect('htmlspecialchars');
		$this->assertSame('htmlspecialchars', $reflection->getName());
	}

	/**
	 * @return array{ClassReflector, FunctionReflector}
	 */
	private function getReflectors(): array
	{
		// memoizing parser screws things up so we need to create the universe from the start
		$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
			'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
		]));
		/** @var FunctionReflector $functionReflector */
		$functionReflector = null;
		$astLocator = new Locator($parser, static function () use (&$functionReflector): FunctionReflector {
			return $functionReflector;
		});
		$sourceStubber = new Php8StubsSourceStubber();
		$phpInternalSourceLocator = new PhpInternalSourceLocator(
			$astLocator,
			$sourceStubber
		);
		$classReflector = new ClassReflector($phpInternalSourceLocator);
		$functionReflector = new FunctionReflector($phpInternalSourceLocator, $classReflector);

		return [$classReflector, $functionReflector];
	}

}
