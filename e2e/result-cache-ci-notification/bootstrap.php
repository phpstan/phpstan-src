<?php declare(strict_types = 1);

// Makes the analysis slow enough to cross
// AnalyseCommand::RESULT_CACHE_CI_NOTIFICATION_ELAPSED_LIMIT so that the e2e
// test can exercise the elapsed-time condition of the result cache CI
// notification. Only sleeps when the test asks for it, so that the same
// project can also be analysed fast.
if (getenv('PHPSTAN_E2E_SLOW_ANALYSIS') === '1') {
	sleep(61);
}
