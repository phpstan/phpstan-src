<?php declare(strict_types=1);

namespace Bug8376;

function (array $arr): array {
    return array_filter($arr, '\\is_int');
};

function ($var): void {
    \assert(\is_int($var));
};
