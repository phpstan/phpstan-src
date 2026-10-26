<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex\Parser;

use Exception;

/** Thrown by RegexParser reducers when the token stream does not form a valid regex. */
final class RegexSyntaxException extends Exception
{

}
