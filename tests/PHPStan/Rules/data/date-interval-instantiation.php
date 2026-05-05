<?php
namespace DateIntervalInstantiation;

// invalid - missing P prefix
new \DateInterval('1M');

// valid durations
new \DateInterval('P1Y');
new \DateInterval('P1M');
new \DateInterval('P1D');
new \DateInterval('PT1H');
new \DateInterval('PT1M');
new \DateInterval('PT1S');
new \DateInterval('P1Y2M3DT4H5M6S');
new \DateInterval('P7D');

// invalid
new \DateInterval('asdfasdf');

// empty string is invalid
new \DateInterval('');

// non-constant string - should not report
function foo(string $duration): void {
	new \DateInterval($duration);
}

// constant string via variable
$test = '1M';
new \DateInterval($test);

/**
 * @param 'invalid' $duration2
 */
function bar(string $duration, string $duration2): void {
	new \DateInterval($duration);
	new \DateInterval($duration2);
}
