<?php declare(strict_types = 1);

namespace App;

interface FatalErrorWhenAutoloaded
{

    public const AUTOLOAD = 'autoload';
    public const CLASS = 'error';

}
