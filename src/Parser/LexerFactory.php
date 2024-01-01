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
		if ($this->phpVersion->getVersionId() === PHP_VERSION_ID) {
			return new Lexer();
		}

		return new Lexer\Emulative(\PhpParser\PhpVersion::fromString($this->phpVersion->getVersionString()));
	}

	public function createEmulative(): Lexer\Emulative
	{
		return new Lexer\Emulative();
	}

}
