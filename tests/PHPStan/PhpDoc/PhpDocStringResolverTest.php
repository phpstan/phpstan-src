<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Testing\PHPStanTestCase;

class PhpDocStringResolverTest extends PHPStanTestCase
{

	public function testConsumeEnd(): void
	{
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);

		$this->expectException(ParserException::class);
		$this->expectExceptionMessage('Unexpected token "test", expected TOKEN_END at offset 25 on line 1');
		$phpDocStringResolver->resolve('/** @param int $test */  test');
	}

	public function testEqualStringsShareOneNode(): void
	{
		$phpDocStringResolver = $this->createPhpDocStringResolver();

		$node = $phpDocStringResolver->resolve('/** @param int $test */');
		$this->assertSame($node, $phpDocStringResolver->resolve('/** @param int $test */'));
		$this->assertNotSame($node, $phpDocStringResolver->resolve('/** @param string $test */'));
	}

	private function createPhpDocStringResolver(): PhpDocStringResolver
	{
		$container = self::getContainer();

		return new PhpDocStringResolver(
			$container->getByType(Lexer::class),
			$container->getByType(PhpDocParser::class),
			1024,
		);
	}

}
