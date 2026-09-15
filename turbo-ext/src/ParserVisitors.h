/*
 * Shared substrate for the native PHPStan\Parser\*Visitor ports.
 *
 * Those visitors are tiny — a type check and, rarely, one setAttribute() —
 * but the node traverser calls them once per node per visitor, which made
 * visitor hooks 46.5% of all native->PHP crossings of a run. Each port keeps
 * its PHP_METHOD glue for direct userland calls and additionally registers a
 * pt_native_visitor entry (support.h), so NodeTraverser.cpp dispatches it in
 * C++ with no engine frame at all.
 *
 * The helpers here are the operations those visitor bodies share: reading a
 * subnode at a memoized property offset, php-parser's toLowerString()
 * comparison, and CallLike::isFirstClassCallable()/getArgs().
 */

#ifndef PHPSTANTURBO_PARSER_VISITORS_H
#define PHPSTANTURBO_PARSER_VISITORS_H

#include "support.h"
#include "zv.h"
#include "reg.h"

namespace phpstanturbo {
namespace visitors {

/*
 * Memoized offset of an instance property declared by one of the pt_class()
 * node classes — a monomorphic inline cache, one pointer compare once the
 * offset is resolved.
 *
 * The offset is resolved against the class ref's own entry rather than the
 * instance's class, which is what makes the memo safe: pt_class() re-resolves
 * per request, so a hit means the entry *is* this request's class, and a
 * subclass inherits the parent's slot at the same offset. Declared as an
 * aggregate so file-static instances are constant-initialised (no static
 * initialisation guard on the hot path).
 */
struct NodeProp
{
	int classIdx;
	const char *name;
	uint32_t nameLen;
	zend_class_entry *resolvedFor;
	int32_t offset;

	/* the property's zval on `node`, dereferenced; NULL when the class does
	 * not declare it */
	zval *of(zend_object *node)
	{
		zend_class_entry *ce = pt_class(classIdx);
		if (UNEXPECTED(ce == NULL)) return NULL;
		if (UNEXPECTED(resolvedFor != ce)) {
			resolvedFor = ce;
			offset = pt_instance_prop_offset(ce, name, nameLen);
		}
		if (UNEXPECTED(offset < 0)) return NULL;
		zval *slot = OBJ_PROP(node, (uint32_t) offset);
		ZVAL_DEREF(slot);
		return slot;
	}

	/* the property when it holds an object instance of pt_class(wantedIdx),
	 * NULL otherwise — the `$node->sub instanceof X` test the twins spell out */
	zend_object *objectOf(zend_object *node, int wantedIdx)
	{
		zval *value = of(node);
		if (value == NULL || Z_TYPE_P(value) != IS_OBJECT) return NULL;
		zend_class_entry *wanted = pt_class(wantedIdx);
		if (UNEXPECTED(wanted == NULL) || !instanceof_function(Z_OBJCE_P(value), wanted)) return NULL;
		return Z_OBJ_P(value);
	}
};

/* a file-static NodeProp initialiser: PT_NODE_PROP(PT_CLASS_FUNC_CALL, "args") */
#define PT_NODE_PROP(classIdx, literal) { (classIdx), (literal), (uint32_t) (sizeof(literal) - 1), NULL, -1 }

inline bool isInstanceOf(zend_object *node, int classIdx)
{
	zend_class_entry *ce = pt_class(classIdx);
	return ce != NULL && instanceof_function(node->ce, ce);
}

/*
 * Name::toLowerString() / Identifier::toLowerString() === $literal, without
 * materialising the lowercased copy. strtolower() is ASCII-only since PHP
 * 8.2, which is exactly this fold.
 */
inline bool lowerEquals(zend_string *s, const char *lower, size_t len)
{
	if (ZSTR_LEN(s) != len) return false;
	const char *p = ZSTR_VAL(s);
	for (size_t i = 0; i < len; i++) {
		char c = p[i];
		if (c >= 'A' && c <= 'Z') {
			c = (char) (c + ('a' - 'A'));
		}
		if (c != lower[i]) return false;
	}
	return true;
}

template<size_t N>
inline bool lowerEquals(zend_string *s, const char (&literal)[N])
{
	return lowerEquals(s, literal, N - 1);
}

/* the string value of a Name/Identifier subnode ($name->name), NULL when the
 * node is not one or the slot does not hold a string */
inline zend_string *nameString(zend_object *node, NodeProp &nameProp)
{
	zval *value = nameProp.of(node);
	if (value == NULL || Z_TYPE_P(value) != IS_STRING) return NULL;
	return Z_STR_P(value);
}

/*
 * CallLike::isFirstClassCallable(): exactly one raw argument, and it is a
 * VariadicPlaceholder. `rawArgs` is the call's own $args slot.
 */
inline bool isFirstClassCallable(zval *rawArgs)
{
	if (rawArgs == NULL || Z_TYPE_P(rawArgs) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(rawArgs)) != 1) return false;
	zend_class_entry *placeholder = pt_class(PT_CLASS_VARIADIC_PLACEHOLDER);
	if (UNEXPECTED(placeholder == NULL)) return false;
	/* current($rawArgs) — the first element of a freshly built list */
	for (auto entry : zv::ArrRef(rawArgs)) {
		zv::Ref value = entry.value().deref();
		return value.instanceOf(placeholder);
	}
	return false;
}

/* $args[$index] as an object, NULL when unset or not an object */
inline zend_object *argAt(zval *args, zend_ulong index)
{
	if (args == NULL || Z_TYPE_P(args) != IS_ARRAY) return NULL;
	zval *found = zend_hash_index_find(Z_ARRVAL_P(args), index);
	if (found == NULL) return NULL;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) == IS_OBJECT ? Z_OBJ_P(found) : NULL;
}

