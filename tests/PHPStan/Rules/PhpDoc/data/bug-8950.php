<?php declare(strict_types=1);
namespace Bug8950;

define('FOO', 'foo');

use Bug8950\Interface\Bug8950Interface;

/** @var Bug8950Interface $foo */
$foo = null;


/** @var BooAlsoNotSubtype $boo */
$boo = null;

