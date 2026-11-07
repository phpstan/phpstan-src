<?php

namespace GlobalConstantWildcardUnresolvable;

const FLAG_READ = 'read';

/**
 * @param PHP_ROUND_* $mode
 * @param FLAG_* $flag
 * @param NOPE_* $nope
 * @param \FLAG_* $globalFlag
 * @param Nope\FLAG_* $nopeFlag
 */
function doFoo(int $mode, string $flag, int $nope, string $globalFlag, string $nopeFlag): void
{
}
