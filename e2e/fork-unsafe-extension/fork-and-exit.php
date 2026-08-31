<?php declare(strict_types = 1);

// A bare pcntl_fork() + exit() with the fork-unsafe extension loaded. The
// child wedges in the extension's module shutdown, so this script never
// finishes - the e2e job relies on that to prove the extension models the
// hang before testing that PHPStan's forked workers survive it.

if (pcntl_fork() === 0) {
	exit(0);
}

pcntl_wait($status);
echo "the forked child exited\n";
