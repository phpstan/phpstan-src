<?php

namespace Bug7676;

$connection = pg_connect("", PGSQL_CONNECT_FORCE_NEW);
if ($connection === false) return;
pg_escape_literal($connection, "test");
