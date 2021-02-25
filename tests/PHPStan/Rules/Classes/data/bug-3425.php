<?php declare(strict_types = 1);

namespace Bug3425;

new \RecursiveIteratorIterator((function() { yield 22; })());
