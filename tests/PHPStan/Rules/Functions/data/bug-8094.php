<?php

namespace Bug8094;

function mask_encode($data, $mask)
{
	$mask = substr($mask, 0, strlen($data));
	$data ^= $mask;
	return(base64_encode($data));
}
