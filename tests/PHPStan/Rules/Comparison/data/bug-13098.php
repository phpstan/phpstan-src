<?php

namespace Bug13098;

$x = substr($_GET["x"], 0, 10);
if ($x == "") echo "empty";

$x = get_include_path();
if ($x == "") echo "empty";

$x = password_hash("password", PASSWORD_DEFAULT);
if ($x == false) echo "false";

$x = php_sapi_name();
if ($x == false) echo "false";
