<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * Seam for the turbo extension's native php-parser engine: the native
 * implementation runs the LALR parse natively for PhpParser\Parser\Php8,
 * falling back to $parser->parse() for anything else. Without the extension
 * this delegation is all there is.
 */
#[ShadowedByTurboExtension(
	turboClass: 'PHPStanTurbo\ParserRunner',
	implementation: __DIR__ . '/../../turbo-ext/src/parser/ParserRunner.cpp',
)]
final class ParserRunner
{

	/**
	 * @return Stmt[]|null
	 */
	public static function parse(Parser $parser, string $sourceCode, ErrorHandler $errorHandler): ?array
	{
		return $parser->parse($sourceCode, $errorHandler);
	}

}
