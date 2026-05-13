<?php

namespace Bug14604;

class UserError extends \RuntimeException {}

function foo(): void
{
	/** @var array{from: string, to: string} $dates */
	$dates = ($_GET['dates'] ?? []) ?: throw new UserError('No Dates selected');
	if (empty($dates['from']) || empty($dates['to'])) {
		throw new UserError('Dates not selected');
	}

	/** @var array{latitude: string, longitude: string} $dates */
	$locations = ($_GET['location'] ?? []) ?: throw new UserError('No Location selected');
}
