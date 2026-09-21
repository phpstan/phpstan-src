<?php
// php-parser 5.9: \u{10FFFF} is the highest accepted code point
$a = "\u{10FFFF}";
$b = "\u{10FFFF} $x";
$c = `\u{10FFFF}$x`;
$d = <<<EOT
\u{10FFFF}
EOT;
$e = <<<EOT
\u{10FFFF} $x
EOT;
$f = "\u{0}\u{7F}\u{80}\u{7FF}\u{800}\u{FFFF}\u{10000}";
