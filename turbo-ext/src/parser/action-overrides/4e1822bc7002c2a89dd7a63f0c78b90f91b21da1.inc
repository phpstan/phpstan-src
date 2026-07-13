		/* list($class, $ctorArgs) = $self->semStack[...]; $$ = new Expr\New_($class, $ctorArgs, attributes); */
		zv::Ref pair = PN_SEM(2, 2);
		semValue = newNode("Expr\\New_", PN_ATTRS(2, 1, 2), itemAt(pair, 0), itemAt(pair, 1));
