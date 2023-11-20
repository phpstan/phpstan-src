<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\Type\Type;

class TypeStringResolver
{

	public function __construct(private Lexer $typeLexer, private TypeParser $typeParser, private TypeNodeResolver $typeNodeResolver)
	{
	}

	/** @api */
	public function resolve(string $typeString, ?NameScope $nameScope = null): Type
	{
		$tokens = new TokenIterator($this->typeLexer->tokenize($typeString));
		$typeNode = $this->typeParser->parse($tokens);
		$tokens->consumeTokenType(Lexer::TOKEN_END); // @phpstan-ignore missingType.checkedException

		return $this->typeNodeResolver->resolve($typeNode, $nameScope ?? new NameScope(null, []));
	}

}
