<?php declare(strict_types = 1);

namespace Bug14519;

$entry = apcu_fetch("key1");

if ($entry === false) {
	die();
}

// do something when $entry exists
sleep(1);

// re-fetch key "key1" again (it probably has been changed or deleted in the meantime by another process)
$entry = apcu_fetch("key1");

// phpstan complains here, but it shouldn't
if ($entry === false) {
	die();
}
