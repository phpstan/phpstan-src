<?php

// intentional in global namespace to make the class-name conflict with a samed named class from a PHP extension

class event {
	public static function add($_event, $_option = []) {
		// $_option est optionnel avec valeur par défaut
	}

	public static function callAdd() {
		self::add('a',[]); // Appel avec 2 paramètres (1 requis + 1 optionnel)
	}
}
