/*
 * The shared helpers of the native statement handlers (ExpressionHandler.cpp,
 * ReturnHandler.cpp, EchoHandler.cpp, BlockHandler.cpp, NopHandler.cpp,
 * ClassMethodHandler.cpp, FunctionHandler.cpp, ClassLikeHandler.cpp,
 * IfHandler.cpp): the AST-node property reads and class tests they all make,
 * and their calls into the declaration processors (AttributesHandler,
 * ParametersProcessor, PhpDocsResolver, DeprecatedAttributeResolver) through
 * their direct entries, one inline helper per called method.
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

/* {{{ the declaration processors (ClassMethodHandler, FunctionHandler,
 * ClassLikeHandler): AttributesHandler, PhpDocsResolver, ParametersProcessor,
 * DeprecatedAttributeResolver */

/* $attributesHandler->processAttributeGroups($nodeScopeResolver, $stmt,
 * $attrGroups, $scope, $storage, $nodeCallback) (AttributesHandler.cpp);
 * false = pending exception */
[[nodiscard]] inline bool processAttributeGroups(zval *attributesHandler, zval *nodeScopeResolver, zval *stmt, zval *attrGroups, zval *scope, zval *storage, zval *nodeCallback)
{
	return pt_attributes_handler_process_attribute_groups(attributesHandler, nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback);
}

/* list(...) = $phpDocsResolver->getPhpDocs($scope, $node), the list's
 * items (1 << index) in `destructured` (PhpDocsResolver.cpp); false = pending
 * exception */
[[nodiscard]] inline bool getPhpDocs(zval *phpDocsResolver, zval *scope, zval *node, uint32_t destructured, pt_php_docs &docs)
{
	return pt_php_docs_resolver_get_php_docs(phpDocsResolver, scope, node, destructured, docs);
}

/* $parametersProcessor->processParams($nodeScopeResolver, $stmt, $params,
 * $scope, $storage, $nodeCallback) (ParametersProcessor.cpp); false = pending
 * exception */
[[nodiscard]] inline bool processParams(zval *parametersProcessor, zval *nodeScopeResolver, zval *stmt, zval *params, zval *scope, zval *storage, zval *nodeCallback)
{
	return pt_parameters_processor_process_params(parametersProcessor, nodeScopeResolver, stmt, params, scope, storage, nodeCallback);
}

/* [$isDeprecated, $deprecatedDescription] =
 * $deprecatedAttributeResolver->getDeprecatedAttribute($scope, $stmt)
 * (DeprecatedAttributeResolver.cpp); false = pending exception */
[[nodiscard]] inline bool getDeprecatedAttribute(zval *deprecatedAttributeResolver, zval *scope, zval *stmt, zv::Val &isDeprecated, zv::Val &deprecatedDescription)
{
	return pt_deprecated_attribute_resolver_get_deprecated_attribute(deprecatedAttributeResolver, scope, stmt, isDeprecated, deprecatedDescription);
}

/* }}} */

/* the value a list() destructuring assigns from position `index` of a
 * value: the element (borrowed; the array must outlive its use), null with
 * the engine's "Undefined array key" warning for a missing key, null for a
 * non-array; NULL = the warning turned into an exception */
inline zval *listItem(zval *array, zend_ulong index)
{
	if (UNEXPECTED(Z_TYPE_P(array) != IS_ARRAY)) return &EG(uninitialized_zval);
	zval *value = zend_hash_index_find(Z_ARRVAL_P(array), index);
	if (UNEXPECTED(value == NULL)) {
		zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, index);
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	ZVAL_DEREF(value);
	return value;
}

/* $array[] = $value on a by-reference captured array (the IS_REFERENCE
 * capture of a native closure; the value borrowed) */
inline void appendToReference(zval *reference, zval *value)
{
	zval *array = Z_REFVAL_P(reference);
	if (UNEXPECTED(Z_TYPE_P(array) != IS_ARRAY)) {
		zval_ptr_dtor(array);
		array_init(array);
	}
	SEPARATE_ARRAY(array);
	Z_TRY_ADDREF_P(value);
	zend_hash_next_index_insert(Z_ARRVAL_P(array), value);
}

/* a fresh `$x = []` captured by reference: an IS_REFERENCE zval over the
 * empty array (owned) */
inline zv::Val newArrayReference()
{
	zval empty;
	ZVAL_EMPTY_ARRAY(&empty);
	zval reference;
	ZVAL_NEW_REF(&reference, &empty);
	return zv::Val::adopt(reference);
}

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
