<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class PhpDocStringResolverTest extends PHPStanTestCase
{

	public function testConsumeEnd(): void
	{
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);

		$this->expectException(ParserException::class);
		$this->expectExceptionMessage('Unexpected token "test", expected TOKEN_END at offset 25 on line 1');
		$phpDocStringResolver->resolve('/** @param int $test */  test');
	}

}
