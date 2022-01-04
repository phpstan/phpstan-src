<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Lexer;
use PHPStan\Php\PhpVersion;
use const PHP_VERSION_ID;

class LexerFactory
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function create(): Lexer
	{
		$options = ['usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos']];
		if ($this->phpVersion->getVersionId() === PHP_VERSION_ID) {
			return new Lexer($options);
		}

		$options['phpVersion'] = $this->phpVersion->getVersionString();

		return new Lexer\Emulative($options);
	}

}
