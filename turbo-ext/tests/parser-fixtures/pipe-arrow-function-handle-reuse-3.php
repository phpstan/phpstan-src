<?php
// error recovery frees a parenthesized arrow function; the next one can reuse
// its object handle and must still get the |> parenthesization error
$a = (fn($q) => $q) +;
$y |> fn() => 1;
