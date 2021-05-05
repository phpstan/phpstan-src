<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\Type\Type;

class TypeStringResolver
{

	private Lexer $typeLexer;

	private TypeParser $typeParser;

	private TypeNodeResolver $typeNodeResolver;

	public function __construct(Lexer $typeLexer, TypeParser $typeParser, TypeNodeResolver $typeNodeResolver)
	{
		$this->typeLexer = $typeLexer;
		$this->typeParser = $typeParser;
		$this->typeNodeResolver = $typeNodeResolver;
	}

	public function resolve(string $typeString, ?NameScope $nameScope = null): Type
	{
		$tokens = new TokenIterator($this->typeLexer->tokenize($typeString));
		$typeNode = $this->typeParser->parse($tokens);
		$tokens->consumeTokenType(Lexer::TOKEN_END); // @phpstan-ignore-line

		return $this->typeNodeResolver->resolve($typeNode, $nameScope ?? new NameScope(null, []));
	}

}