/* $node->setAttribute($name, true) */
inline void setAttributeTrue(zend_object *node, zend_string *name)
{
	zval value;
	ZVAL_TRUE(&value);
	pt_node_set_attribute(node, name, &value);
}

/* $node->setAttribute($name, $value) — the value is borrowed */
inline void setAttribute(zend_object *node, zend_string *name, zval *value)
{
	pt_node_set_attribute(node, name, value);
}

inline void setAttributeBool(zend_object *node, zend_string *name, bool value)
{
	zval zvalue;
	ZVAL_BOOL(&zvalue, value);
	pt_node_set_attribute(node, name, &zvalue);
}

inline void setAttributeLong(zend_object *node, zend_string *name, zend_long value)
{
	zval zvalue;
	ZVAL_LONG(&zvalue, value);
	pt_node_set_attribute(node, name, &zvalue);
}

/*
 * The visitor stacks ($typeStack, $traits) are plain lists only ever pushed
 * to and popped from the end, so push is a next-index insert and array_pop()
 * removes the element at count-1.
 */
inline void pushStack(zval *stack, zval *value)
{
	if (UNEXPECTED(Z_TYPE_P(stack) != IS_ARRAY)) return;
	SEPARATE_ARRAY(stack);
	Z_TRY_ADDREF_P(value);
	zend_hash_next_index_insert(Z_ARRVAL_P(stack), value);
}

inline void popStack(zval *stack)
{
	if (UNEXPECTED(Z_TYPE_P(stack) != IS_ARRAY)) return;
	uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(stack));
	if (count == 0) return;
	SEPARATE_ARRAY(stack);
	HashTable *table = Z_ARRVAL_P(stack);
	zend_hash_index_del(table, (zend_ulong) (count - 1));
	table->nNextFreeElement = (zend_long) (count - 1);
}

/*
 * The shape every port of a "reacts to one FuncCall by name" visitor has:
 * `$node instanceof FuncCall && $node->name instanceof Name &&
 * !$node->isFirstClassCallable()`, then the lowercased function name.
 * Returns NULL when the node is not such a call.
 */
struct FuncCallProps
{
	NodeProp name;
	NodeProp args;
};

#define PT_FUNC_CALL_PROPS { PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name"), PT_NODE_PROP(PT_CLASS_FUNC_CALL, "args") }

/* the Name::$name slot of any Name node */
#define PT_NAME_PROP PT_NODE_PROP(PT_CLASS_NAME, "name")
/* the Identifier::$name slot of any Identifier node */
#define PT_IDENTIFIER_PROP PT_NODE_PROP(PT_CLASS_IDENTIFIER, "name")

/*
 * The plain-function-call name of `node` ($node->name->toLowerString()) when
 * the node is a FuncCall with a Name callee that is not a first-class
 * callable, plus its $args slot; NULL otherwise.
 */
inline zend_string *plainFuncCallName(zend_object *node, FuncCallProps &props, NodeProp &nameProp, zval **argsOut)
{
	if (!isInstanceOf(node, PT_CLASS_FUNC_CALL)) return NULL;
	zend_object *callee = props.name.objectOf(node, PT_CLASS_NAME);
	if (callee == NULL) return NULL;
	zval *args = props.args.of(node);
	if (isFirstClassCallable(args)) return NULL;
	*argsOut = args;
	return nameString(callee, nameProp);
}

} // namespace visitors
} // namespace phpstanturbo

#endif
