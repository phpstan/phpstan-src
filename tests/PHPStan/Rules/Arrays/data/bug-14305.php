<?php

namespace Bug14305;

define('BUG14305_XDOC_GMETA_EMTY', 0);
define('BUG14305_XDOC_GMETA_NUPATH', 7);
define('BUG14305_XDOC_GMETAS', [
    'empty'         => 0,
    'nupath'        => 7,
]);

$row = ['id' => 0];

foreach ([BUG14305_XDOC_GMETA_EMTY, BUG14305_XDOC_GMETA_NUPATH] as $meta)
	$row[array_search($meta, BUG14305_XDOC_GMETAS)] = '';
