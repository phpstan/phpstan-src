<?php
// error recovery frees a parenthesized arrow function; the next one can reuse
// its object handle and must still get the |> parenthesization error
if ((fn($x) => $x) { } $b = 1 |> fn($x) => $x;
