<?php declare(strict_types = 1);

use RobotLoaderE2e\Discovered;

function (Discovered $discovered): int {
	return $discovered->doFoo();
};
