<?php

// constraint from composer.json is overruled by phpstan.neon config
\PHPStan\Testing\assertType('int<80103, 80304>', PHP_VERSION_ID);
