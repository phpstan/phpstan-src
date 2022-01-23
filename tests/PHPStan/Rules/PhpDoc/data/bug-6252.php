#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace foo;

class bar {}

namespace abc;

use foo\bar;

/** @var bar $x */
$x = new bar();
