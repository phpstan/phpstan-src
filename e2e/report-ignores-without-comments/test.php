<?php

// @phpstan-ignore variable.undefined
echo $undefined;

// @phpstan-ignore variable.undefined (this one has a comment so no error)
echo $anotherUndefined;

// @phpstan-ignore-next-line
echo $yetAnotherUndefined;

echo $yetYetAnotherUndefined; // @phpstan-ignore-line
