<?php declare(strict_types = 1);

namespace Bug11533;

/** @param mixed[] $param */
function hello(array $param): void
{
    foreach (['need', 'field'] as $field) {
        if (!isset($param[$field]) || !is_string($param[$field])) {
            throw new \Exception();
        }
    }
    world($param);
}

/** @param array{need: string, field: string} $param */
function world(array $param): void
{
}
