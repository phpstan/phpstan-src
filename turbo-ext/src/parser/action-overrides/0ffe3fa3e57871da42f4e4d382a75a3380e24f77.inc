		/* Error node created now to remember the position; later either reported or
		 * converted to a null element, depending on creation vs destructuring context.
		 * createEmptyElemAttributes() delegates to getAttributesForToken(); the SAME
		 * attrs array is shared between the Error node and the ArrayItem, as in PHP. */
		zv::Arr attrs = getAttributesForToken(tokenPos);
		zv::Val attrsForError = zv::Val::copyOf(attrs.ref());
		zv::Val err = newNode("Expr\\Error", std::move(attrsForError));
		semValue = newNode("Node\\ArrayItem", std::move(attrs), err, nullptr, zv::Val::boolean(false), zv::Val::boolean(false));
