<?php declare(strict_types = 1);

$null = null;

/** @var int|null $a */
$a = $null;

/** @var int|null $a */
$a = null;

/** @var int|null $a */
$a = 1;

/** @var mixed[]|null $a */
$a = [];

/** @var array<string, int> $a */
$a = [];

/** @var array{string, int} $a */
$a = [];

/** @var int $a */
$a = [];

$translationsTree = [];

/** @var array $byRef */
$byRef = &$translationsTree;
