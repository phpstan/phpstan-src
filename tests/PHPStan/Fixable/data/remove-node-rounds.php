<?php

namespace RemoveNodeRounds;

function doFoo(int ...$args): void
{
}

// each argument is removed by its own error, the fixes overlap
doFoo(REMOVE_ARG, 1, REMOVE_ARG, REMOVE_ARG);
