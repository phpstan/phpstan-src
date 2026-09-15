<?php

namespace Bug15139;

$curl = curl_init();
assert(is_resource($curl));
$result = curl_exec($curl);
$curl_info = curl_getinfo($curl);
