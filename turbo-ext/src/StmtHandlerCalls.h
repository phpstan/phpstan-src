/*
 * The shared helpers of the native statement handlers (ExpressionHandler.cpp,
 * ReturnHandler.cpp, EchoHandler.cpp, BlockHandler.cpp, NopHandler.cpp,
 * ClassMethodHandler.cpp, FunctionHandler.cpp, ClassLikeHandler.cpp,
 * IfHandler.cpp): the AST-node property reads and class tests they all make.
 * NodeScopeResolver and StatementsHandler are called through their direct
 * entries (support.h), the twins' try/finally through pt_finally()
 * (Engine.h).
 */

#ifndef PHPSTANTURBO_STMT_HANDLER_CALLS_H
#define PHPSTANTURBO_STMT_HANDLER_CALLS_H

#include "support.h"
#include "zv.h"
#include "Engine.h"

namespace ptsh {

/* a declared property of an AST node (dereferenced), NULL when the node's
 * class declares no such property (the caller raises the twin's error) */
inline zval *nodeProperty(pt_property_site &site, zval *node, const char *name, size_t len)
{
	zval *value = pt_property_cached(site, Z_OBJ_P(node), name, len);
	if (UNEXPECTED(value == NULL)) return NULL;
	ZVAL_DEREF(value);
	return value;
}

/* the twin's "Undefined property" read of a node class without it: a
 * warning and null, like the engine (NULL = the warning turned into an
 * exception) */
inline zend_never_inline ZEND_COLD zval *undefinedNodeProperty(zval *node, const char *name)
{
	zend_error(E_WARNING, "Undefined property: %s::$%s", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
	if (UNEXPECTED(EG(exception))) return NULL;
	return &EG(uninitialized_zval);
}

/* $node->name read (a declared property, or the twin's warning and null) */
inline zval *readNodeProperty(pt_property_site &site, zval *node, const char *name, size_t len)
{
	zval *value = nodeProperty(site, node, name, len);
	return EXPECTED(value != NULL) ? value : undefinedNodeProperty(node, name);
}

/* whether a value is an object of (a subclass of) the class-map class;
 * false with *error when the class map cannot resolve the class */
inline bool isInstanceOf(zval *value, int classIdx, bool &error)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) {
		error = true;
		return false;
	}
	return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
}

} // namespace ptsh

#endif /* PHPSTANTURBO_STMT_HANDLER_CALLS_H */
