<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

class PhpDocStringResolver
{

	public function __construct(private Lexer $phpDocLexer, private PhpDocParser $phpDocParser)
	{
	}

	public function resolve(string $phpDocString): PhpDocNode
	{
		$tokens = new TokenIterator($this->phpDocLexer->tokenize($phpDocString));
		$phpDocNode = $this->phpDocParser->parse($tokens);
		$tokens->consumeTokenType(Lexer::TOKEN_END); // @phpstan-ignore missingType.checkedException

		return $phpDocNode;
	}

}
