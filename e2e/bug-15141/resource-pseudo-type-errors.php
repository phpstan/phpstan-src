<?php

// Dropping the bogus native type must not drop the parameter check with it.

pg_numrows('not a resource');
pg_freeresult(42);
finfo_buffer(1, '');
