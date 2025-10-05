<?php

namespace Bug11863;

$ips = ['0.0.0.0', '1.2.3.4', '10.0.0.1'];

$ips = array_filter(
	filter_var(
		$ips,
		FILTER_VALIDATE_IP,
		FILTER_REQUIRE_ARRAY
	)
);
