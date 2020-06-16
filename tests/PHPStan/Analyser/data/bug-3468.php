<?php

namespace Bug3468;

class NewInterval extends \DateInterval
{
}

function (NewInterval $ni): void {
	$ni->f = 0.1;
};

class NewDocument extends \DOMDocument
{
}

function (NewDocument $nd): void {
	$element = $nd->documentElement;
};
