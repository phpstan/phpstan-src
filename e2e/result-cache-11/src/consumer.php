<?php

// The inline @var below is this file's ONLY reference to TestResultCache11\Coll.
// The result cache has to record the dependency edge from it, otherwise a change
// to Coll's @extends signature does not re-analyse this file.

/** @var \TestResultCache11\Coll<\TestResultCache11\Item> $coll */
$coll = $GLOBALS['coll'];

foreach ($coll as $item) {
	echo $item->name();
}

