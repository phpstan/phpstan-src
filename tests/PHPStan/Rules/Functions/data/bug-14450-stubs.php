<?php

/**
 * Simulates vendor/jetbrains/phpstorm-stubs/standard/standard_1.php
 * where optional parameters lack default values.
 *
 * @param array|string $search
 * @param array|string $replace
 * @param array|string $subject
 * @param int $count
 * @return array|string
 */
function str_replace(array|string $search, array|string $replace, array|string $subject, &$count): array|string {}

/**
 * @return string
 */
function substr(string $string, int $offset, ?int $length): string {}
