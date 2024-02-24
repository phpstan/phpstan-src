<?php

namespace Bug10626;

function intByValue(int $value): void
{

}

function intByReference(int &$value): void
{

}

$notAnInt = 'not-an-int';
intByValue($notAnInt);
intByReference($notAnInt);
