<?php

namespace Bug14198;

function say_HELLO() {
    /** @var string $name */
    static $name = "world";
    echo 'HELLO ', strtoupper($name), "\n";
    $name = [ ]; // <--- should report “Static variable $name (string) does not accept array{}”.
}

say_HELLO();
say_HELLO(); // <-- because a runtime error will occur here, and PHPStan doesn’t detect it
