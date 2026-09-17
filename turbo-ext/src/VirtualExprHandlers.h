/*
 * What the ExprHandler\Virtual\* ports (TypeExprHandler.cpp,
 * NativeTypeExprHandler.cpp, UnsetOffsetExprHandler.cpp, ...) and
 * VirtualExprResultHelper.cpp share: the reads of the virtual nodes they
 * handle, the `fn (TypeSpecifierContext $context, bool $nativeTypesPromoted)
 * => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context)`
 * specifyTypesCallback most of them create and the sub-result reads.
 *
 * The virtual node classes (PHPStan\Node\Expr\TypeExpr, ...) stay PHP: they
 * are final and their getters return private promoted properties, so an
 * instance of exactly the class-map class is read in its slot; any other
 * object gets the getter by name (the twin's own Error for a foreign node).
 */

#ifndef PHPSTANTURBO_VIRTUAL_EXPR_HANDLERS_H
#define PHPSTANTURBO_VIRTUAL_EXPR_HANDLERS_H

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "ParserVisitors.h"

namespace ptveh {

using phpstanturbo::visitors::NodeProp;

/* {{{ the virtual nodes' properties */

inline NodeProp typeExprExprType = PT_NODE_PROP(PT_CLASS_TYPE_EXPR, "exprType");
inline NodeProp nativeTypeExprPhpdocType = PT_NODE_PROP(PT_CLASS_NATIVE_TYPE_EXPR, "phpdocType");
inline NodeProp nativeTypeExprNativeType = PT_NODE_PROP(PT_CLASS_NATIVE_TYPE_EXPR, "nativeType");
inline NodeProp unsetOffsetExprVar = PT_NODE_PROP(PT_CLASS_UNSET_OFFSET_EXPR, "var");
inline NodeProp unsetOffsetExprDim = PT_NODE_PROP(PT_CLASS_UNSET_OFFSET_EXPR, "dim");
inline NodeProp alwaysRememberedExprExpr = PT_NODE_PROP(PT_CLASS_ALWAYS_REMEMBERED_EXPR, "expr");
inline NodeProp alwaysRememberedExprType = PT_NODE_PROP(PT_CLASS_ALWAYS_REMEMBERED_EXPR, "type");
inline NodeProp alwaysRememberedExprNativeType = PT_NODE_PROP(PT_CLASS_ALWAYS_REMEMBERED_EXPR, "nativeType");
inline NodeProp existingArrayDimFetchVar = PT_NODE_PROP(PT_CLASS_EXISTING_ARRAY_DIM_FETCH, "var");
inline NodeProp existingArrayDimFetchDim = PT_NODE_PROP(PT_CLASS_EXISTING_ARRAY_DIM_FETCH, "dim");
inline NodeProp issetExprExpr = PT_NODE_PROP(PT_CLASS_ISSET_EXPR, "expr");
inline NodeProp possiblyImpureCallExprCallExpr = PT_NODE_PROP(PT_CLASS_POSSIBLY_IMPURE_CALL_EXPR, "callExpr");
inline NodeProp setOffsetValueTypeExprVar = PT_NODE_PROP(PT_CLASS_SET_OFFSET_VALUE_TYPE_EXPR, "var");
inline NodeProp setOffsetValueTypeExprDim = PT_NODE_PROP(PT_CLASS_SET_OFFSET_VALUE_TYPE_EXPR, "dim");
inline NodeProp setOffsetValueTypeExprValue = PT_NODE_PROP(PT_CLASS_SET_OFFSET_VALUE_TYPE_EXPR, "value");
inline NodeProp setExistingOffsetValueTypeExprVar = PT_NODE_PROP(PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR, "var");
inline NodeProp setExistingOffsetValueTypeExprDim = PT_NODE_PROP(PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR, "dim");
inline NodeProp setExistingOffsetValueTypeExprValue = PT_NODE_PROP(PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR, "value");

/* $node->getter() of a virtual node (an object) whose final class returns
 * the private property: the slot (dereferenced, borrowed) for an instance of
 * exactly the property's class-map class, the getter by name otherwise (the
 * twin's Error for a foreign node), its result kept alive in hold; NULL =
 * pending exception */
inline zval *getterRead(NodeProp &prop, zval *node, const char *lcname, size_t len, zv::Val &hold)
{
	zend_class_entry *ce = pt_class(prop.classIdx);
	if (UNEXPECTED(ce == NULL)) return NULL;
	if (EXPECTED(Z_OBJCE_P(node) == ce)) {
		zval *value = prop.of(Z_OBJ_P(node));
		if (EXPECTED(value != NULL && Z_TYPE_P(value) != IS_UNDEF)) return value;
	}
	hold = pt_type_call(Z_OBJ_P(node), lcname, len, 0, NULL);
	return hold.isUndef() ? NULL : hold.raw();
}

/* $node->$name of a public property of a virtual node (an object), read
 * from the handler's class scope: the slot (dereferenced, borrowed) for an
 * instance of exactly the property's class-map class, the engine's property
 * read otherwise (its warning and null for an undefined property), kept
 * alive in hold; NULL = pending exception */
inline zval *publicRead(NodeProp &prop, zval *node, zend_class_entry *scope, zv::Val &hold)
{
	zend_class_entry *ce = pt_class(prop.classIdx);
	if (UNEXPECTED(ce == NULL)) return NULL;
	if (EXPECTED(Z_OBJCE_P(node) == ce)) {
		zval *value = prop.of(Z_OBJ_P(node));
		if (EXPECTED(value != NULL && Z_TYPE_P(value) != IS_UNDEF)) return value;
	}
	zval rv;
	zval *value = zend_read_property(scope, Z_OBJ_P(node), prop.name, prop.nameLen, false, &rv);
	if (UNEXPECTED(EG(exception) != NULL)) return NULL;
	hold = zv::Val::copyOf(zv::Ref(value).deref());
	if (value == &rv) zval_ptr_dtor(&rv);
	return hold.raw();
}

/* whether a value is an object of the class-map class; -1 = pending
 * exception (the class map failing) */
inline int isInstance(zval *value, int classIdx)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return -1;
	return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce) ? 1 : 0;
}

