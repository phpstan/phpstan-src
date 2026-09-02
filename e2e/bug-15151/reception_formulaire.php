<?php

namespace MyApp;

class reception_formulaire {

    /** @var list<array{valeur: string, affichage: string}> */
    public array $liste_genre_pers = [ ];


    // @phpstan-ignore missingType.iterableValue (a phpstan error seems useful in order to trigger the bug)
    final function __construct(
        protected readonly array $params
    ) { }


    public function foo(): void {
        $liste_genre = [ ];
        foreach (validate_array($this->params['Genre_Admis'] ?? null) ?? [ ] as $_) {
            \PHPStan\dumpType($_); // ### should be: mixed, sometimes incorrectly: array
            if (in_array($_, [ 'Cli', 'Emp', 'Fou', 'Pct', 'Pre', 'Self' ], true))
                $liste_genre[] = $_;
        }
        $liste_genre_pers = [ ];
        foreach ($liste_genre as $ce_genre) {
            $liste_genre_pers[] = [ 'valeur' => $ce_genre, 'affichage' => '['.$ce_genre.']' ];
        }
        $this->liste_genre_pers = $liste_genre_pers;

    }


}
