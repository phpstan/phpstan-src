<?php

namespace Randomizer;

$r = new \Random\Randomizer();
var_dump($r->pickArrayKeys(['a', 'b'], 10));
var_dump($r->pickArrayKeys(['a'], 0));
var_dump($r->pickArrayKeys([], 1));
