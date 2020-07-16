<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Lexer;
use PHPStan\Php\PhpVersion;

class LexerFactory
{

	private PhpVersion $phpVersion;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function create(): Lexer
	{
		if ($this->phpVersion->getVersionId() <= PHP_VERSION_ID) {
			return new Lexer();
		}

		return new Lexer\Emulative();
	}

}
