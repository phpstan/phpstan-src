<?php declare(strict_types = 1);

namespace Bug12850;

$handle = fopen(__FILE__, 'r');
assert($handle !== false);

// exclusive group violation - LOCK_SH and LOCK_EX cannot be combined
flock($handle, LOCK_EX|LOCK_SH);

// ok - single lock type
flock($handle, LOCK_EX);
flock($handle, LOCK_SH);
flock($handle, LOCK_UN);

// ok - lock type with LOCK_NB modifier
flock($handle, LOCK_EX|LOCK_NB);
flock($handle, LOCK_SH|LOCK_NB);
