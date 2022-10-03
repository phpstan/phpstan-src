<?php

namespace AssertUnresolvedGeneric;

/**
 * @phpstan-template T
 */
abstract class Enum
{
    /**
     * @phpstan-assert-if-true T $value
     * @return bool
     */
    public static function isValid($value)
    {
        return true;
    }
}

class Impl extends Enum
{
}

function (string $value) {
	if (Impl::isValid($value)) {
	}
};
