<?php declare(strict_types=1);

namespace BugSpaceship;

$arr = range(1, 16);
usort($arr, static fn (int $a, int $b): int => $a <=> $b);
