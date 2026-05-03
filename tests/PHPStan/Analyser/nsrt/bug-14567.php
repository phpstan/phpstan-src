<?php

namespace Bug14567;

use function PHPStan\Testing\assertType;

function sscanfNulTerminator(string $s) {
	// NUL byte terminates sscanf format string - placeholders after \0 are ignored
	assertType('array{int|null}|null', sscanf($s, "%d\0%d"));
	assertType('array{int|null, string|null}|null', sscanf($s, "%d %s\0%d"));
	assertType('array{}|null', sscanf($s, "\0%d%s"));
}

function fscanfNulTerminator($r) {
	// Same for fscanf
	assertType('array{int|null}|null', fscanf($r, "%d\0%d"));
	assertType('array{int|null, string|null}|null', fscanf($r, "%d %s\0%d"));
	assertType('array{}|null', fscanf($r, "\0%d%s"));
}

function sscanfEdgeCases(string $s) {
	// Empty format string - no placeholders
	assertType('array{}|null', sscanf($s, ""));

	// %n - counts characters consumed, returns integer
	assertType('array{int|null}|null', sscanf($s, "%n"));

	// %% - literal percent, not a placeholder
	assertType('array{}|null', sscanf($s, "%%"));

	// %i - integer with base detection
	assertType('array{int|null}|null', sscanf($s, "%i"));

	// %X - uppercase hex, same as %x
	assertType('array{int|null}|null', sscanf($s, "%X"));

	// %D - uppercase alias for %d
	assertType('array{int|null}|null', sscanf($s, "%D"));

	// %g - general float
	assertType('array{float|null}|null', sscanf($s, "%g"));

	// mixed specifiers with %n
	assertType('array{int|null, int|null}|null', sscanf($s, "%d%n"));
}
