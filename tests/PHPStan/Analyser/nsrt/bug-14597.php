<?php

namespace Bug14597;

use function PHPStan\Testing\assertType;

function sscanfNulTerminator(string $s) {
	// NUL byte terminates sscanf format string – placeholders after \0 are ignored
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%d\0%d"));
	assertType('array{float|int|non-empty-string|null, float|int|non-empty-string|null}|null', sscanf($s, "%d %s\0%d"));
	assertType('array{}|null', sscanf($s, "\0%d%s"));
}

function fscanfNulTerminator($r) {
	// Same for fscanf
	assertType('array{float|int|non-empty-string|null}|null', fscanf($r, "%d\0%d"));
	assertType('array{float|int|non-empty-string|null, float|int|non-empty-string|null}|null', fscanf($r, "%d %s\0%d"));
	assertType('array{}|null', fscanf($r, "\0%d%s"));
}

function sscanfEdgeCases(string $s) {
	// Empty format string – no placeholders
	assertType('array{}|null', sscanf($s, ""));

	// %n – counts characters consumed, returns integer
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%n"));

	// %% – literal percent, not a placeholder
	assertType('array{}|null', sscanf($s, "%%"));

	// %i – integer with base detection
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%i"));

	// %X – uppercase hex, same as %x
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%X"));

	// %D – uppercase alias for %d
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%D"));

	// Size modifiers (l, L, h) – consumed by ValidateFormat, no effect on PHP type
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%ld"));
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%lf"));
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%Lf"));
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%hd"));
	assertType('array{float|int|non-empty-string|null}|null', sscanf($s, "%lu"));
	assertType('array{float|int|non-empty-string|null, float|int|non-empty-string|null, float|int|non-empty-string|null}|null', sscanf($s, "%ld %lf %s"));
}
