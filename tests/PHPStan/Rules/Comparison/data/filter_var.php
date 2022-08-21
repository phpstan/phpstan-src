<?php

namespace FilterVarAlwayTrue;

function doIni()
{
	$errors = [];
	if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
		$errors['allow_url_fopen'] = true;
	}
}

