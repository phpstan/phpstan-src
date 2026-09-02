<?php

namespace MyApp;


/**
 * Note. The bug disappears if I remove this function or if I edit it in certain ways.
 *
 * @param ?int $quote_style
 * @param array{string, string}|bool|null ...$substitutions_list
 * @return ($string is null ? null : ($string is '' ? '' : ($string is non-empty-string ? non-empty-string : string)))
 */
function htmlentities2(string|int|float|\BackedEnum|null $string, ?int $quote_style = \ENT_QUOTES, array|bool|null ...$substitutions_list): string|null {
    if ($string instanceof \BackedEnum)
        $string = validate_string($string->value);
    if ($string === null)
        return null;
    $string = validate_string($string) ?? throw new \ValueError('Incompatible value');
    $sortie = \htmlspecialchars($string, ($quote_style ?? \ENT_QUOTES) | \ENT_HTML401 | \ENT_SUBSTITUTE, 'utf-8');
    foreach ($substitutions_list as $substitutions) {
        if ($substitutions) {
            if ($substitutions === true)
                $substitutions = [ [ "\n", "\r" ], [ '<br/>', '' ] ];
            $sortie = \str_replace($substitutions[0], $substitutions[1], $sortie);
        }
    }
    return $sortie;
}



/**
 * @template T
 * @param T $val
 * @return (T is array ? T : null)
 */
function validate_array(mixed $val): ?array {
    return \is_array($val) ? $val : null;
}


/**
 * @template T of mixed
 * @param T $val
 * @return ($val is string ? T&string : ($val is int ? decimal-int-string : ($val is float ? ?numeric-string : null)))
 */
function validate_string(mixed $val): ?string {
    if (\is_string($val)) {
        return $val;
    }
    elseif (\is_int($val)) {
        return (string) $val;
    }
    elseif (\is_float($val)) {
        return (string) $val;
    }
    return null;
}
