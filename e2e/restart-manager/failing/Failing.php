<?php declare(strict_types = 1);

function restartProbeFailingFunction(): void
{
	// an error every level reports, so the exit code assertion does not depend on the level
	thisFunctionDoesNotExist();
}