/* the supports() body: $expr instanceof <class-map class>; false = pending
 * exception */
[[nodiscard]] inline bool supportsInstance(zval *expr, int classIdx, bool &out)
{
	int is = isInstance(expr, classIdx);
	if (UNEXPECTED(is < 0)) return false;
	out = is == 1;
	return true;
}

/* }}} */

/* {{{ closures and small value helpers */

/* the closure's `Too few arguments` ArgumentCountError; false = thrown */
[[nodiscard]] inline bool requireArgs(uint32_t argc, uint32_t expected, const char *function)
{
	if (EXPECTED(argc >= expected)) return true;
	zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function %s(), %u passed and exactly %u expected", function, argc, expected);
	return false;
}

/* the body of `fn (TypeSpecifierContext $context, bool $nativeTypesPromoted)
 * => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context)` —
 * captures: $this, $expr; HelperSlot is the handler's defaultNarrowingHelper
 * slot, ClosureName the twin's `<Class>::{closure}` (a static constexpr
 * char array) for the engine's messages */
template <uint32_t HelperSlot, const char ClosureName[]>
void specifyDefaultTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(!requireArgs(argc, 2, ClosureName))) return;
	zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), HelperSlot), &captures[1], &argv[0]);
	if (UNEXPECTED(specifiedTypes.isUndef())) return;
	specifiedTypes.intoReturnValue(return_value);
}

/* $nativeTypesPromoted ? $result->getNativeType() : $result->getType() */
inline zv::Val resultType(zval *result, bool nativeTypesPromoted)
{
	return nativeTypesPromoted ? pt_expression_result_get_native_type(result) : pt_expression_result_get_type(result);
}

/* $nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope,
 * the promoted scope kept alive in hold; NULL = pending exception */
inline zval *promotedScope(zval *scope, bool nativeTypesPromoted, zv::Val &hold)
{
	if (!nativeTypesPromoted) return scope;
	hold = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
	return hold.isUndef() ? NULL : hold.raw();
}

/* }}} */

} // namespace ptveh

#endif
