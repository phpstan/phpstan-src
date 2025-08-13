<?php

namespace Bug10312e;

class Cmsissue
{
	public const LANE_BACKLOG = 'Backlog';

	public const LANE_READY_FOR_IMPL = 'Bereit zur Entwicklung';

	public const LANE_IN_IMPL = 'In Entwicklung';

	public const LANE_READY_FOR_UAT = 'Bereit zur Abnahme';

	public const LANE_READY_FOR_UAT_KANBAN = 'Test';

	public const LANE_PROJECT_BACKLOG = 'Backlog';

	public const LANE_PROJECT_IN_IMPL = 'Projekt in Umsetzung';

	public const LANE_SKIP = '';
}


/**
 * @return Cmsissue::LANE_*
 */
$x = function(): string
{
	if (rand(0,1) === 0) {
		return Cmsissue::LANE_BACKLOG;
	}
	if (rand(0,1) === 0) {
		return Cmsissue::LANE_READY_FOR_IMPL;
	}
	if (rand(0,1) === 0) {
		return Cmsissue::LANE_IN_IMPL;
	}
	if (rand(0,1) === 0) {
		return Cmsissue::LANE_READY_FOR_UAT;
	}

	return Cmsissue::LANE_SKIP;
};
