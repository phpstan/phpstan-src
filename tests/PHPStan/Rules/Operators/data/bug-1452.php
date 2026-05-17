<?php

namespace BugRuleTest1452;

use DateTimeImmutable;

function doFoo(): void {
	$dateInterval = (new DateTimeImmutable('now -60 minutes'))->diff(new DateTimeImmutable('now'));
	$minutes = $dateInterval->format('%a') * 60;
}
