		/* $var = $1->name; $$ = \is_string($var) ? new VarLikeIdentifier($var, attributes) : $var; */
		zv::Ref var = prop(PN_SEM(1, 1), "name");
		if (var.raw() != NULL && var.isString()) {
			semValue = newNode("Node\\VarLikeIdentifier", PN_ATTRS(1, 1, 1), var);
		} else if (var.raw() != NULL) {
			semValue = var;
		} else {
			semValue = zv::Val::null();
		}
