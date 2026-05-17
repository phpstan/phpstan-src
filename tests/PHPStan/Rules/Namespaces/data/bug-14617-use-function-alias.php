<?php declare(strict_types = 1);

namespace Bug14617UseFunctionAliasNs;

function myFunction(): void {}

namespace Bug14617UseFunctionAliasNs\Consumer;

use function Bug14617UseFunctionAliasNs\myFunction as myfunction;
