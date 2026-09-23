<?php
// error recovery frees a parenthesized arrow function; the next one can reuse
// its object handle and must still get the |> parenthesization error
f((fn() => 1) 2); 1 |> fn() => 1;
