<?php

sscanf($str, '%d%d'); // correct - result returned as an array
sscanf($str, '%d', $number); // correct
sscanf($str, '%d%d', $number); // one parameter missing
sscanf($str, '%20[^,],%d', $string, $number); // ok
fscanf($resource, '%d%d'); // correct - result returned as an array
fscanf($resource, '%d', $number); // correct
fscanf($resource, '%d%d', $number); // one parameter missing

sscanf($str, "%20[^\n]\n%d", $string, $number); // ok
sscanf($str, "%20[^\n]\r\n%d", $string, $number); // ok
sscanf($str, "%20[^abcde]a%d", $string, $number); // ok

sscanf($str, '%.E', $number); // ok
fscanf($str, '%.E', $number); // ok
sscanf($str, '%[A-Z]%d', $char, $number); // ok
