<?php

define("FILTER_VALIDATE_FLOAT",false);

$mixed = doFoo();
if (filter_var($mixed, FILTER_VALIDATE_BOOLEAN)) {
}
