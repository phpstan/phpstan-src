<?php

namespace JsonDecodeParams;

function doFoo($m) {
	json_decode($m, null, 0, JSON_BIGINT_AS_STRING);
	json_decode($m, null, -10, JSON_OBJECT_AS_ARRAY);
	json_decode($m, null, 512, FILE_APPEND);
}
