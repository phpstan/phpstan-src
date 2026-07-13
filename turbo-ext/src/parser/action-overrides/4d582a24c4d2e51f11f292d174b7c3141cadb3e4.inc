		/* $$ = $self->parseNumString('-' . $2, attributes) */
		zend_string *cat = zend_string_concat2("-", 1,
			Z_STRVAL_P(PN_SEM(2, 2).raw()), Z_STRLEN_P(PN_SEM(2, 2).raw()));
		zv::Val tmp = zv::Val::adoptString(cat);
		semValue = parseNumString(tmp.ref(), PN_ATTRS(2, 1, 2));
