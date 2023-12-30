<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Lexer;
use PhpParser\Parser\Php7;
use PhpParser\Parser\Php8;
use PhpParser\ParserAbstract;
use PHPStan\Php\PhpVersion;

class PhpParserFactory
{

	public function __construct(private Lexer $lexer, private PhpVersion $phpVersion)
	{
	}

	public function create(): ParserAbstract
	{
		$phpVersion = \PhpParser\PhpVersion::fromString($this->phpVersion->getVersionString());
		if ($this->phpVersion->getVersionId() >= 80000) {
			return new Php8($this->lexer, $phpVersion);
		}

		return new Php7($this->lexer, $phpVersion);
	}

}
