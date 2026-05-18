<?php declare(strict_types = 1);

namespace Bug14617UseFunctionAlias;

function myFunction(): void {}
function anotherFunction(): void {}

namespace Bug14617UseFunctionAlias\Consumer;

use function Bug14617UseFunctionAlias\myFunction as myfunction;
use function Bug14617UseFunctionAlias\{anotherFunction as ANOTHERFUNCTION};

myfunction();
ANOTHERFUNCTION();
