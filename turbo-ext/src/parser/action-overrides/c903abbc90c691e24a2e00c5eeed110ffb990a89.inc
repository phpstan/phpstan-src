		/* $$ = $1; if ($$[count($$)-1]->value instanceof Expr\Error) array_pop($$); */
		semValue = PN_SEM(1, 1);
		uint32_t n = zv::ArrRef(semValue.raw()).size();
		if (n > 0) {
			zv::Ref last = itemAt(semValue.ref(), n - 1);
			if (last.raw() != NULL) {
				zv::Ref value = prop(last, "value");
				if (value.raw() != NULL && isInstanceOf(value, "Expr\\Error")) {
					phpArrayPop(semValue.raw());
				}
			}
		}
