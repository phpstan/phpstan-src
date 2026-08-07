<?php

namespace consumer12972c;

use shared12972c\Thing;

function run(Thing $thing): int
{
	return $thing->prependedOnly();
}
