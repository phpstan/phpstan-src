<?php

namespace Bug10260;

// %* in scanf means "assignment suppression" - match but don't store
// These should NOT count as placeholders

// From the issue: %*[a-z] matches lowercase letters but doesn't assign
sscanf('appletone_day_1', '%*[a-z]_day_%s', $day_number);

// %*d means match an integer but don't assign it
sscanf('123 456', '%*d %d', $number);

// Multiple suppressed placeholders
sscanf('foo 123 bar', '%*s %*d %s', $str);

// Suppressed with character class
sscanf('ABC123', '%*[A-Z]%d', $num);

// Mix of suppressed and non-suppressed
sscanf('hello world 42', '%s %*s %d', $word, $num2);

// fscanf with suppression
fscanf(STDIN, '%*d %s', $value);

// No values needed when all are suppressed (returned as array)
sscanf('123 abc', '%*d %*s');
