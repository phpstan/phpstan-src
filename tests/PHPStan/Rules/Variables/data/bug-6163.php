<?php

namespace Bug6163;

function (): void {
	$products = [];
	foreach (['123', 'abc'] as $id) {
		$products[$id] = true;
	}

	var_dump(isset($products['123']));
};
