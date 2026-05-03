<?php

namespace Bug5020;

interface ITransformer
{
	public static function Transform(string $theInput, bool &$theErrorEncountered): string;
}

class Transformer implements ITransformer
{
	public static function Transform(string $theInput, bool &$theErrorEncountered): string
	{
		if ($theInput === 'invalid') {
			$theErrorEncountered = true;
			return '';
		}
		return strtoupper(trim($theInput));
	}
}

/**
 * @param class-string<Transformer> $transformer
 */
function foo(string $transformer): void
{
	$input = ' asdasda asdasd ';
	$error = false;
	$output = $transformer::Transform($input, $error);
	if ($error) {

	}
}
