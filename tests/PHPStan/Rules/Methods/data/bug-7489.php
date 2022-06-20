<?php declare(strict_types = 1);

namespace Bug7489;

\Closure::bind(function () {
}, null, null)();
