<?php

namespace Bug9180;

$queue = new \SplQueue();

$queue->push(1);

if ($queue->count() > 0) {
	for ($i=0;$i<5;$i++) {
		while ($queue->count() > 0 && $value = $queue->shift()) {
			//do something with $value
		}
	}
}
