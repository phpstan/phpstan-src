<?php
// php-parser 5.9 rejects code points above \u{10FFFF} (5.8 accepted up to \u{1FFFFF})
$a = <<<EOT
\u{1FFFFF}
EOT;
