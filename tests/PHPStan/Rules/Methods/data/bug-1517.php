<?php

declare(strict_types = 1);

$gen1 = static function () {
    yield true => true;
};

$gen2 = static function () {
    yield false => false;
};

$ait = new AppendIterator();

$ait->append($gen1());
$ait->append($gen2());
