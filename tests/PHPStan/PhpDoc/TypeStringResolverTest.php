<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class TypeStringResolverTest extends PHPStanTestCase
{

	public function testConsumeEnd(): void
	{
		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);

		$this->expectException(ParserException::class);
		$this->expectExceptionMessage('Unexpected token "int", expected TOKEN_END at offset 7 on line 1');
		$typeStringResolver->resolve('string int');
	}

}
