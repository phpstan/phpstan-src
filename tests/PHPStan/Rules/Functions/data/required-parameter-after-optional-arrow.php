<?php // lint >= 8.0

namespace RequiredAfterOptional;

fn ($foo = null, $bar): int => 1; // not OK

fn (int $foo = null, $bar): int => 1; // is OK

fn (int $foo = 1, $bar): int => 1; // not OK

fn (bool $foo = true, $bar): int => 1; // not OK

fn (?int $foo = 1, $bar): int => 1; // not OK

fn (?int $foo = null, $bar): int => 1; // not OK

fn (int|null $foo = 1, $bar): int => 1; // not OK

fn (int|null $foo = null, $bar): int => 1; // not OK

fn (mixed $foo = 1, $bar): int => 1; // not OK

fn (mixed $foo = null, $bar): int => 1; // not OK

fn (int|null $foo = null, $bar, ?int $baz = null, $qux, int $quux = 1, $quuz): int => 1; // not OK
