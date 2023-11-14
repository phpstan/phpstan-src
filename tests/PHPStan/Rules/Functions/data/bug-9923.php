<?php declare(strict_types=1); // lint >= 8.0

namespace Bug9923;

echo join(separator: ' ', array: ['a', 'b', 'c']);
echo implode(separator: ' ', array: ['a', 'b', 'c']);
