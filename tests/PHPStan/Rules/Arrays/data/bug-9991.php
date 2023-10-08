<?php

namespace Bug9991;

function (): void {
	$data = json_decode(file_get_contents('') ?: '', flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);

	if (
		isset($data['title'])
		&& is_string($data['title'])
	) {
		echo $data['title'];
	}
};
