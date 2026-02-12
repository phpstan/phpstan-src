<?php

namespace Bug5020;

use function PHPStan\Testing\assertType;

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

function testConstantStringStaticCall(): void
{
	$transformer = 'Bug5020\Transformer';
	$input = ' asdasda asdasd ';
	$error = false;
	$output = $transformer::Transform($input, $error);
	assertType('string', $output);
	assertType('bool', $error);
}

function testDirectStaticCall(): void
{
	$input = ' asdasda asdasd ';
	$error = false;
	$output = Transformer::Transform($input, $error);
	assertType('string', $output);
	assertType('bool', $error);
}

function testClassStringStaticCall(): void
{
	/** @var class-string<ITransformer> $transformer */
	$transformer = 'Bug5020\Transformer';
	$input = ' asdasda asdasd ';
	$error = false;
	$output = $transformer::Transform($input, $error);
	assertType('string', $output);
	assertType('bool', $error);
}
