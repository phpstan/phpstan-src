<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Testing\PHPStanTestCase;
use ReflectionClassConstant;
use function sprintf;

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

	public function testLeastRecentlyUsedNodeIsEvicted(): void
	{
		$limit = (new ReflectionClassConstant(PhpDocStringResolver::class, 'PARSED_PHPDOC_CACHE_LIMIT'))->getValue();
		$phpDocStringResolver = $this->createPhpDocStringResolver();

		$first = $phpDocStringResolver->resolve('/** @var int */');
		$second = $phpDocStringResolver->resolve('/** @var string */');
		for ($i = 2; $i < $limit; $i++) {
			$phpDocStringResolver->resolve(sprintf('/** @var %d */', $i));
		}

		// a hit makes the first node the most recently used, so the next miss evicts the second one
		$this->assertSame($first, $phpDocStringResolver->resolve('/** @var int */'));
		$phpDocStringResolver->resolve('/** @var float */');

		$this->assertSame($first, $phpDocStringResolver->resolve('/** @var int */'));
		$this->assertNotSame($second, $phpDocStringResolver->resolve('/** @var string */'));
	}

	private function createPhpDocStringResolver(): PhpDocStringResolver
	{
		$container = self::getContainer();

		return new PhpDocStringResolver(
			$container->getByType(Lexer::class),
			$container->getByType(PhpDocParser::class),
		);
	}

}
