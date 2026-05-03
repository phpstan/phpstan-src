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
