<?php

namespace FilterVarAlwayTrue;

function doIni($mixed)
{
	$errors = [];
	if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
		$errors['allow_url_fopen'] = true;
	}
}

function mixIt($mixed)
{
	if (filter_var($mixed, FILTER_VALIDATE_EMAIL)) {
		if (filter_var($mixed, FILTER_VALIDATE_DOMAIN)) {
		}
	}
	if (filter_var($mixed, FILTER_VALIDATE_DOMAIN)) {
	}
}

