<?php declare(strict_types = 1);

namespace Bug14473;

$link = mysqli_connect('host', 'user', 'pass', 'database') or die('Could not connect: ' . mysqli_connect_error());

// (assume long-running code that can cause the connection to time out)

$link = mysqli_connect('host', 'user', 'pass', 'database') or die('Could not reconnect: ' . mysqli_connect_error());

// okay, so let's make certain that the connection is closed
mysqli_close($link);

$link = mysqli_connect('host', 'user', 'pass', 'database') or die('Could not reconnect: ' . mysqli_connect_error());

// close it and destroy the variable
mysqli_close($link);
unset($link);

$link = mysqli_connect('host', 'user', 'pass', 'database') or die('Could not reconnect: ' . mysqli_connect_error());

// close, destroy ...
mysqli_close($link);
unset($link);

// ... and assign to a different variable
$newLink = mysqli_connect('host', 'user', 'pass', 'database') or die('Could not reconnect: ' . mysqli_connect_error());
