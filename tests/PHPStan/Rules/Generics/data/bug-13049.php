<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13049;

/**
 * @template-covariant Value of string|list<string>
 *
 * @immutable
 */
final class LanguageProperty
{

    /** @var Value */
    public $value;

    /**
     * @param Value $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}

/**
 * @template-covariant Value of string|list<string>
 *
 * @immutable
 */
final class LanguageProperty2
{
    /**
     * @param Value $value
     */
    public function __construct(public $value)
    {
        $this->value = $value;
    }
}
