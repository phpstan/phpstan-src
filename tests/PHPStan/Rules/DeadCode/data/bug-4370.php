<?php

namespace Bug4370;

interface Lexer
{
	public function lex(): int;
}

/**
 * This class creates coverage data for the json files in the resources directory
 */
final class P
{
	/**
	 * The codes representing different JSON elements
	 *
	 * These come from the Seld\JsonLint\JsonParser class. The values are returned by the lexer when
	 * the lex() method is called.
	 */
	private const JSON_OBJECT_START = 17;
	private const JSON_OBJECT_END   = 18;

	/**
	 * Processes JSON object block that isn't needed for coverage data
	 */
	public function ignoreObjectBlock(Lexer $lexer): int
	{
		do {
			$code = $lexer->lex();

			// recursively ignore nested objects
			if ($code !== self::JSON_OBJECT_START) {
				continue;
			}

			$this->ignoreObjectBlock($lexer);
		} while ($code !== self::JSON_OBJECT_END);

		return $lexer->lex();
	}
}
