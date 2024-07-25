<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Parser\ConstExprParser;

final class ConstExprParserFactory
{

	public function __construct(private bool $unescapeStrings)
	{
	}

	public function create(): ConstExprParser
	{
		return new ConstExprParser($this->unescapeStrings, $this->unescapeStrings);
	}

}
