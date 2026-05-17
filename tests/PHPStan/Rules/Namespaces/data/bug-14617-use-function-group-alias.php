<?php declare(strict_types = 1);

namespace Bug14617UseFunctionGroupAliasNs;

function myFunction(): void {}
function anotherFunction(): void {}

namespace Bug14617UseFunctionGroupAliasNs\Consumer;

use function Bug14617UseFunctionGroupAliasNs\{myFunction as myfunction, anotherFunction as ANOTHERFUNCTION};
