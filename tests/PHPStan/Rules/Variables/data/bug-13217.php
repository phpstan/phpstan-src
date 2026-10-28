<?php declare(strict_types = 1);

array_map(fn($_, $bValue) => $bValue ?? 1, [1, 2], [3]);
