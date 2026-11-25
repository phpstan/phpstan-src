<?php declare(strict_types = 1);

namespace Bug10368;

function f(string $s): void {
  $ptr = 0;
  if ($s[$ptr++] == ":") {
    if ($s[$ptr++] != ":") {
      print "hello\n";
    }
  }
}
f(":x");  // prints "hello"
