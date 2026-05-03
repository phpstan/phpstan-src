<?php

namespace Bug14567;

// NUL byte terminates sscanf/fscanf format string parsing
// Placeholders after \0 should not be counted

// Only 1 placeholder active before NUL
sscanf('123 456', "%d\0%d", $a);

// Only 1 placeholder active before NUL (fscanf)
fscanf(STDIN, "%d\0%d", $a2);

// No placeholders after NUL
sscanf('123', "\0%d");

// Multiple placeholders, NUL in middle
sscanf('123 456 789', "%d %d\0%d", $b, $c);

// %n specifier - counts characters consumed, 1 placeholder
sscanf('hello', "%n", $n);

// %% - literal percent, 0 placeholders
sscanf('100%', "100%%");

// %i specifier - integer with base detection, 1 placeholder
sscanf('0xff', "%i", $hex);

// Mixed with %n
sscanf('hello world', "%s%n", $word, $pos);

// %D specifier - uppercase alias for %d, 1 placeholder
sscanf('42', "%D", $dval);

// %g specifier - general float, 1 placeholder
sscanf('1.5', "%g", $gval);
