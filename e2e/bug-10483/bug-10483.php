<?php

define("FILTER_VALIDATE_FLOAT",false);

/** @return mixed */
function doFoo() { return null; }

$mixed = doFoo();
if (filter_var($mixed, FILTER_VALIDATE_BOOLEAN)) {
}
