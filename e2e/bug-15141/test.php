<?php

class Resource {}

function doFoo(Resource $r):void {}

$r = new Resource();
doFoo($r);

/** @param mixed $m */
function doBar($m):void {
    if (is_resource($m)) {
        doFoo($m);
    }
}
