<?php

if (!class_exists('NonexistentClassBug8579')) throw new \Exception('nonexistentclass');
$x = new \NonexistentClassBug8579();
