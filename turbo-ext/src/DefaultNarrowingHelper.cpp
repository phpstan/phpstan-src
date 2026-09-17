/*
 * PHPStanTurbo\DefaultNarrowingHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it (and pairs the #[AutowiredParameter] by
 * name). Every public method is exported as a pt_default_narrowing_helper_*
 * direct entry (support.h) for the native handlers; the class is final, so
 * the entries take the native body for exactly the native class entry and
 * call the method by name on anything else (the PHP twin in the prefixed
 * differential tests).
 *
 * The twin's closures are native closures: buildChainTypeReader()'s reader
 * (handed out as a real \Closure — its return type demands one), the two
 * `static fn (): bool => true` issetability callbacks, and the two
 * TypeTraverser::map() callbacks of specifyTypesFromAsserts() — the asserted
 * type's callback captures the scope instead of the twin's $getArgType
 * closure over it (the closure is only ever called from that callback, so
 * its body runs directly and one allocation per call is saved); the template
 * callback keeps the twin's `use (&$containsUnresolvedTemplate)` reference.
 *
 * SpecifiedTypes, TypeSpecifierContext, ExpressionResult, MutatingScope,
 * ExpressionResultStorage, ExprPrinter, TypeCombinator, TypeTraverser,
 * StaticTypeFactory and the Type classes are called through their direct
 * entries / ops, IssetabilityResolution and IssetabilityLinkInfo through
 * their native entries and readers; the collaborators that stay PHP for now
 * (ImpurePoint, NullsafeOperatorHelper,
 * AllowedArrayKeysTypes, the reflection provider, AssertTag and the
 * parameters acceptors) through the cached method sites in the block below,
 * one helper each.
 *
 * captureChainResults() walks the chain with an explicit stack instead of the
 * twin's recursion, in the same pre-order, so a deep chain costs heap, not C
 * stack.
 */

#include "support.h"
#include "generated/DefaultNarrowingHelper.h"

namespace slots = ptdecl::DefaultNarrowingHelper::slot;
namespace sigs = ptdecl::DefaultNarrowingHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "ParameterValues.h"
#include "AnalyserValues.h"

#include "zend_closures.h" /* zend_ce_closure */

#include <algorithm>

zend_class_entry *pt_ce_default_narrowing_helper = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_dnh_nullsafe_shortcircuited_site;
pt_method_site pt_dnh_narrow_offset_key_type_site;
pt_method_site pt_dnh_get_asserts_site;
pt_method_site pt_dnh_get_asserts_if_true_site;
pt_method_site pt_dnh_get_asserts_if_false_site;
pt_method_site pt_dnh_acceptor_get_parameters_site;
pt_method_site pt_dnh_acceptor_is_variadic_site;
pt_method_site pt_dnh_acceptor_get_resolved_template_type_map_site;
pt_method_site pt_dnh_acceptor_get_original_parameters_acceptor_site;
pt_method_site pt_dnh_original_acceptor_get_return_type_site;
pt_method_site pt_dnh_assert_get_parameter_site;
pt_method_site pt_dnh_assert_get_type_site;
pt_method_site pt_dnh_assert_get_original_type_site;
pt_method_site pt_dnh_assert_is_negated_site;
pt_method_site pt_dnh_assert_is_equality_site;
pt_method_site pt_dnh_assert_parameter_get_parameter_name_site;
pt_method_site pt_dnh_assert_parameter_get_expr_site;
pt_method_site pt_dnh_function_has_side_effects_site;

/* the Error PHP raises for a method call on a non-object */
zv::Val callOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
	return zv::Val();
}

/* $impurePoint->getNode() (borrowed, held in hold); NULL = pending exception */
zval *impurePointGetNode(zval *impurePoint, zv::Val &hold)
{
	if (UNEXPECTED(Z_TYPE_P(impurePoint) != IS_OBJECT)) {
		(void) callOnNonObject("getNode", impurePoint);
		return NULL;
	}
	return pt_impure_point_node(impurePoint, hold);
}

/* $impurePoint->isCertain(); false = pending exception */
[[nodiscard]] bool impurePointIsCertain(zval *impurePoint, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(impurePoint) != IS_OBJECT)) return !callOnNonObject("isCertain", impurePoint).isUndef();
	return pt_impure_point_is_certain(impurePoint, out);
}

/* NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($expr) */
zv::Val getNullsafeShortcircuitedExpr(zval *expr)
{
	return pt_call_static_cached(pt_dnh_nullsafe_shortcircuited_site, PT_CLASS_NULLSAFE_OPERATOR_HELPER, PT_LC("getnullsafeshortcircuitedexpr"), 1, expr);
}

/* AllowedArrayKeysTypes::narrowOffsetKeyType($varType, $dimType) */
zv::Val narrowOffsetKeyType(zval *varType, zval *dimType)
{
	zv::Args argv{varType, dimType};
	return pt_call_static_cached(pt_dnh_narrow_offset_key_type_site, PT_CLASS_ALLOWED_ARRAY_KEYS_TYPES, PT_LC("narrowoffsetkeytype"), 2, argv);
}

/* $resolution->isSet($typeCallback) (IssetabilityResolution.cpp); the ?bool
 * answer */
zv::Val resolutionIsSet(zval *resolution, zval *typeCallback)
{
	if (UNEXPECTED(Z_TYPE_P(resolution) != IS_OBJECT)) return callOnNonObject("isSet", resolution);
	return pt_issetability_resolution_is_set(resolution, typeCallback);
}

/* a borrowed AnalyserValues.h read as an owned value; UNDEF = pending
 * exception */
zv::Val ownedRead(zval *value, zv::Val &hold)
{
	if (UNEXPECTED(value == NULL)) return zv::Val();
	if (!hold.isUndef()) return std::move(hold);
	return zv::Val::copyOf(zv::Ref(value));
}

/* a no-argument method of a PHP collaborator through its site */
zv::Val callNoArgs(pt_method_site &site, zval *object, const char *method, const char *lcname, size_t len)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) return callOnNonObject(method, object);
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, 0, NULL);
}

/* the same for a bool-returning method; false = pending exception */
[[nodiscard]] bool callNoArgsBool(pt_method_site &site, zval *object, const char *method, const char *lcname, size_t len, bool &out)
{
	zv::Val value = callNoArgs(site, object, method, lcname, len);
	if (UNEXPECTED(value.isUndef())) return false;
	out = zend_is_true(value.raw());
	return true;
}

/* $reflectionProvider->hasFunction($name, $scope) (the memoized answer,
 * FunctionReflectionAccess.cpp); false = pending exception */
[[nodiscard]] bool reflectionProviderHasFunction(zval *reflectionProvider, zval *name, zval *scope, bool &out)
{
	return pt_reflection_provider_has_function(reflectionProvider, name, scope, out);
}

/* $reflectionProvider->getFunction($name, $scope) (the memoized reflection) */
zv::Val reflectionProviderGetFunction(zval *reflectionProvider, zval *name, zval *scope)
{
	return pt_reflection_provider_get_function(reflectionProvider, name, scope);
}

/* }}} */

/* {{{ node shapes and properties */

pt_property_site pt_dnh_var_site;
pt_property_site pt_dnh_dim_site;
pt_property_site pt_dnh_class_site;
pt_property_site pt_dnh_name_site;
pt_property_site pt_dnh_identifier_name_site;
pt_property_site pt_dnh_args_site;
pt_property_site pt_dnh_arg_unpack_site;
pt_property_site pt_dnh_arg_name_site;
pt_property_site pt_dnh_arg_value_site;

/* $value instanceof <class-map class> — the php-parser and PHPStan node
 * classes the keys name always resolve (a failure leaves its exception
 * pending and answers false) */
inline bool isA(zval *value, int classIdx)
{
	if (Z_TYPE_P(value) != IS_OBJECT) return false;
	zend_class_entry *ce = pt_class(classIdx);
	return ce != NULL && instanceof_function(Z_OBJCE_P(value), ce);
}

/* a declared property of a node (dereferenced) through a per-site offset;
 * an undefined zval when the class declares no such property */
zval *nodeProp(pt_property_site &site, zval *node, const char *name, size_t len)
{
	static zval undefined;
	zval *slot = pt_property_cached(site, Z_OBJ_P(node), name, len);
	if (UNEXPECTED(slot == NULL)) {
		ZVAL_UNDEF(&undefined);
		return &undefined;
	}
	ZVAL_DEREF(slot);
	return slot;
}

inline zval *varOf(zval *node) { return nodeProp(pt_dnh_var_site, node, PT_LC("var")); }
inline zval *dimOf(zval *node) { return nodeProp(pt_dnh_dim_site, node, PT_LC("dim")); }
inline zval *classOf(zval *node) { return nodeProp(pt_dnh_class_site, node, PT_LC("class")); }
inline zval *nameOf(zval *node) { return nodeProp(pt_dnh_name_site, node, PT_LC("name")); }

/* $identifier->toString() / $name->toString() — the `name` string of an
 * Identifier or Name (NULL when it is not one) */
zend_string *identifierString(zval *identifier)
{
	if (Z_TYPE_P(identifier) != IS_OBJECT) return NULL;
	zval *name = nodeProp(pt_dnh_identifier_name_site, identifier, PT_LC("name"));
	return Z_TYPE_P(name) == IS_STRING ? Z_STR_P(name) : NULL;
}

/* $call->getArgs(): the raw `args` of a call that is not a first-class
 * callable (what getRawArgs() returns for every CallLike), the method —
 * whose assert() fires — for one that is; UNDEF = pending exception */
zv::Val callArgs(zval *call)
{
	bool firstClassCallable;
	if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(call), firstClassCallable))) return zv::Val();
	if (UNEXPECTED(firstClassCallable)) return pt_type_call(Z_OBJ_P(call), PT_LC("getargs"), 0, NULL);
	zval *args = nodeProp(pt_dnh_args_site, call, PT_LC("args"));
	if (UNEXPECTED(Z_TYPE_P(args) == IS_UNDEF)) return zv::Val::null();
	return zv::Val::copyOf(zv::Ref(args));
}

/* }}} */

/* {{{ values */

/* new SpecifiedTypes([], []) */
inline zv::Val emptyTypes()
{
	return pt_specified_types_new();
}

/* (new SpecifiedTypes([], []))->setRootExpr($rootExpr) */
inline zv::Val emptyTypesRooted(zval *rootExpr)
{
	return pt_specified_types_new_with_root_expr(NULL, NULL, rootExpr);
}

/* $types->setRootExpr($rootExpr) ($rootExpr NULL for null) */
inline zv::Val setRootExpr(zv::Val types, zval *rootExpr)
{
	if (UNEXPECTED(types.isUndef())) return zv::Val();
	return pt_specified_types_set_root_expr(Z_OBJ_P(types.raw()), rootExpr);
}

/* $types->unionWith($other) */
inline zv::Val unionWith(zv::Val types, zv::Val other)
{
	if (UNEXPECTED(types.isUndef() || other.isUndef())) return zv::Val();
	return pt_specified_types_union_with(Z_OBJ_P(types.raw()), other.raw());
}

/* $map[$exprString] = [$expr, $type] — symtable semantics, as the twin's
 * array write */
void setEntry(zv::Arr &map, zend_string *exprString, zval *expr, zval *type)
{
	zv::Arr pair = zv::Arr::create(2);
	pair.push(zv::Ref(expr));
	pair.push(zv::Ref(type));
	map.set(exprString, zv::Val(std::move(pair)));
}

inline zv::Val newNullType()
{
	zval out;
	if (UNEXPECTED(!pt_null_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

inline zv::Val borrowedObject(zend_object *object)
{
	zval value;
	ZVAL_OBJ_COPY(&value, object);
	return zv::Val::adopt(value);
}

/* TypeCombinator::union(...$types) over a list built here */
zv::Val unionOfList(zv::Arr &types)
{
	HashTable *table = types.table();
	if (HT_IS_PACKED(table) && table->nNumUsed == zend_hash_num_elements(table)) return pt_type_combinator_union(zend_hash_num_elements(table), table->arPacked);
	return pt_type_combinator_call_spread(PT_LC("union"), table);
}

/* $type->isNull() & co. through the Type ops: the PT_TRI_* value, -1 =
 * pending exception */
inline zend_long typeTrinary(zval *type, pt_type_op_id op, const char *method)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		(void) callOnNonObject(method, type);
		return -1;
	}
	return pt_type_op_trinary(Z_OBJ_P(type), op, 0, NULL);
}

/* $type->isTrue() / ->isFalse() (not ops): the PT_TRI_* value, -1 = pending
 * exception */
inline zend_long typeTrinaryByName(zval *type, const char *method, const char *lcname, size_t len)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		(void) callOnNonObject(method, type);
		return -1;
	}
	return pt_type_call_trinary(Z_OBJ_P(type), lcname, len, 0, NULL);
}

/* $a->isSuperTypeOf($b)->yes() */
[[nodiscard]] bool isSuperTypeOfYes(zval *a, zval *b, bool &out)
{
	zv::Val result = pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);
	if (UNEXPECTED(result.isUndef())) return false;
	zend_long value = pt_type_result_trinary(result.raw());
	if (UNEXPECTED(value < 0)) return false;
	out = value == PT_TRI_YES;
	return true;
}

/* substr($name, 1) of a parameter name */
zv::Val withoutFirstCharacter(zval *name)
{
	if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) {
		zend_type_error("substr(): Argument #1 ($string) must be of type string, %s given", zend_zval_value_name(name));
		return zv::Val();
	}
	zend_string *string = Z_STR_P(name);
	if (ZSTR_LEN(string) <= 1) return zv::Val::string(PT_LC(""));
	return zv::Val::string(ZSTR_VAL(string) + 1, ZSTR_LEN(string) - 1);
}

/* }}} */

/* {{{ TypeSpecifierContext queries (false = pending exception) */

[[nodiscard]] inline bool ctxNull(zval *context, bool &out) { return pt_type_specifier_context_null(Z_OBJ_P(context), out); }
[[nodiscard]] inline bool ctxFalseyButNotFalse(zval *context, bool &out) { return pt_type_specifier_context_falsey_but_not_false(Z_OBJ_P(context), out); }
[[nodiscard]] inline bool ctxTrue(zval *context, bool &out) { return pt_type_specifier_context_true(Z_OBJ_P(context), out); }
[[nodiscard]] inline bool ctxFalse(zval *context, bool &out) { return pt_type_specifier_context_false(Z_OBJ_P(context), out); }
[[nodiscard]] inline bool ctxTruthy(zval *context, bool &out) { return pt_type_specifier_context_truthy(Z_OBJ_P(context), out); }
[[nodiscard]] inline bool ctxFalsey(zval *context, bool &out) { return pt_type_specifier_context_falsey(Z_OBJ_P(context), out); }

/* a context singleton as an owned value */
inline zv::Val contextValue(zend_object *context)
{
	if (UNEXPECTED(context == NULL)) return zv::Val();
	return borrowedObject(context);
}

/* }}} */

/* the `static fn (): bool => true` of createIssetSingleSubjectNonTrueTypes() */
void alwaysTrueBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) captures;
	(void) argc;
	(void) argv;
	ZVAL_TRUE(return_value);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper; UNDEF =
 * pending exception, NULL zval arguments stand for null. */
class DefaultNarrowingHelper
{
public:
	explicit DefaultNarrowingHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *exprPrinter, bool rememberPossiblyImpureFunctionValues, zval *reflectionProvider)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::exprPrinter, zv::Val::copyOf(zv::Ref(exprPrinter)));
		object.propAtWrite(slots::rememberPossiblyImpureFunctionValues, zv::Val::boolean(rememberPossiblyImpureFunctionValues));
		object.propAtWrite(slots::reflectionProvider, zv::Val::copyOf(zv::Ref(reflectionProvider)));
	}

	/* Mirrors specifyTypesForNode(). */
	zv::Val specifyTypesForNode(zval *scope, zval *node, zval *context) const
	{
		if (isA(node, PT_CLASS_CALL_LIKE)) {
			bool firstClassCallable;
			if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(node), firstClassCallable))) return zv::Val();
			if (firstClassCallable) return emptyTypesRooted(node);
		} else if (UNEXPECTED(EG(exception))) {
			return zv::Val();
		}

		zv::Val walkScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scope));
		if (UNEXPECTED(walkScope.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(walkScope.raw()) != IS_OBJECT)) return callOnNonObject("specifyTypesOfNewWorldHandlerNode", walkScope.raw());
		return pt_mutating_scope_specify_types_of_new_world_handler_node(Z_OBJ_P(walkScope.raw()), Z_OBJ_P(node), context);
	}

	/* Mirrors specifyDefaultTypes(). */
	zv::Val specifyDefaultTypes(zval *expr, zval *context) const
	{
		bool isNull;
		if (UNEXPECTED(!ctxNull(context, isNull))) return zv::Val();
		if (isNull) return emptyTypesRooted(expr);

		zv::Val removedType;
		bool truthy;
		if (UNEXPECTED(!ctxTruthy(context, truthy))) return zv::Val();
		if (!truthy) {
			removedType = pt_static_type_factory_truthy();
		} else {
			bool falsey;
			if (UNEXPECTED(!ctxFalsey(context, falsey))) return zv::Val();
			if (falsey) return emptyTypesRooted(expr);
			removedType = pt_static_type_factory_falsey();
		}
		if (UNEXPECTED(removedType.isUndef())) return zv::Val();

		zv::Str exprString = print(expr);
		if (UNEXPECTED(exprString.isNull())) return zv::Val();
		zv::Arr sureNotTypes = zv::Arr::create(1);
		setEntry(sureNotTypes, exprString.get(), expr, removedType.raw());
		return pt_specified_types_new_with_root_expr(NULL, sureNotTypes.raw(), expr);
	}

	/* Mirrors specifyDefaultTypesWithPlainTwin(). */
	zv::Val specifyDefaultTypesWithPlainTwin(zval *expr, zval *exprResult, zval *context, zval *s) const
	{
		zv::Val defaultTypes = specifyDefaultTypes(expr, context);
		if (UNEXPECTED(defaultTypes.isUndef())) return zv::Val();
		if (exprResult == NULL) return defaultTypes;
		bool truthy;
		if (UNEXPECTED(!ctxTruthy(context, truthy))) return zv::Val();
		if (!truthy) return defaultTypes;
		bool falsey;
		if (UNEXPECTED(!ctxFalsey(context, falsey))) return zv::Val();
		if (falsey) return defaultTypes;

		zv::Val twinNodeHold;
		zval *twinNodeSlot = pt_expression_result_expr(exprResult, twinNodeHold);
		if (UNEXPECTED(twinNodeSlot == NULL)) return zv::Val();
		zv::Val twinNode = zv::Val::copyOf(zv::Ref(twinNodeSlot));
		zv::Val twin = createFromResultStateOnFalsey(s, twinNode.raw(), exprResult);
		if (UNEXPECTED(twin.isUndef())) return zv::Val();
		bool remembered;
		if (UNEXPECTED(!isSubjectValueRemembered(exprResult, twinNode.raw(), remembered))) return zv::Val();
		if (!remembered) {
			// only the receiver-not-null fan createSubjectTypesFromResultState()
			// produced for the impure call survives
			return setRootExpr(std::move(twin), expr);
		}

		zv::Val plainChain = getNullsafeShortcircuitedExpr(expr);
		if (UNEXPECTED(plainChain.isUndef())) return zv::Val();
		if (!(Z_TYPE_P(plainChain.raw()) == IS_OBJECT && Z_OBJ_P(plainChain.raw()) == Z_OBJ_P(expr))) {
			zv::Str plainString = print(plainChain.raw());
			if (UNEXPECTED(plainString.isNull())) return zv::Val();
			zv::Str twinString = print(twinNode.raw());
			if (UNEXPECTED(twinString.isNull())) return zv::Val();
			if (!zend_string_equals(plainString.get(), twinString.get())) {
				twin = unionWith(std::move(twin), createFromResultStateOnFalsey(s, plainChain.raw(), exprResult));
				if (UNEXPECTED(twin.isUndef())) return zv::Val();
			}
		}

		return setRootExpr(unionWith(std::move(defaultTypes), std::move(twin)), expr);
	}

	/* Mirrors isSubjectValueRemembered(): a value computed by a (possibly)
	 * impure call - the subject itself or any call nested in it - is not
	 * remembered; the result carries the impure points of its whole subtree.
	 * false = pending exception */
	[[nodiscard]] bool isSubjectValueRemembered(zval *subjectResult, zval *subject, bool &out) const
	{
		if (isA(subject, PT_CLASS_ALWAYS_REMEMBERED_EXPR)) {
			out = true;
			return true;
		}
		if (UNEXPECTED(EG(exception))) return false;

		zv::Val impurePointsHold;
		zval *impurePoints = pt_expression_result_impure_points(subjectResult, impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return false;
		if (Z_TYPE_P(impurePoints) == IS_ARRAY) {
			/* the slot is borrowed: keep the array alive across the calls */
			zv::Val impurePointsCopy = zv::Val::copyOf(zv::Ref(impurePoints));
			for (zv::ArrayEntry entry : zv::ArrRef(impurePointsCopy.raw())) {
				zval *impurePoint = entry.value().deref().raw();
				zv::Val nodeHold;
				zval *node = impurePointGetNode(impurePoint, nodeHold);
				if (UNEXPECTED(node == NULL)) return false;
				if (
					!isA(node, PT_CLASS_FUNC_CALL)
					&& !isA(node, PT_CLASS_METHOD_CALL)
					&& !isA(node, PT_CLASS_STATIC_CALL)
					&& !isA(node, PT_CLASS_NULLSAFE_METHOD_CALL)
				) {
					if (UNEXPECTED(EG(exception))) return false;
					continue;
				}

				bool certain = false;
				if (UNEXPECTED(!impurePointIsCertain(impurePoint, certain))) return false;
				if (!certain && rememberPossiblyImpureFunctionValues()) continue;

				out = false;
				return true;
			}
		}

		out = true;
		return true;
	}

	/* Mirrors toSureTypes(). */
	zv::Val toSureTypes(zval *types, zval *evaluationScope) const
	{
		zv::Val sureTypesValue = pt_specified_types_get_sure_types(Z_OBJ_P(types));
		if (UNEXPECTED(sureTypesValue.isUndef())) return zv::Val();
		zv::Arr sureTypes = zv::Arr::adoptVal(std::move(sureTypesValue));
		zv::Val sureNotTypes = pt_specified_types_get_sure_not_types(Z_OBJ_P(types));
		if (UNEXPECTED(sureNotTypes.isUndef())) return zv::Val();

		for (zv::ArrayEntry entry : zv::ArrRef(sureNotTypes.raw())) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong index = entry.indexKey();
			zval exprNode, sureNotType;
			if (UNEXPECTED(!destructurePair(entry.value().deref().raw(), exprNode, sureNotType))) return zv::Val();

			zval *existing = pt_ht_find(sureTypes.table(), key, index);
			if (existing != NULL) {
				ZVAL_DEREF(existing);
			}
			if (existing == NULL || Z_TYPE_P(existing) == IS_NULL) {
				if (UNEXPECTED(Z_TYPE(exprNode) != IS_OBJECT)) {
					zend_type_error("PHPStan\\Analyser\\MutatingScope::getStateType(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(&exprNode));
					return zv::Val();
				}
				zv::Val stateType = pt_mutating_scope_get_state_type(Z_OBJ_P(evaluationScope), Z_OBJ(exprNode));
				if (UNEXPECTED(stateType.isUndef())) return zv::Val();
				zv::Val removed = pt_type_combinator_remove(stateType.raw(), &sureNotType);
				if (UNEXPECTED(removed.isUndef())) return zv::Val();
				zv::Arr pair = zv::Arr::create(2);
				pair.push(zv::Ref(&exprNode));
				pair.push(std::move(removed));
				zval pairZv = zv::Val(std::move(pair)).take();
				sureTypes.separate();
				pt_ht_update(sureTypes.table(), key, index, &pairZv);
				continue;
			}

			/* $sureTypes[$exprString][1] = TypeCombinator::remove($sureTypes[$exprString][1], $sureNotType) */
			zval *current = Z_TYPE_P(existing) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(existing), 1) : NULL;
			if (UNEXPECTED(current == NULL)) {
				zend_error(E_WARNING, "Undefined array key 1");
				if (UNEXPECTED(EG(exception))) return zv::Val();
			}
			zval nullZv;
			ZVAL_NULL(&nullZv);
			zval *from = current != NULL ? current : &nullZv;
			ZVAL_DEREF(from);
			zv::Val removed = pt_type_combinator_remove(from, &sureNotType);
			if (UNEXPECTED(removed.isUndef())) return zv::Val();
			sureTypes.separate();
			zval *slot = pt_ht_find(sureTypes.table(), key, index);
			ZVAL_DEREF(slot);
			if (UNEXPECTED(Z_TYPE_P(slot) != IS_ARRAY)) {
				zend_throw_error(NULL, "Cannot use a scalar value as an array");
				return zv::Val();
			}
			SEPARATE_ARRAY(slot);
			zval removedZv = removed.take();
			zend_hash_index_update(Z_ARRVAL_P(slot), 1, &removedZv);
		}

		zv::Val result = pt_specified_types_new(sureTypes.raw(), NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		bool overwrite;
		if (UNEXPECTED(!pt_specified_types_should_overwrite(Z_OBJ_P(types), overwrite))) return zv::Val();
		if (overwrite) {
			result = pt_specified_types_set_always_overwrite_types(Z_OBJ_P(result.raw()));
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		zv::Val rootExpr = pt_specified_types_get_root_expr(Z_OBJ_P(types));
		if (UNEXPECTED(rootExpr.isUndef())) return zv::Val();
		return pt_specified_types_set_root_expr(Z_OBJ_P(result.raw()), rootExpr.raw());
	}

	/* Mirrors createSubjectTypes(). */
	zv::Val createSubjectTypes(zval *s, zval *subject, zval *subjectResult, zval *type, zval *context) const
	{
		if (subjectResult != NULL) {
			// a call handler's createTypesCallback gates only the call itself -
			// an impure call nested in it (strlen($record->getName())) is read
			// off the stored result, whose impure points cover the whole subtree;
			// a nullsafe call is left to its handler, which narrows through its
			// plain twin and so reaches this gate as a MethodCall
			bool isCall = isA(subject, PT_CLASS_FUNC_CALL) || isA(subject, PT_CLASS_METHOD_CALL) || isA(subject, PT_CLASS_STATIC_CALL);
			if (UNEXPECTED(EG(exception))) return zv::Val();
			if (isCall) {
				bool remembered;
				if (UNEXPECTED(!isSubjectValueRemembered(subjectResult, subject, remembered))) return zv::Val();
				if (!remembered) return createSubjectTypesFromResultState(s, subject, subjectResult, type, context);
			}

			zv::Val createdTypes = pt_expression_result_get_created_types_for_scope(subjectResult, s, type, context);
			if (UNEXPECTED(createdTypes.isUndef())) return zv::Val();
			if (!createdTypes.isNull()) return createdTypes;
		}

		return createSubjectTypesFromResultState(s, subject, subjectResult, type, context);
	}

	/* Mirrors createSubjectTypesFromResultState(). */
	zv::Val createSubjectTypesFromResultState(zval *s, zval *subject, zval *subjectResult, zval *type, zval *context) const
	{
		if (isA(subject, PT_CLASS_INSTANCEOF_EXPR) || isA(subject, PT_CLASS_LIST_EXPR)) return emptyTypes();
		if (UNEXPECTED(EG(exception))) return zv::Val();

		zval *exprToSpecify = subject;
		zv::Val exprToSpecifyHolder;
		zv::Val nullsafeFanTypes = zv::Val::null();
		if (subjectResult != NULL) {
			// a call whose own execution is (possibly) impure must not get a
			// remembered type - the gate reads the result's own impure point
			// instead of re-asking reflection like the old create() did
			bool remembered;
			if (UNEXPECTED(!isSubjectValueRemembered(subjectResult, subject, remembered))) return zv::Val();
			if (!remembered) {
				// the call's value is not remembered, but a nullsafe receiver
				// chain still narrows not-null: the chain must have evaluated
				// for the (impure) call to produce any non-null value at all
				bool containsNullsafe;
				if (UNEXPECTED(!pt_expression_result_contains_nullsafe(subjectResult, containsNullsafe))) return zv::Val();
				if (containsNullsafe) {
					bool ruledOut;
					if (UNEXPECTED(!nullsafeShortCircuitRuledOut(s, subjectResult, type, context, ruledOut))) return zv::Val();
					if (ruledOut) {
						zv::Val fan = createFirstNullsafeReceiverTypes(s, subject);
						if (UNEXPECTED(fan.isUndef())) return zv::Val();
						return fan.isNull() ? emptyTypes() : std::move(fan);
					}
				}

				return emptyTypes();
			}

			// a chain containing a nullsafe narrows its short-circuited plain
			// twin too, when the constraint (or the subject's own type) rules
			// the short-circuit null out
			bool containsNullsafe;
			if (UNEXPECTED(!pt_expression_result_contains_nullsafe(subjectResult, containsNullsafe))) return zv::Val();
			if (containsNullsafe) {
				bool nullRuledOut;
				if (UNEXPECTED(!nullsafeShortCircuitRuledOut(s, subjectResult, type, context, nullRuledOut))) return zv::Val();

				if (nullRuledOut) {
					exprToSpecifyHolder = getNullsafeShortcircuitedExpr(subject);
					if (UNEXPECTED(exprToSpecifyHolder.isUndef())) return zv::Val();
					exprToSpecify = exprToSpecifyHolder.raw();
					// a plain fetch/call wrapped AROUND a nullsafe chain has no
					// createTypesCallback of its own - fan "the chain did not
					// short-circuit" through the first nullsafe below it
					nullsafeFanTypes = createFirstNullsafeReceiverTypes(s, subject);
					if (UNEXPECTED(nullsafeFanTypes.isUndef())) return zv::Val();
				}
			}
		}

		bool sameNode = Z_TYPE_P(exprToSpecify) == IS_OBJECT && Z_OBJ_P(exprToSpecify) == Z_OBJ_P(subject);
		zv::Arr sureTypes = zv::Arr::empty();
		zv::Arr sureNotTypes = zv::Arr::empty();
		bool isFalse;
		if (UNEXPECTED(!ctxFalse(context, isFalse))) return zv::Val();
		if (isFalse) {
			if (UNEXPECTED(!addEntries(sureNotTypes, exprToSpecify, subject, sameNode, type))) return zv::Val();
		} else {
			bool isTrue;
			if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
			if (isTrue && UNEXPECTED(!addEntries(sureTypes, exprToSpecify, subject, sameNode, type))) return zv::Val();
		}

		zv::Val result = pt_specified_types_new(sureTypes.raw(), sureNotTypes.raw());
		if (!nullsafeFanTypes.isNull()) {
			result = unionWith(std::move(result), std::move(nullsafeFanTypes));
		}

		return result;
	}

	/* Mirrors specifyDefaultTypesWithNullsafeFan(). */
	zv::Val specifyDefaultTypesWithNullsafeFan(zval *expr, zval *context, zval *beforeScope, bool nativeTypesPromoted) const
	{
		zv::Val defaultTypes = specifyDefaultTypes(expr, context);
		if (UNEXPECTED(defaultTypes.isUndef())) return zv::Val();
		bool truthy;
		if (UNEXPECTED(!ctxTruthy(context, truthy))) return zv::Val();
		if (!truthy) return defaultTypes;
		bool falsey;
		if (UNEXPECTED(!ctxFalsey(context, falsey))) return zv::Val();
		if (falsey) return defaultTypes;

		zv::Val promotedScope;
		zval *s = beforeScope;
		if (nativeTypesPromoted) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(promotedScope.isUndef())) return zv::Val();
			s = promotedScope.raw();
		}
		zv::Val result = findStoredResult(s, expr);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (result.isNull()) return defaultTypes;

		zv::Val falseyType = pt_static_type_factory_falsey();
		if (UNEXPECTED(falseyType.isUndef())) return zv::Val();
		zend_object *falseContext = pt_type_specifier_context_create_false();
		if (UNEXPECTED(falseContext == NULL)) return zv::Val();
		zval falseContextZv;
		ZVAL_OBJ(&falseContextZv, falseContext);
		zv::Val fan = createNullsafeReceiverOnlyTypes(s, expr, result.raw(), falseyType.raw(), &falseContextZv);

		return setRootExpr(unionWith(std::move(defaultTypes), std::move(fan)), expr);
	}

	/* Mirrors createNullsafeReceiverOnlyTypes(). */
	zv::Val createNullsafeReceiverOnlyTypes(zval *s, zval *subject, zval *subjectResult, zval *type, zval *context) const
	{
		if (subjectResult == NULL) return emptyTypes();
		bool containsNullsafe;
		if (UNEXPECTED(!pt_expression_result_contains_nullsafe(subjectResult, containsNullsafe))) return zv::Val();
		if (!containsNullsafe) return emptyTypes();
		bool ruledOut;
		if (UNEXPECTED(!nullsafeShortCircuitRuledOut(s, subjectResult, type, context, ruledOut))) return zv::Val();
		if (!ruledOut) return emptyTypes();

		zv::Val fan = createFirstNullsafeReceiverTypes(s, subject);
		if (UNEXPECTED(fan.isUndef())) return zv::Val();
		return fan.isNull() ? emptyTypes() : std::move(fan);
	}

	/* Mirrors callMayHaveBeenSkipped(): whether a call on a `?->` chain may
	 * have been skipped in the branch the context describes, so nothing the
	 * callee declares narrows there; $receiverResult NULL for null; false =
	 * pending exception */
	[[nodiscard]] bool callMayHaveBeenSkipped(zval *receiverResult, zval *receiverType, zval *context, bool &out) const
	{
		out = false;
		if (receiverResult == NULL) return true;
		bool containsNullsafe;
		if (UNEXPECTED(!pt_expression_result_contains_nullsafe(receiverResult, containsNullsafe))) return false;
		if (!containsNullsafe) return true;

		bool isNull;
		if (UNEXPECTED(!ctxNull(context, isNull))) return false;
		if (!isNull) {
			bool falseyButNotFalse;
			if (UNEXPECTED(!ctxFalseyButNotFalse(context, falseyButNotFalse))) return false;
			if (!falseyButNotFalse) return true;
		}

		return pt_type_combinator_contains_null(receiverType, out);
	}

	/* Mirrors createForSubject(); $resultFor NULL for null. */
	zv::Val createForSubject(zval *subject, zval *type, zval *context, zval *scope, zval *resultFor) const
	{
		zv::Val walkScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scope));
		if (UNEXPECTED(walkScope.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(walkScope.raw()) != IS_OBJECT)) return callOnNonObject("getCurrentExpressionResultStorage", walkScope.raw());
		zv::Val subjectResult = zv::Val::null();
		if (resultFor != NULL) {
			subjectResult = pt_type_call_callable(resultFor, 1, subject);
			if (UNEXPECTED(subjectResult.isUndef())) return zv::Val();
		}

		zv::Val storage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(walkScope.raw()));
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		if (subjectResult.isNull() && !storage.isNull()) {
			subjectResult = pt_expression_result_storage_find(storage.raw(), subject);
			if (UNEXPECTED(subjectResult.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(!subjectResult.isNull() && Z_TYPE_P(subjectResult.raw()) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::createSubjectTypes(): Argument #3 ($subjectResult) must be of type ?PHPStan\\Analyser\\ExpressionResult, %s given", zend_zval_value_name(subjectResult.raw()));
			return zv::Val();
		}

		return createSubjectTypes(walkScope.raw(), subject, subjectResult.isNull() ? NULL : subjectResult.raw(), type, context);
	}

	/* Mirrors captureChainResults() — the twin's recursion as an explicit
	 * pre-order walk: a node, then its var chain, then its dim; false =
	 * pending exception. */
	[[nodiscard]] bool captureChainResults(zval *node, zval *storage, zval *chainResults) const
	{
		enum { INLINE_STACK_LIMIT = 16 };
		zend_object *inlineStack[INLINE_STACK_LIMIT];
		zend_object **stack = inlineStack;
		uint32_t capacity = INLINE_STACK_LIMIT;
		uint32_t depth = 0;
		stack[depth++] = Z_OBJ_P(node);
		bool ok = true;

		while (depth > 0) {
			zval current;
			ZVAL_OBJ(&current, stack[--depth]);

			zv::Val result = pt_expression_result_storage_find(storage, &current);
			if (UNEXPECTED(result.isUndef())) {
				ok = false;
				break;
			}
			if (!result.isNull()) {
				if (Z_TYPE_P(chainResults) != IS_ARRAY) {
					zval_ptr_dtor(chainResults);
					array_init(chainResults);
				}
				SEPARATE_ARRAY(chainResults);
				zval resultZv = result.take();
				zend_hash_index_update(Z_ARRVAL_P(chainResults), (zend_ulong) Z_OBJ_HANDLE(current), &resultZv);
			}

			zval *children[2];
			uint32_t childCount = 0;
			if (isA(&current, PT_CLASS_ARRAY_DIM_FETCH)) {
				children[childCount++] = varOf(&current);
				zval *dim = dimOf(&current);
				if (Z_TYPE_P(dim) != IS_NULL && Z_TYPE_P(dim) != IS_UNDEF) {
					children[childCount++] = dim;
				}
			} else if (isA(&current, PT_CLASS_PROPERTY_FETCH)) {
				children[childCount++] = varOf(&current);
			} else if (isA(&current, PT_CLASS_STATIC_PROPERTY_FETCH)) {
				zval *classNode = classOf(&current);
				if (isA(classNode, PT_CLASS_EXPR)) {
					children[childCount++] = classNode;
				}
			}
			if (UNEXPECTED(EG(exception))) {
				ok = false;
				break;
			}

			if (depth + childCount > capacity) {
				uint32_t grown = capacity * 2;
				zend_object **larger = (zend_object **) safe_emalloc(grown, sizeof(zend_object *), 0);
				std::copy_n(stack, depth, larger);
				if (stack != inlineStack) {
					efree(stack);
				}
				stack = larger;
				capacity = grown;
			}
			/* the var chain is visited before the dim: pushed last */
			for (uint32_t i = childCount; i > 0; i--) {
				zval *child = children[i - 1];
				if (UNEXPECTED(Z_TYPE_P(child) != IS_OBJECT)) {
					zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::captureChainResults(): Argument #1 ($node) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(child));
					ok = false;
					break;
				}
				stack[depth++] = Z_OBJ_P(child);
			}
			if (!ok) break;
		}

		if (stack != inlineStack) {
			efree(stack);
		}
		return ok;
	}

	/* Mirrors buildChainTypeReader(). */
	zv::Val buildChainTypeReader(zval *chainResults, zval *s) const
	{
		zv::Val reader = pt_native_closure(&chainTypeReaderBody, chainResults, s);
		return pt_native_closure_to_closure(reader.raw());
	}

	/* Mirrors createIssetTruthyChainTypes(). */
	zv::Val createIssetTruthyChainTypes(zval *s, zval *issetExpr, zval *readType, zval *rootExpr, zval *context) const
	{
		/* $tmpVars, then array_reverse(): the chain from its root outwards */
		zv::Arr tmpVars = zv::Arr::create(4);
		tmpVars.push(zv::Ref(issetExpr));
		zval *current = issetExpr;
		for (;;) {
			bool isStaticPropertyFetch = isA(current, PT_CLASS_STATIC_PROPERTY_FETCH);
			if (isA(current, PT_CLASS_ARRAY_DIM_FETCH) || isA(current, PT_CLASS_PROPERTY_FETCH)) {
				current = varOf(current);
			} else if (isStaticPropertyFetch && isA(classOf(current), PT_CLASS_EXPR)) {
				current = classOf(current);
			} else {
				break;
			}
			tmpVars.push(zv::Ref(current));
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		zv::Val types = emptyTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		HashTable *vars = tmpVars.table();
		for (uint32_t i = vars->nNumUsed; i > 0; i--) {
			zval *var = &vars->arPacked[i - 1];

			if (isA(var, PT_CLASS_VARIABLE)) {
				zval *name = nameOf(var);
				if (Z_TYPE_P(name) == IS_STRING) {
					zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(s), Z_STR_P(name));
					if (UNEXPECTED(has.isUndef())) return zv::Val();
					zend_long hasValue = pt_type_trinary_value(has.raw());
					if (UNEXPECTED(hasValue < 0)) return zv::Val();
					if (hasValue == PT_TRI_NO) return emptyTypesRooted(rootExpr);
				}
			}

			if (isA(var, PT_CLASS_ARRAY_DIM_FETCH)) {
				zval *dim = dimOf(var);
				if (Z_TYPE_P(dim) != IS_NULL && Z_TYPE_P(dim) != IS_UNDEF) {
					zv::Val probedVarType = pt_type_call_callable(readType, 1, varOf(var));
					if (UNEXPECTED(probedVarType.isUndef())) return zv::Val();
					if (!(Z_TYPE_P(probedVarType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(probedVarType.raw()), pt_ce_mixed_type))) {
						zv::Val dimType = pt_type_call_callable(readType, 1, dim);
						if (UNEXPECTED(dimType.isUndef())) return zv::Val();

						if (isConstantIntegerOrString(dimType.raw())) {
							zval hasOffset;
							if (UNEXPECTED(!pt_has_offset_type_new(&hasOffset, dimType.raw()))) return zv::Val();
							zv::Val hasOffsetType = zv::Val::adopt(hasOffset);
							types = unionWith(std::move(types), setRootExpr(createForSubject(varOf(var), hasOffsetType.raw(), context, s, NULL), rootExpr));
							if (UNEXPECTED(types.isUndef())) return zv::Val();
						} else {
							zv::Val varType = pt_type_call_callable(readType, 1, varOf(var));
							if (UNEXPECTED(varType.isUndef())) return zv::Val();

							zv::Val narrowedKey = narrowOffsetKeyType(varType.raw(), dimType.raw());
							if (UNEXPECTED(narrowedKey.isUndef())) return zv::Val();
							if (!narrowedKey.isNull()) {
								types = unionWith(std::move(types), setRootExpr(createForSubject(dimOf(var), narrowedKey.raw(), context, s, NULL), rootExpr));
								if (UNEXPECTED(types.isUndef())) return zv::Val();
							}

							zend_long isArray = typeTrinary(varType.raw(), PT_OP_IS_ARRAY, "isArray");
							if (UNEXPECTED(isArray < 0)) return zv::Val();
							if (isArray == PT_TRI_YES) {
								zval nonEmpty;
								if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmpty))) return zv::Val();
								zv::Val nonEmptyArrayType = zv::Val::adopt(nonEmpty);
								types = unionWith(std::move(types), setRootExpr(createForSubject(varOf(var), nonEmptyArrayType.raw(), context, s, NULL), rootExpr));
								if (UNEXPECTED(types.isUndef())) return zv::Val();
							}
						}
					}
				}
			}

			if (isA(var, PT_CLASS_PROPERTY_FETCH)) {
				zend_string *propertyName = isA(nameOf(var), PT_CLASS_IDENTIFIER) ? identifierString(nameOf(var)) : NULL;
				if (propertyName != NULL) {
					zv::Val hasPropertyIntersection = objectWithProperty(propertyName);
					if (UNEXPECTED(hasPropertyIntersection.isUndef())) return zv::Val();
					zval truthyZv;
					if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_truthy(), truthyZv))) return zv::Val();
					types = unionWith(std::move(types), setRootExpr(createForSubject(varOf(var), hasPropertyIntersection.raw(), &truthyZv, s, NULL), rootExpr));
					if (UNEXPECTED(types.isUndef())) return zv::Val();
				}
			} else if (isA(var, PT_CLASS_STATIC_PROPERTY_FETCH) && isA(classOf(var), PT_CLASS_EXPR) && isA(nameOf(var), PT_CLASS_VAR_LIKE_IDENTIFIER)) {
				zend_string *propertyName = identifierString(nameOf(var));
				if (propertyName != NULL) {
					zv::Val hasPropertyIntersection = objectWithProperty(propertyName);
					if (UNEXPECTED(hasPropertyIntersection.isUndef())) return zv::Val();
					zval truthyZv;
					if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_truthy(), truthyZv))) return zv::Val();
					types = unionWith(std::move(types), setRootExpr(createForSubject(classOf(var), hasPropertyIntersection.raw(), &truthyZv, s, NULL), rootExpr));
					if (UNEXPECTED(types.isUndef())) return zv::Val();
				}
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();

			zv::Val nullType = newNullType();
			if (UNEXPECTED(nullType.isUndef())) return zv::Val();
			zval falseZv;
			if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_false(), falseZv))) return zv::Val();
			types = unionWith(std::move(types), setRootExpr(createForSubject(var, nullType.raw(), &falseZv, s, NULL), rootExpr));
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		}

		return types;
	}

	/* Mirrors createIssetSingleSubjectNonTrueTypes(). */
	zv::Val createIssetSingleSubjectNonTrueTypes(zval *s, zval *issetExpr, zval *varResult, zval *readType, zval *context, zval *rootExpr) const
	{
		zv::Val resolution = pt_expression_result_get_issetability_resolution(varResult, s, false, false);
		if (UNEXPECTED(resolution.isUndef())) return zv::Val();
		zv::Val alwaysTrue = pt_native_closure(&alwaysTrueBody);
		zv::Val issetValue = resolutionIsSet(resolution.raw(), alwaysTrue.raw());
		if (UNEXPECTED(issetValue.isUndef())) return zv::Val();
		/* ?bool: IS_TRUE / IS_FALSE / IS_NULL */
		zend_uchar isset = Z_TYPE_P(issetValue.raw());

		if (isset == IS_FALSE) return pt_specified_types_new();

		zv::Val type = pt_type_call_callable(readType, 1, issetExpr);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zend_long typeIsNull = typeTrinary(type.raw(), PT_OP_IS_NULL, "isNull");
		if (UNEXPECTED(typeIsNull < 0)) return zv::Val();
		bool isNullable = typeIsNull != PT_TRI_NO;
		zv::Val nullType = newNullType();
		if (UNEXPECTED(nullType.isUndef())) return zv::Val();
		zv::Val negated = pt_type_specifier_context_negate(Z_OBJ_P(context));
		if (UNEXPECTED(negated.isUndef())) return zv::Val();
		zv::Val exprType = setRootExpr(createForSubject(issetExpr, nullType.raw(), negated.raw(), s, NULL), rootExpr);
		if (UNEXPECTED(exprType.isUndef())) return zv::Val();

		if (isA(issetExpr, PT_CLASS_VARIABLE) && Z_TYPE_P(nameOf(issetExpr)) == IS_STRING) {
			if (isset == IS_TRUE) {
				if (isNullable) return exprType;

				// variable cannot exist in !isset()
				zv::Val issetNode = pt_type_new(PT_CLASS_ISSET_EXPR, 1, issetExpr);
				if (UNEXPECTED(issetNode.isUndef())) return zv::Val();
				zv::Val nullType2 = newNullType();
				if (UNEXPECTED(nullType2.isUndef())) return zv::Val();
				return setRootExpr(unionWith(std::move(exprType), createForSubject(issetNode.raw(), nullType2.raw(), context, s, NULL)), rootExpr);
			}

			if (isNullable) {
				// reduces variable certainty to maybe
				zv::Val issetNode = pt_type_new(PT_CLASS_ISSET_EXPR, 1, issetExpr);
				if (UNEXPECTED(issetNode.isUndef())) return zv::Val();
				zv::Val nullType2 = newNullType();
				if (UNEXPECTED(nullType2.isUndef())) return zv::Val();
				zv::Val negated2 = pt_type_specifier_context_negate(Z_OBJ_P(context));
				if (UNEXPECTED(negated2.isUndef())) return zv::Val();
				return setRootExpr(unionWith(std::move(exprType), createForSubject(issetNode.raw(), nullType2.raw(), negated2.raw(), s, NULL)), rootExpr);
			}

			// variable cannot exist in !isset()
			zv::Val issetNode = pt_type_new(PT_CLASS_ISSET_EXPR, 1, issetExpr);
			if (UNEXPECTED(issetNode.isUndef())) return zv::Val();
			zv::Val nullType2 = newNullType();
			if (UNEXPECTED(nullType2.isUndef())) return zv::Val();
			return setRootExpr(createForSubject(issetNode.raw(), nullType2.raw(), context, s, NULL), rootExpr);
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		if (isNullable && isset == IS_TRUE) return exprType;

		// A maybe verdict on a native-typed property whose inner chain is fully
		// set can only mean "nullable value" or "maybe uninitialized".
		if (isset == IS_NULL && isNullable) {
			zv::Val resolution2 = pt_expression_result_get_issetability_resolution(varResult, s, false, false);
			if (UNEXPECTED(resolution2.isUndef())) return zv::Val();
			if (UNEXPECTED(!resolution2.ref().isObject())) return callOnNonObject("getLink", resolution2.raw());
			zv::Val linkHold;
			zv::Val link = ownedRead(pt_issetability_resolution_link(resolution2.raw(), linkHold), linkHold);
			if (UNEXPECTED(link.isUndef())) return zv::Val();
			zv::Val innerHold;
			zv::Val inner = ownedRead(pt_issetability_resolution_inner(resolution2.raw(), innerHold), innerHold);
			if (UNEXPECTED(inner.isUndef())) return zv::Val();
			if (UNEXPECTED(!link.ref().isObject())) return callOnNonObject("isProperty", link.raw());
			bool matches;
			if (UNEXPECTED(!pt_issetability_link_info_is_kind(link.raw(), PT_ISSETABILITY_LINK_PROPERTY, matches))) return zv::Val();
			if (matches && UNEXPECTED(!pt_issetability_link_info_is_reflection_native(link.raw(), matches))) return zv::Val();
			if (matches && UNEXPECTED(!pt_issetability_link_info_has_native_type(link.raw(), matches))) return zv::Val();
			if (matches) {
				zv::Val virtualHold;
				zval *isVirtual = pt_issetability_link_info_is_virtual(link.raw(), virtualHold);
				if (UNEXPECTED(isVirtual == NULL)) return zv::Val();
				zend_long virtualValue = pt_type_trinary_value(isVirtual);
				if (UNEXPECTED(virtualValue < 0)) return zv::Val();
				matches = virtualValue != PT_TRI_YES;
			}
			if (matches && !inner.isNull()) {
				zv::Val innerAlwaysTrue = pt_native_closure(&alwaysTrueBody);
				zv::Val innerIsSet = resolutionIsSet(inner.raw(), innerAlwaysTrue.raw());
				if (UNEXPECTED(innerIsSet.isUndef())) return zv::Val();
				matches = Z_TYPE_P(innerIsSet.raw()) == IS_TRUE;
			}
			if (matches) return exprType;
		}

		if (isA(issetExpr, PT_CLASS_ARRAY_DIM_FETCH)) {
			zval *dim = dimOf(issetExpr);
			zval *var = varOf(issetExpr);
			// When the var is itself an offset access (a nested isset like
			// $r['K']['Port']), narrowing it in the falsey branch leaks the
			// intermediate offset's existence into the enclosing scope.
			if (Z_TYPE_P(dim) != IS_NULL && Z_TYPE_P(dim) != IS_UNDEF && !isA(var, PT_CLASS_ARRAY_DIM_FETCH)) {
				zv::Val result = offsetRemovalTypes(s, var, dim, readType, rootExpr);
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				if (!result.isNull()) return result;
			}
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		return pt_specified_types_new();
	}

	/* Mirrors specifyTypesFromAsserts(). */
	zv::Val specifyTypesFromAsserts(zval *context, zval *call, zval *assertions, zval *parametersAcceptor, zval *scope) const
	{
		zv::Val asserts;
		bool matches;
		if (UNEXPECTED(!ctxNull(context, matches))) return zv::Val();
		if (matches) {
			asserts = callNoArgs(pt_dnh_get_asserts_site, assertions, "getAsserts", PT_LC("getasserts"));
		} else {
			if (UNEXPECTED(!ctxTrue(context, matches))) return zv::Val();
			if (matches) {
				asserts = callNoArgs(pt_dnh_get_asserts_if_true_site, assertions, "getAssertsIfTrue", PT_LC("getassertsiftrue"));
			} else {
				if (UNEXPECTED(!ctxFalse(context, matches))) return zv::Val();
				if (!matches) {
					pt_throw_should_not_happen();
					return zv::Val();
				}
				asserts = callNoArgs(pt_dnh_get_asserts_if_false_site, assertions, "getAssertsIfFalse", PT_LC("getassertsiffalse"));
			}
		}
		if (UNEXPECTED(asserts.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(asserts.raw()) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(asserts.raw()));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(asserts.raw())) == 0) return zv::Val::null();

		zv::Arr argsMap = zv::Arr::empty();
		zv::Val parameters = callNoArgs(pt_dnh_acceptor_get_parameters_site, parametersAcceptor, "getParameters", PT_LC("getparameters"));
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		if (UNEXPECTED(!mapArgumentsToParameters(call, parametersAcceptor, parameters.raw(), true, argsMap))) return zv::Val();
		if (Z_TYPE_P(parameters.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(parameters.raw())) {
				zval *parameter = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) return callOnNonObject("getName", parameter);
				zv::Val name = pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				zv::Val defaultValue = pt_parameter_reflection_call(parameter, PT_PR_GET_DEFAULT_VALUE);
				if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(name.raw()) != IS_STRING)) {
					zend_throw_error(NULL, "phpstan_turbo: a parameter name is not a string");
					return zv::Val();
				}
				zval *existing = zend_symtable_find(argsMap.table(), Z_STR_P(name.raw()));
				if ((existing != NULL && Z_TYPE_P(existing) != IS_NULL) || defaultValue.isNull()) continue;
				zv::Val typeExpr = pt_type_new(PT_CLASS_TYPE_EXPR, 1, defaultValue.raw());
				if (UNEXPECTED(typeExpr.isUndef())) return zv::Val();
				appendToArgsMap(argsMap, Z_STR_P(name.raw()), std::move(typeExpr));
			}
		}

		if (isA(call, PT_CLASS_METHOD_CALL)) {
			zv::Arr thisList = zv::Arr::create(1);
			thisList.push(zv::Ref(varOf(call)));
			argsMap.set("this", zv::Val(std::move(thisList)));
		} else if (UNEXPECTED(EG(exception))) {
			return zv::Val();
		}

		zv::Val types = zv::Val::null();
		/* the asserted type's subject lookup for ConditionalTypeForParameter::resolveInType() (created on first use) */
		zv::Val assertedTypeCallback;

		zv::Val assertsHolder = std::move(asserts);
		for (zv::ArrayEntry assertEntry : zv::ArrRef(assertsHolder.raw())) {
			zval *assert = assertEntry.value().deref().raw();
			zv::Val assertParameter = callNoArgs(pt_dnh_assert_get_parameter_site, assert, "getParameter", PT_LC("getparameter"));
			if (UNEXPECTED(assertParameter.isUndef())) return zv::Val();
			zv::Val parameterName = callNoArgs(pt_dnh_assert_parameter_get_parameter_name_site, assertParameter.raw(), "getParameterName", PT_LC("getparametername"));
			if (UNEXPECTED(parameterName.isUndef())) return zv::Val();
			zv::Val lookupName = withoutFirstCharacter(parameterName.raw());
			if (UNEXPECTED(lookupName.isUndef())) return zv::Val();
			zval *parameterExprs = zend_symtable_find(argsMap.table(), Z_STR_P(lookupName.raw()));
			if (parameterExprs == NULL || Z_TYPE_P(parameterExprs) == IS_NULL) continue;
			zv::Val parameterExprList = zv::Val::copyOf(zv::Ref(parameterExprs));
			if (UNEXPECTED(Z_TYPE_P(parameterExprList.raw()) != IS_ARRAY)) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(parameterExprList.raw()));
				if (UNEXPECTED(EG(exception))) return zv::Val();
				continue;
			}

			for (zv::ArrayEntry parameterExprEntry : zv::ArrRef(parameterExprList.raw())) {
				zval *parameterExpr = parameterExprEntry.value().deref().raw();

				zv::Val assertType = callNoArgs(pt_dnh_assert_get_type_site, assert, "getType", PT_LC("gettype"));
				if (UNEXPECTED(assertType.isUndef())) return zv::Val();
				if (assertedTypeCallback.isUndef()) {
					assertedTypeCallback = pt_native_closure(&assertSubjectTypeBody, argsMap.raw(), scope);
				}
				zv::Val assertedType = pt_conditional_type_for_parameter_resolve_in_type(assertType.raw(), assertedTypeCallback.raw());
				if (UNEXPECTED(assertedType.isUndef())) return zv::Val();

				zv::Val assertParameter2 = callNoArgs(pt_dnh_assert_get_parameter_site, assert, "getParameter", PT_LC("getparameter"));
				if (UNEXPECTED(assertParameter2.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(assertParameter2.raw()) != IS_OBJECT)) return callOnNonObject("getExpr", assertParameter2.raw());
				zv::Val assertExpr = pt_call_method_cached(pt_dnh_assert_parameter_get_expr_site, Z_OBJ_P(assertParameter2.raw()), PT_LC("getexpr"), 1, parameterExpr);
				if (UNEXPECTED(assertExpr.isUndef())) return zv::Val();

				zv::Val templateTypeMap = callNoArgs(pt_dnh_acceptor_get_resolved_template_type_map_site, parametersAcceptor, "getResolvedTemplateTypeMap", PT_LC("getresolvedtemplatetypemap"));
				if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
				zval containsUnresolvedTemplate;
				ZVAL_NEW_REF(&containsUnresolvedTemplate, &EG(uninitialized_zval));
				ZVAL_FALSE(Z_REFVAL(containsUnresolvedTemplate));
				zv::Val containsUnresolvedTemplateRef = zv::Val::adopt(containsUnresolvedTemplate);
				zv::Val originalType = callNoArgs(pt_dnh_assert_get_original_type_site, assert, "getOriginalType", PT_LC("getoriginaltype"));
				if (UNEXPECTED(originalType.isUndef())) return zv::Val();
				{
					zval captures[2];
					ZVAL_COPY_VALUE(&captures[0], templateTypeMap.raw());
					ZVAL_COPY_VALUE(&captures[1], containsUnresolvedTemplateRef.raw());
					zv::Val templateCallback = pt_native_closure_new(&unresolvedTemplateTraverseBody, 2, captures, 1u << 1);
					zval ignored;
					if (UNEXPECTED(!pt_type_traverser_map(&ignored, originalType.raw(), templateCallback.raw()))) return zv::Val();
					zval_ptr_dtor(&ignored);
				}

				zv::Val assertStorage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(scope));
				if (UNEXPECTED(assertStorage.isUndef())) return zv::Val();
				zv::Val subjectResult = zv::Val::null();
				bool isTypeExpr = isA(assertExpr.raw(), PT_CLASS_TYPE_EXPR);
				if (UNEXPECTED(EG(exception))) return zv::Val();
				if (!isTypeExpr && !assertStorage.isNull()) {
					if (UNEXPECTED(Z_TYPE_P(assertExpr.raw()) != IS_OBJECT)) {
						zend_type_error("PHPStan\\Analyser\\ExpressionResultStorage::findExpressionResult(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(assertExpr.raw()));
						return zv::Val();
					}
					subjectResult = pt_expression_result_storage_find(assertStorage.raw(), assertExpr.raw());
					if (UNEXPECTED(subjectResult.isUndef())) return zv::Val();
				}
				if (subjectResult.isNull() && isA(assertExpr.raw(), PT_CLASS_CALL_LIKE)) {
					bool mayRemember;
					if (UNEXPECTED(!mayRememberCallSubject(scope, assertExpr.raw(), mayRemember))) return zv::Val();
					if (!mayRemember) {
						// a call subject whose value must not be remembered (side
						// effects) contributes no narrowing
						continue;
					}
				}
				if (UNEXPECTED(EG(exception))) return zv::Val();

				bool negated;
				if (UNEXPECTED(!callNoArgsBool(pt_dnh_assert_is_negated_site, assert, "isNegated", PT_LC("isnegated"), negated))) return zv::Val();
				zval subjectContext;
				if (UNEXPECTED(!contextZval(negated ? pt_type_specifier_context_create_false() : pt_type_specifier_context_create_true(), subjectContext))) return zv::Val();
				zv::Val newTypes = createSubjectTypes(scope, assertExpr.raw(), subjectResult.isNull() ? NULL : subjectResult.raw(), assertedType.raw(), &subjectContext);
				if (UNEXPECTED(newTypes.isUndef())) return zv::Val();
				bool equality = zend_is_true(Z_REFVAL_P(containsUnresolvedTemplateRef.raw()));
				if (!equality && UNEXPECTED(!callNoArgsBool(pt_dnh_assert_is_equality_site, assert, "isEquality", PT_LC("isequality"), equality))) return zv::Val();
				if (equality) {
					newTypes = pt_specified_types_set_equality(Z_OBJ_P(newTypes.raw()));
					if (UNEXPECTED(newTypes.isUndef())) return zv::Val();
				}
				types = types.isNull() ? std::move(newTypes) : unionWith(std::move(types), std::move(newTypes));
				if (UNEXPECTED(types.isUndef())) return zv::Val();

				bool contextNull;
				if (UNEXPECTED(!ctxNull(context, contextNull))) return zv::Val();
				if (!contextNull) continue;
				zend_long assertedTrue = typeTrinaryByName(assertedType.raw(), "isTrue", PT_LC("istrue"));
				if (UNEXPECTED(assertedTrue < 0)) return zv::Val();
				if (assertedTrue != PT_TRI_YES) {
					zend_long assertedFalse = typeTrinaryByName(assertedType.raw(), "isFalse", PT_LC("isfalse"));
					if (UNEXPECTED(assertedFalse < 0)) return zv::Val();
					if (assertedFalse != PT_TRI_YES) continue;
				}

				zend_long assertedTrueAgain = typeTrinaryByName(assertedType.raw(), "isTrue", PT_LC("istrue"));
				if (UNEXPECTED(assertedTrueAgain < 0)) return zv::Val();
				zv::Val subContext = contextValue(assertedTrueAgain == PT_TRI_YES ? pt_type_specifier_context_create_true() : pt_type_specifier_context_create_false());
				if (UNEXPECTED(subContext.isUndef())) return zv::Val();
				bool negatedAgain;
				if (UNEXPECTED(!callNoArgsBool(pt_dnh_assert_is_negated_site, assert, "isNegated", PT_LC("isnegated"), negatedAgain))) return zv::Val();
				if (negatedAgain) {
					subContext = pt_type_specifier_context_negate(Z_OBJ_P(subContext.raw()));
					if (UNEXPECTED(subContext.isUndef())) return zv::Val();
				}

				types = unionWith(std::move(types), specifyTypesForNode(scope, assertExpr.raw(), subContext.raw()));
				if (UNEXPECTED(types.isUndef())) return zv::Val();
			}
		}

		return types;
	}

	/* Mirrors specifyTypesFromConditionalReturnType(). */
	zv::Val specifyTypesFromConditionalReturnType(zval *context, zval *call, zval *parametersAcceptor, zval *scope) const
	{
		if (!isA(parametersAcceptor, PT_CLASS_RESOLVED_FUNCTION_VARIANT)) {
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}

		zv::Val originalAcceptor = callNoArgs(pt_dnh_acceptor_get_original_parameters_acceptor_site, parametersAcceptor, "getOriginalParametersAcceptor", PT_LC("getoriginalparametersacceptor"));
		if (UNEXPECTED(originalAcceptor.isUndef())) return zv::Val();
		zv::Val returnType = callNoArgs(pt_dnh_original_acceptor_get_return_type_site, originalAcceptor.raw(), "getReturnType", PT_LC("getreturntype"));
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		if (!(Z_TYPE_P(returnType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(returnType.raw()), pt_ce_conditional_type_for_parameter))) return zv::Val::null();

		zval leftZv, rightZv;
		bool matches;
		if (UNEXPECTED(!ctxTrue(context, matches))) return zv::Val();
		if (matches) {
			if (UNEXPECTED(!pt_constant_boolean_type_new(&leftZv, true))) return zv::Val();
			if (UNEXPECTED(!pt_constant_boolean_type_new(&rightZv, false))) {
				zval_ptr_dtor(&leftZv);
				return zv::Val();
			}
		} else {
			if (UNEXPECTED(!ctxFalse(context, matches))) return zv::Val();
			if (matches) {
				if (UNEXPECTED(!pt_constant_boolean_type_new(&leftZv, false))) return zv::Val();
				if (UNEXPECTED(!pt_constant_boolean_type_new(&rightZv, true))) {
					zval_ptr_dtor(&leftZv);
					return zv::Val();
				}
			} else {
				if (UNEXPECTED(!ctxNull(context, matches))) return zv::Val();
				if (!matches) return zv::Val::null();
				if (UNEXPECTED(!pt_mixed_type_new(&leftZv))) return zv::Val();
				if (UNEXPECTED(!pt_never_type_new(&rightZv))) {
					zval_ptr_dtor(&leftZv);
					return zv::Val();
				}
			}
		}
		zv::Val leftType = zv::Val::adopt(leftZv);
		zv::Val rightType = zv::Val::adopt(rightZv);

		zv::Val argumentExpr = zv::Val::null();
		zv::Val parameters = callNoArgs(pt_dnh_acceptor_get_parameters_site, parametersAcceptor, "getParameters", PT_LC("getparameters"));
		if (UNEXPECTED(parameters.isUndef())) return zv::Val();
		zv::Val args = callArgs(call);
		if (UNEXPECTED(args.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(args.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(args.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			for (zv::ArrayEntry entry : zv::ArrRef(args.raw())) {
				zval *arg = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) continue;
				if (zend_is_true(nodeProp(pt_dnh_arg_unpack_site, arg, PT_LC("unpack")))) continue;

				zv::Val paramName;
				zval *argName = nodeProp(pt_dnh_arg_name_site, arg, PT_LC("name"));
				if (Z_TYPE_P(argName) == IS_OBJECT) {
					zend_string *name = identifierString(argName);
					paramName = name != NULL ? zv::Val::string(name) : zv::Val::null();
				} else {
					zval *parameter = Z_TYPE_P(parameters.raw()) == IS_ARRAY ? parameterAt(parameters.raw(), entry) : NULL;
					if (parameter == NULL) continue;
					if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) return callOnNonObject("getName", parameter);
					paramName = pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
					if (UNEXPECTED(paramName.isUndef())) return zv::Val();
				}

				zv::Val conditionalParameterName = pt_type_call(Z_OBJ_P(returnType.raw()), PT_LC("getparametername"), 0, NULL);
				if (UNEXPECTED(conditionalParameterName.isUndef())) return zv::Val();
				if (!isDollarName(conditionalParameterName.raw(), paramName.raw())) continue;

				argumentExpr = zv::Val::copyOf(zv::Ref(nodeProp(pt_dnh_arg_value_site, arg, PT_LC("value"))));
			}
		}

		if (argumentExpr.isNull()) return zv::Val::null();

		return getConditionalSpecifiedTypes(returnType.raw(), leftType.raw(), rightType.raw(), scope, argumentExpr.raw());
	}

	/* the entry of the handler class: $this->exprPrinter */
	zval *exprPrinter() const { return OBJ_PROP_NUM(self, slots::exprPrinter); }

private:
	zend_object *self;

	bool rememberPossiblyImpureFunctionValues() const { return Z_TYPE_P(OBJ_PROP_NUM(self, slots::rememberPossiblyImpureFunctionValues)) == IS_TRUE; }

	/* $this->exprPrinter->printExpr($expr); NULL = pending exception */
	zv::Str print(zval *expr) const
	{
		if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Node\\Printer\\ExprPrinter::printExpr(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(expr));
			return zv::Str();
		}
		return zv::Str::adopt(pt_expr_printer_print(exprPrinter(), Z_OBJ_P(expr)));
	}

	/* the entries of the true / false branch: $map[print($exprToSpecify)],
	 * then $map[print($subject)] when it is another node; false = pending
	 * exception */
	[[nodiscard]] bool addEntries(zv::Arr &map, zval *exprToSpecify, zval *subject, bool sameNode, zval *type) const
	{
		zv::Str key = print(exprToSpecify);
		if (UNEXPECTED(key.isNull())) return false;
		setEntry(map, key.get(), exprToSpecify, type);
		if (!sameNode) {
			zv::Str subjectKey = print(subject);
			if (UNEXPECTED(subjectKey.isNull())) return false;
			setEntry(map, subjectKey.get(), subject, type);
		}
		return true;
	}

	/* createSubjectTypesFromResultState($s, $node, $result,
	 * StaticTypeFactory::falsey(), TypeSpecifierContext::createFalse()) */
	zv::Val createFromResultStateOnFalsey(zval *s, zval *node, zval *result) const
	{
		zv::Val falseyType = pt_static_type_factory_falsey();
		if (UNEXPECTED(falseyType.isUndef())) return zv::Val();
		zval falseZv;
		if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_false(), falseZv))) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::createSubjectTypesFromResultState(): Argument #2 ($subject) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(node));
			return zv::Val();
		}
		return createSubjectTypesFromResultState(s, node, result, falseyType.raw(), &falseZv);
	}

	/* a borrowed context singleton as a zval; false = pending exception */
	[[nodiscard]] static bool contextZval(zend_object *context, zval &out)
	{
		if (UNEXPECTED(context == NULL)) return false;
		ZVAL_OBJ(&out, context);
		return true;
	}

	/* $s->getCurrentExpressionResultStorage()?->findExpressionResult($expr)
	 * — the stored result or null */
	static zv::Val findStoredResult(zval *s, zval *expr)
	{
		zv::Val storage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(s));
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		if (storage.isNull()) return zv::Val::null();
		return pt_expression_result_storage_find(storage.raw(), expr);
	}

	/* [$exprNode, $type] = $entry — the pair of a SpecifiedTypes entry
	 * (borrowed into out; null for a missing element, with PHP's warning);
	 * false = pending exception */
	[[nodiscard]] static bool destructurePair(zval *pair, zval &first, zval &second)
	{
		ZVAL_NULL(&first);
		ZVAL_NULL(&second);
		if (UNEXPECTED(Z_TYPE_P(pair) != IS_ARRAY)) return true;
		zval *zero = zend_hash_index_find(Z_ARRVAL_P(pair), 0);
		if (zero == NULL) {
			zend_error(E_WARNING, "Undefined array key 0");
			if (UNEXPECTED(EG(exception))) return false;
		} else {
			ZVAL_COPY_VALUE(&first, zero);
			derefInPlace(first);
		}
		zval *one = zend_hash_index_find(Z_ARRVAL_P(pair), 1);
		if (one == NULL) {
			zend_error(E_WARNING, "Undefined array key 1");
			if (UNEXPECTED(EG(exception))) return false;
		} else {
			ZVAL_COPY_VALUE(&second, one);
			derefInPlace(second);
		}
		return true;
	}

	/* the dereferenced copy of a borrowed zval (no addref: still borrowed) */
	static inline void derefInPlace(zval &value)
	{
		if (Z_ISREF(value)) {
			zval *inner = Z_REFVAL(value);
			ZVAL_COPY_VALUE(&value, inner);
		}
	}

	/* Mirrors nullsafeShortCircuitRuledOut(); false = pending exception */
	[[nodiscard]] bool nullsafeShortCircuitRuledOut(zval *s, zval *subjectResult, zval *type, zval *context, bool &out) const
	{
		bool isTrue;
		if (UNEXPECTED(!ctxTrue(context, isTrue))) return false;
		if (isTrue) {
			zend_long isNull = typeTrinary(type, PT_OP_IS_NULL, "isNull");
			if (UNEXPECTED(isNull < 0)) return false;
			if (isNull == PT_TRI_NO) {
				out = true;
				return true;
			}
			return subjectTypeExcludesNull(s, subjectResult, out);
		}
		bool isFalse;
		if (UNEXPECTED(!ctxFalse(context, isFalse))) return false;
		if (isFalse) {
			bool containsNull;
			if (UNEXPECTED(!pt_type_combinator_contains_null(type, containsNull))) return false;
			if (containsNull) {
				out = true;
				return true;
			}
			return subjectTypeExcludesNull(s, subjectResult, out);
		}

		out = false;
		return true;
	}

	/* ($s->nativeTypesPromoted ? $subjectResult->getNativeType() :
	 * $subjectResult->getType())->isNull()->no() */
	[[nodiscard]] static bool subjectTypeExcludesNull(zval *s, zval *subjectResult, bool &out)
	{
		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(s), promoted))) return false;
		zv::Val subjectType = promoted ? pt_expression_result_get_native_type(subjectResult) : pt_expression_result_get_type(subjectResult);
		if (UNEXPECTED(subjectType.isUndef())) return false;
		zend_long isNull = typeTrinary(subjectType.raw(), PT_OP_IS_NULL, "isNull");
		if (UNEXPECTED(isNull < 0)) return false;
		out = isNull == PT_TRI_NO;
		return true;
	}

	/* Mirrors createFirstNullsafeReceiverTypes(): a SpecifiedTypes or null */
	zv::Val createFirstNullsafeReceiverTypes(zval *s, zval *expr) const
	{
		zval current;
		ZVAL_COPY_VALUE(&current, expr);
		for (;;) {
			if (isA(&current, PT_CLASS_NULLSAFE_PROPERTY_FETCH) || isA(&current, PT_CLASS_NULLSAFE_METHOD_CALL)) {
				zv::Val stored = findStoredResult(s, &current);
				if (UNEXPECTED(stored.isUndef())) return zv::Val();
				zv::Val nullType = newNullType();
				if (UNEXPECTED(nullType.isUndef())) return zv::Val();
				zval falseZv;
				if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_false(), falseZv))) return zv::Val();

				return createSubjectTypes(s, &current, stored.isNull() ? NULL : stored.raw(), nullType.raw(), &falseZv);
			}

			if (isA(&current, PT_CLASS_PROPERTY_FETCH) || isA(&current, PT_CLASS_METHOD_CALL) || isA(&current, PT_CLASS_ARRAY_DIM_FETCH)) {
				zval *var = varOf(&current);
				if (UNEXPECTED(Z_TYPE_P(var) != IS_OBJECT)) return zv::Val::null();
				ZVAL_COPY_VALUE(&current, var);
				continue;
			}

			if (isA(&current, PT_CLASS_STATIC_PROPERTY_FETCH) || isA(&current, PT_CLASS_STATIC_CALL)) {
				zval *classNode = classOf(&current);
				if (isA(classNode, PT_CLASS_EXPR)) {
					ZVAL_COPY_VALUE(&current, classNode);
					continue;
				}
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();

			return zv::Val::null();
		}
	}

	/* whether a type is a ConstantIntegerType or a ConstantStringType */
	static bool isConstantIntegerOrString(zval *type)
	{
		return Z_TYPE_P(type) == IS_OBJECT && (instanceof_function(Z_OBJCE_P(type), pt_ce_constant_integer_type) || instanceof_function(Z_OBJCE_P(type), pt_ce_constant_string_type));
	}

	/* new IntersectionType([new ObjectWithoutClassType(), new HasPropertyType($name)]) */
	static zv::Val objectWithProperty(zend_string *propertyName)
	{
		zval objectZv, hasPropertyZv;
		if (UNEXPECTED(!pt_object_without_class_type_new(&objectZv))) return zv::Val();
		zv::Val object = zv::Val::adopt(objectZv);
		if (UNEXPECTED(!pt_has_property_type_new(&hasPropertyZv, propertyName))) return zv::Val();
		zv::Val hasProperty = zv::Val::adopt(hasPropertyZv);
		zv::Arr members = zv::Arr::create(2);
		members.push(std::move(object));
		members.push(std::move(hasProperty));
		zval intersectionZv;
		if (UNEXPECTED(!pt_intersection_type_new(&intersectionZv, members.raw()))) return zv::Val();
		return zv::Val::adopt(intersectionZv);
	}

	/* the constant-array offset removal of createIssetSingleSubjectNonTrueTypes():
	 * a SpecifiedTypes, or null when nothing is removed (the caller falls
	 * through to `new SpecifiedTypes()`) */
	zv::Val offsetRemovalTypes(zval *s, zval *var, zval *dim, zval *readType, zval *rootExpr) const
	{
		zv::Val varType = pt_type_call_callable(readType, 1, var);
		if (UNEXPECTED(varType.isUndef())) return zv::Val();
		if (Z_TYPE_P(varType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(varType.raw()), pt_ce_mixed_type)) return zv::Val::null();

		zv::Val dimType = pt_type_call_callable(readType, 1, dim);
		if (UNEXPECTED(dimType.isUndef())) return zv::Val();
		if (!isConstantIntegerOrString(dimType.raw())) return zv::Val::null();

		if (UNEXPECTED(Z_TYPE_P(varType.raw()) != IS_OBJECT)) return callOnNonObject("getConstantArrays", varType.raw());
		zv::Val constantArrays = pt_type_op(Z_OBJ_P(varType.raw()), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
		zv::Arr typesToRemove = zv::Arr::empty();
		bool hasOptionalNonNullableOffset = false;
		bool hasPossiblyNullOffsetValue = false;
		if (Z_TYPE_P(constantArrays.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
				zval *constantArray = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(constantArray) != IS_OBJECT)) return callOnNonObject("hasOffsetValueType", constantArray);
				zend_long hasOffset = pt_type_op_trinary(Z_OBJ_P(constantArray), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, dimType.raw());
				if (UNEXPECTED(hasOffset < 0)) return zv::Val();
				if (hasOffset == PT_TRI_NO) continue;
				zv::Val offsetValueType = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_OFFSET_VALUE_TYPE, 1, dimType.raw());
				if (UNEXPECTED(offsetValueType.isUndef())) return zv::Val();
				zend_long offsetIsNull = typeTrinary(offsetValueType.raw(), PT_OP_IS_NULL, "isNull");
				if (UNEXPECTED(offsetIsNull < 0)) return zv::Val();
				if (offsetIsNull != PT_TRI_NO) {
					hasPossiblyNullOffsetValue = true;
					continue;
				}

				if (hasOffset == PT_TRI_YES) {
					typesToRemove.push(zv::Ref(constantArray));
					continue;
				}

				hasOptionalNonNullableOffset = true;
			}
		}

		// !isset() on an optional key with a non-nullable value means the
		// key is absent - but only when no member can hold null at that
		// offset (the removal distributes over every union member).
		if (hasOptionalNonNullableOffset && !hasPossiblyNullOffsetValue) {
			zval hasOffsetZv;
			if (UNEXPECTED(!pt_has_offset_type_new(&hasOffsetZv, dimType.raw()))) return zv::Val();
			typesToRemove.push(zv::Val::adopt(hasOffsetZv));
		}

		if (zend_hash_num_elements(typesToRemove.table()) == 0) return zv::Val::null();

		zv::Val typeToRemove = unionOfList(typesToRemove);
		if (UNEXPECTED(typeToRemove.isUndef())) return zv::Val();

		zval falseZv;
		if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_false(), falseZv))) return zv::Val();
		zv::Val result = setRootExpr(createForSubject(var, typeToRemove.raw(), &falseZv, s, NULL), rootExpr);
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		zend_long hasValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(s), var);
		if (UNEXPECTED(hasValue < 0)) return zv::Val();
		if (hasValue == PT_TRI_MAYBE) {
			zv::Val issetNode = pt_type_new(PT_CLASS_ISSET_EXPR, 1, var);
			if (UNEXPECTED(issetNode.isUndef())) return zv::Val();
			zv::Val nullType = newNullType();
			if (UNEXPECTED(nullType.isUndef())) return zv::Val();
			zval truthyZv;
			if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_truthy(), truthyZv))) return zv::Val();
			result = unionWith(std::move(result), setRootExpr(createForSubject(issetNode.raw(), nullType.raw(), &truthyZv, s, NULL), rootExpr));
		}

		return result;
	}

	/* $parameters[$i] of the args loop ($i the arg's key); NULL when unset */
	static zval *parameterAt(zval *parameters, const zv::ArrayEntry &argEntry)
	{
		zend_string *key = argEntry.stringKeyOrNull();
		zval *parameter = key != NULL ? zend_symtable_find(Z_ARRVAL_P(parameters), key) : zend_hash_index_find(Z_ARRVAL_P(parameters), argEntry.indexKey());
		if (parameter == NULL) return NULL;
		ZVAL_DEREF(parameter);
		return Z_TYPE_P(parameter) == IS_NULL ? NULL : parameter;
	}

	/* $argsMap[$name][] = $value */
	static void appendToArgsMap(zv::Arr &argsMap, zend_string *name, zv::Val value)
	{
		argsMap.separate();
		zval *list = zend_symtable_find(argsMap.table(), name);
		if (list == NULL) {
			zval empty;
			array_init(&empty);
			list = zend_symtable_update(argsMap.table(), name, &empty);
		}
		ZVAL_DEREF(list);
		if (Z_TYPE_P(list) != IS_ARRAY) {
			zval_ptr_dtor(list);
			array_init(list);
		}
		SEPARATE_ARRAY(list);
		zval v = value.take();
		zend_hash_next_index_insert(Z_ARRVAL_P(list), &v);
	}

	/* the argsMap loop of specifyTypesFromAsserts(); false = pending exception */
	[[nodiscard]] bool mapArgumentsToParameters(zval *call, zval *parametersAcceptor, zval *parameters, bool variadicFallback, zv::Arr &argsMap) const
	{
		zv::Val args = callArgs(call);
		if (UNEXPECTED(args.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(args.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(args.raw()));
			return EG(exception) == NULL;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(args.raw())) {
			zval *arg = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) continue;
			if (zend_is_true(nodeProp(pt_dnh_arg_unpack_site, arg, PT_LC("unpack")))) continue;

			zv::Val paramName;
			zval *argName = nodeProp(pt_dnh_arg_name_site, arg, PT_LC("name"));
			zval *parameter = Z_TYPE_P(parameters) == IS_ARRAY ? parameterAt(parameters, entry) : NULL;
			if (Z_TYPE_P(argName) == IS_OBJECT) {
				zend_string *name = identifierString(argName);
				paramName = name != NULL ? zv::Val::string(name) : zv::Val::null();
			} else if (parameter != NULL) {
				if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) return !callOnNonObject("getName", parameter).isUndef();
				paramName = pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
				if (UNEXPECTED(paramName.isUndef())) return false;
			} else {
				if (!variadicFallback || Z_TYPE_P(parameters) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(parameters)) == 0) continue;
				bool variadic;
				if (UNEXPECTED(!callNoArgsBool(pt_dnh_acceptor_is_variadic_site, parametersAcceptor, "isVariadic", PT_LC("isvariadic"), variadic))) return false;
				if (!variadic) continue;
				zval *lastParameter = lastElement(Z_ARRVAL_P(parameters));
				if (UNEXPECTED(lastParameter == NULL || Z_TYPE_P(lastParameter) != IS_OBJECT)) return !callOnNonObject("getName", lastParameter != NULL ? lastParameter : &EG(uninitialized_zval)).isUndef();
				paramName = pt_type_call(Z_OBJ_P(lastParameter), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(paramName.isUndef())) return false;
			}
			if (UNEXPECTED(Z_TYPE_P(paramName.raw()) != IS_STRING)) {
				zend_throw_error(NULL, "phpstan_turbo: a parameter name is not a string");
				return false;
			}

			appendToArgsMap(argsMap, Z_STR_P(paramName.raw()), zv::Val::copyOf(zv::Ref(nodeProp(pt_dnh_arg_value_site, arg, PT_LC("value")))));
		}
		return true;
	}

	/* array_last($array) (dereferenced); NULL for an empty array */
	static zval *lastElement(HashTable *table)
	{
		for (uint32_t i = table->nNumUsed; i > 0; i--) {
			zval *value = HT_IS_PACKED(table) ? &table->arPacked[i - 1] : &table->arData[i - 1].val;
			if (Z_TYPE_P(value) == IS_UNDEF) continue;
			ZVAL_DEREF(value);
			return value;
		}
		return NULL;
	}

	/* $conditionalParameterName !== '$' . $paramName */
	static bool isDollarName(zval *conditionalParameterName, zval *paramName)
	{
		if (Z_TYPE_P(conditionalParameterName) != IS_STRING) return false;
		zend_string *expected = Z_STR_P(conditionalParameterName);
		zend_string *name = Z_TYPE_P(paramName) == IS_STRING ? Z_STR_P(paramName) : ZSTR_EMPTY_ALLOC();
		return ZSTR_LEN(expected) == ZSTR_LEN(name) + 1 && ZSTR_VAL(expected)[0] == '$' && memcmp(ZSTR_VAL(expected) + 1, ZSTR_VAL(name), ZSTR_LEN(name)) == 0;
	}

	/* Mirrors getConditionalSpecifiedTypes(). */
	zv::Val getConditionalSpecifiedTypes(zval *conditionalType, zval *leftType, zval *rightType, zval *scope, zval *argumentExpr) const
	{
		zend_object *conditional = Z_OBJ_P(conditionalType);
		zv::Val targetType = pt_type_call(conditional, PT_LC("gettarget"), 0, NULL);
		if (UNEXPECTED(targetType.isUndef())) return zv::Val();
		zv::Val ifType = pt_type_call(conditional, PT_LC("getif"), 0, NULL);
		if (UNEXPECTED(ifType.isUndef())) return zv::Val();
		zv::Val elseType = pt_type_call(conditional, PT_LC("getelse"), 0, NULL);
		if (UNEXPECTED(elseType.isUndef())) return zv::Val();

		bool literalArgument = isA(argumentExpr, PT_CLASS_SCALAR);
		if (!literalArgument && isA(argumentExpr, PT_CLASS_CONST_FETCH)) {
			zend_string *constantName = identifierString(nameOf(argumentExpr));
			literalArgument = constantName != NULL && (zend_string_equals_literal_ci(constantName, "true") || zend_string_equals_literal_ci(constantName, "false") || zend_string_equals_literal_ci(constantName, "null"));
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();
		if (literalArgument && (instanceof_function(Z_OBJCE_P(ifType.raw()), pt_ce_never_type) || instanceof_function(Z_OBJCE_P(elseType.raw()), pt_ce_never_type))) return zv::Val::null();

		zval contextZv;
		bool firstYes;
		if (UNEXPECTED(!isSuperTypeOfYes(leftType, ifType.raw(), firstYes))) return zv::Val();
		bool secondYes = false;
		if (firstYes && UNEXPECTED(!isSuperTypeOfYes(rightType, elseType.raw(), secondYes))) return zv::Val();
		if (firstYes && secondYes) {
			bool negated;
			if (UNEXPECTED(!conditionalIsNegated(conditional, negated))) return zv::Val();
			if (UNEXPECTED(!contextZval(negated ? pt_type_specifier_context_create_false() : pt_type_specifier_context_create_true(), contextZv))) return zv::Val();
		} else {
			bool thirdYes;
			if (UNEXPECTED(!isSuperTypeOfYes(leftType, elseType.raw(), thirdYes))) return zv::Val();
			bool fourthYes = false;
			if (thirdYes && UNEXPECTED(!isSuperTypeOfYes(rightType, ifType.raw(), fourthYes))) return zv::Val();
			if (!(thirdYes && fourthYes)) return zv::Val::null();
			bool negated;
			if (UNEXPECTED(!conditionalIsNegated(conditional, negated))) return zv::Val();
			if (UNEXPECTED(!contextZval(negated ? pt_type_specifier_context_create_true() : pt_type_specifier_context_create_false(), contextZv))) return zv::Val();
		}

		zv::Val argumentResult = findStoredResult(scope, argumentExpr);
		if (UNEXPECTED(argumentResult.isUndef())) return zv::Val();
		if (argumentResult.isNull() && isA(argumentExpr, PT_CLASS_CALL_LIKE)) {
			bool mayRemember;
			if (UNEXPECTED(!mayRememberCallSubject(scope, argumentExpr, mayRemember))) return zv::Val();
			if (!mayRemember) return zv::Val::null();
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();
		zv::Val specifiedTypes = createSubjectTypes(scope, argumentExpr, argumentResult.isNull() ? NULL : argumentResult.raw(), targetType.raw(), &contextZv);
		if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();

		zend_long targetTrue = typeTrinaryByName(targetType.raw(), "isTrue", PT_LC("istrue"));
		if (UNEXPECTED(targetTrue < 0)) return zv::Val();
		bool targetIsBool = targetTrue == PT_TRI_YES;
		if (!targetIsBool) {
			zend_long targetFalse = typeTrinaryByName(targetType.raw(), "isFalse", PT_LC("isfalse"));
			if (UNEXPECTED(targetFalse < 0)) return zv::Val();
			targetIsBool = targetFalse == PT_TRI_YES;
		}
		if (targetIsBool) {
			zv::Val context = zv::Val::copyOf(zv::Ref(&contextZv));
			zend_long targetFalse = typeTrinaryByName(targetType.raw(), "isFalse", PT_LC("isfalse"));
			if (UNEXPECTED(targetFalse < 0)) return zv::Val();
			if (targetFalse == PT_TRI_YES) {
				context = pt_type_specifier_context_negate(Z_OBJ_P(context.raw()));
				if (UNEXPECTED(context.isUndef())) return zv::Val();
			}

			specifiedTypes = unionWith(std::move(specifiedTypes), specifyTypesForNode(scope, argumentExpr, context.raw()));
		}

		return specifiedTypes;
	}

	/* $conditionalType->isNegated(); false = pending exception */
	[[nodiscard]] static bool conditionalIsNegated(zend_object *conditional, bool &out)
	{
		zv::Val value = pt_type_call(conditional, PT_LC("isnegated"), 0, NULL);
		if (UNEXPECTED(value.isUndef())) return false;
		out = zend_is_true(value.raw());
		return true;
	}

	/* Mirrors mayRememberCallSubject(); false = pending exception */
	[[nodiscard]] bool mayRememberCallSubject(zval *scope, zval *expr, bool &out) const
	{
		zv::Val hasSideEffects;
		if (isA(expr, PT_CLASS_FUNC_CALL) && isA(nameOf(expr), PT_CLASS_NAME)) {
			zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
			bool hasFunction;
			if (UNEXPECTED(!reflectionProviderHasFunction(reflectionProvider, nameOf(expr), scope, hasFunction))) return false;
			if (!hasFunction) {
				out = false;
				return true;
			}
			zv::Val function = reflectionProviderGetFunction(reflectionProvider, nameOf(expr), scope);
			if (UNEXPECTED(function.isUndef())) return false;
			hasSideEffects = callNoArgs(pt_dnh_function_has_side_effects_site, function.raw(), "hasSideEffects", PT_LC("hassideeffects"));
		} else if (isA(expr, PT_CLASS_METHOD_CALL) && isA(nameOf(expr), PT_CLASS_IDENTIFIER)) {
			zval *var = varOf(expr);
			if (UNEXPECTED(Z_TYPE_P(var) != IS_OBJECT)) {
				zend_type_error("PHPStan\\Analyser\\MutatingScope::getStateType(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(var));
				return false;
			}
			zv::Val stateType = pt_mutating_scope_get_state_type(Z_OBJ_P(scope), Z_OBJ_P(var));
			if (UNEXPECTED(stateType.isUndef())) return false;
			zend_string *methodName = identifierString(nameOf(expr));
			if (UNEXPECTED(methodName == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: an Identifier without a name");
				return false;
			}
			zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), stateType.raw(), methodName);
			if (UNEXPECTED(methodReflection.isUndef())) return false;
			if (methodReflection.isNull()) {
				out = false;
				return true;
			}
			hasSideEffects = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
		} else if (isA(expr, PT_CLASS_STATIC_CALL) && isA(nameOf(expr), PT_CLASS_IDENTIFIER) && isA(classOf(expr), PT_CLASS_NAME)) {
			zv::Val classType = pt_mutating_scope_resolve_type_by_name(Z_OBJ_P(scope), Z_OBJ_P(classOf(expr)));
			if (UNEXPECTED(classType.isUndef())) return false;
			zend_string *methodName = identifierString(nameOf(expr));
			if (UNEXPECTED(methodName == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: an Identifier without a name");
				return false;
			}
			zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), classType.raw(), methodName);
			if (UNEXPECTED(methodReflection.isUndef())) return false;
			if (methodReflection.isNull()) {
				out = false;
				return true;
			}
			hasSideEffects = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
		} else {
			if (UNEXPECTED(EG(exception))) return false;
			out = false;
			return true;
		}
		if (UNEXPECTED(hasSideEffects.isUndef())) return false;

		zend_long value = pt_type_trinary_value(hasSideEffects.raw());
		if (UNEXPECTED(value < 0)) return false;
		if (value == PT_TRI_YES) {
			out = false;
			return true;
		}

		out = rememberPossiblyImpureFunctionValues() || value == PT_TRI_NO;
		return true;
	}

	/* static function (Expr $e) use ($chainResults, $s): Type — captures:
	 * $chainResults, $s */
	static void chainTypeReaderBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zval *e = &argv[0];
		ZVAL_DEREF(e);
		if (UNEXPECTED(Z_TYPE_P(e) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::{closure}(): Argument #1 ($e) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(e));
			return;
		}
		zval *chainResults = &captures[0];
		zval *s = &captures[1];
		zval *result = Z_TYPE_P(chainResults) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(chainResults), (zend_ulong) Z_OBJ_HANDLE_P(e)) : NULL;
		if (result != NULL) {
			ZVAL_DEREF(result);
		}
		if (result == NULL || Z_TYPE_P(result) == IS_NULL) {
			pt_throw_should_not_happen();
			return;
		}
		if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
			(void) callOnNonObject("getTypeOnScope", result);
			return;
		}

		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(s), promoted))) return;
		zv::Val type = pt_expression_result_get_type_on_scope(result, s, promoted);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* $getArgType($expr) — the twin's `static function (Expr $expr) use
	 * ($scope): Type`, run directly */
	static zv::Val argType(zval *scope, zval *expr)
	{
		if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::{closure}(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(expr));
			return zv::Val();
		}
		if (isA(expr, PT_CLASS_TYPE_EXPR)) return pt_type_call(Z_OBJ_P(expr), PT_LC("getexprtype"), 0, NULL);
		if (UNEXPECTED(EG(exception))) return zv::Val();

		zv::Val result = findStoredResult(scope, expr);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (!result.isNull()) {
			bool promoted;
			if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), promoted))) return zv::Val();
			return pt_expression_result_get_type_on_scope(result.raw(), scope, promoted);
		}

		return pt_mutating_scope_get_state_type(Z_OBJ_P(scope), Z_OBJ_P(expr));
	}

	/* static function (string $parameterName) use ($argsMap, $getArgType): ?Type
	 * — the subject lookup of ConditionalTypeForParameter::resolveInType();
	 * captures: $argsMap, $scope (the $getArgType closure's own capture) */
	static void assertSubjectTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zval *argsMap = &captures[0];
		zval *scope = &captures[1];
		zval *parameterName = &argv[0];
		ZVAL_DEREF(parameterName);
		if (UNEXPECTED(Z_TYPE_P(parameterName) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::{closure}(): Argument #1 ($parameterName) must be of type string, %s given", zend_zval_value_name(parameterName));
			return;
		}
		/* $argsMap[substr($parameterName, 1)] ?? null */
		zv::Val lookupName = withoutFirstCharacter(parameterName);
		if (UNEXPECTED(lookupName.isUndef())) return;
		zval *exprs = Z_TYPE_P(argsMap) == IS_ARRAY ? zend_symtable_find(Z_ARRVAL_P(argsMap), Z_STR_P(lookupName.raw())) : NULL;
		if (exprs != NULL) {
			ZVAL_DEREF(exprs);
		}
		if (exprs == NULL || Z_TYPE_P(exprs) == IS_NULL) {
			RETVAL_NULL();
			return;
		}
		if (UNEXPECTED(Z_TYPE_P(exprs) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(exprs));
			return;
		}
		/* TypeCombinator::union(...array_map($getArgType, $parameterExprs)) */
		zv::Arr argTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(exprs)));
		for (zv::ArrayEntry entry : zv::ArrRef(exprs)) {
			zv::Val one = argType(scope, entry.value().deref().raw());
			if (UNEXPECTED(one.isUndef())) return;
			argTypes.push(std::move(one));
		}
		zv::Val unioned = unionOfList(argTypes);
		if (UNEXPECTED(unioned.isUndef())) return;
		unioned.intoReturnValue(return_value);
	}

	/* static function (Type $type, callable $traverse) use ($templateTypeMap,
	 * &$containsUnresolvedTemplate) — captures: $templateTypeMap,
	 * &$containsUnresolvedTemplate */
	static void unresolvedTemplateTraverseBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *templateTypeMap = &captures[0];
		zval *containsUnresolvedTemplate = &captures[1];
		zval *type = &argv[0];
		ZVAL_DEREF(type);
		zval *traverse = &argv[1];

		if (isA(type, PT_CLASS_TEMPLATE_TYPE)) {
			zv::Val templateScope = pt_type_call(Z_OBJ_P(type), PT_LC("getscope"), 0, NULL);
			if (UNEXPECTED(templateScope.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(templateScope.raw()) != IS_OBJECT)) {
				(void) callOnNonObject("getClassName", templateScope.raw());
				return;
			}
			zv::Val className = pt_type_call(Z_OBJ_P(templateScope.raw()), PT_LC("getclassname"), 0, NULL);
			if (UNEXPECTED(className.isUndef())) return;
			if (!className.isNull()) {
				zv::Val name = pt_type_call(Z_OBJ_P(type), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(name.isUndef())) return;
				if (UNEXPECTED(Z_TYPE_P(templateTypeMap) != IS_OBJECT)) {
					(void) callOnNonObject("getType", templateTypeMap);
					return;
				}
				zv::Val resolvedType = pt_type_call(Z_OBJ_P(templateTypeMap), PT_LC("gettype"), 1, name.raw());
				if (UNEXPECTED(resolvedType.isUndef())) return;
				bool unresolved = resolvedType.isNull();
				if (!unresolved) {
					zv::Val bound = pt_type_call(Z_OBJ_P(type), PT_LC("getbound"), 0, NULL);
					if (UNEXPECTED(bound.isUndef())) return;
					if (UNEXPECTED(Z_TYPE_P(bound.raw()) != IS_OBJECT)) {
						(void) callOnNonObject("equals", bound.raw());
						return;
					}
					zv::Val equals = pt_type_op(Z_OBJ_P(bound.raw()), PT_OP_EQUALS, 1, resolvedType.raw());
					if (UNEXPECTED(equals.isUndef())) return;
					unresolved = zend_is_true(equals.raw());
				}
				if (unresolved) {
					zval *target = containsUnresolvedTemplate;
					ZVAL_DEREF(target);
					zval_ptr_dtor(target);
					ZVAL_TRUE(target);
					ZVAL_COPY(return_value, type);
					return;
				}
			}
		} else if (UNEXPECTED(EG(exception))) {
			return;
		}

		zval traversedZv;
		if (UNEXPECTED(!pt_type_traverser_traverse(&traversedZv, traverse, type))) return;
		ZVAL_COPY_VALUE(return_value, &traversedZv);
	}
};

} // namespace phpstanturbo

using phpstanturbo::DefaultNarrowingHelper;

/* {{{ direct entries (support.h): the native body for the native class (the
 * twin is final), the method by name for anything else */

namespace {

inline bool isNativeHelper(zval *helper)
{
	return EXPECTED(Z_OBJCE_P(helper) == pt_ce_default_narrowing_helper);
}

inline zval *orNull(zval *value, zval &nullZv)
{
	if (value != NULL) return value;
	ZVAL_NULL(&nullZv);
	return &nullZv;
}

inline zval *nullable(zval *value)
{
	return value != NULL && Z_TYPE_P(value) != IS_NULL ? value : NULL;
}

} // namespace

zv::Val pt_default_narrowing_helper_specify_types_for_node(zval *helper, zval *scope, zval *node, zval *context)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).specifyTypesForNode(scope, node, context);
	zv::Args argv{scope, node, context};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifytypesfornode"), 3, argv);
}

zv::Val pt_default_narrowing_helper_specify_default_types(zval *helper, zval *expr, zval *context)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).specifyDefaultTypes(expr, context);
	zv::Args argv{expr, context};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifydefaulttypes"), 2, argv);
}

zv::Val pt_default_narrowing_helper_specify_default_types_with_plain_twin(zval *helper, zval *expr, zval *exprResult, zval *context, zval *s)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).specifyDefaultTypesWithPlainTwin(expr, nullable(exprResult), context, s);
	zval nullZv;
	zv::Args argv{expr, orNull(exprResult, nullZv), context, s};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifydefaulttypeswithplaintwin"), 4, argv);
}

zv::Val pt_default_narrowing_helper_to_sure_types(zval *helper, zval *types, zval *evaluationScope)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).toSureTypes(types, evaluationScope);
	zv::Args argv{types, evaluationScope};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("tosuretypes"), 2, argv);
}

zv::Val pt_default_narrowing_helper_create_subject_types(zval *helper, zval *s, zval *subject, zval *subjectResult, zval *type, zval *context)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).createSubjectTypes(s, subject, nullable(subjectResult), type, context);
	zval nullZv;
	zv::Args argv{s, subject, orNull(subjectResult, nullZv), type, context};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("createsubjecttypes"), 5, argv);
}

zv::Val pt_default_narrowing_helper_create_subject_types_from_result_state(zval *helper, zval *s, zval *subject, zval *subjectResult, zval *type, zval *context)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).createSubjectTypesFromResultState(s, subject, nullable(subjectResult), type, context);
	zval nullZv;
	zv::Args argv{s, subject, orNull(subjectResult, nullZv), type, context};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("createsubjecttypesfromresultstate"), 5, argv);
}

zv::Val pt_default_narrowing_helper_specify_default_types_with_nullsafe_fan(zval *helper, zval *expr, zval *context, zval *beforeScope, bool nativeTypesPromoted)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).specifyDefaultTypesWithNullsafeFan(expr, context, beforeScope, nativeTypesPromoted);
	zv::Args argv{expr, context, beforeScope, nativeTypesPromoted};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifydefaulttypeswithnullsafefan"), 4, argv);
}

zv::Val pt_default_narrowing_helper_create_nullsafe_receiver_only_types(zval *helper, zval *s, zval *subject, zval *subjectResult, zval *type, zval *context)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).createNullsafeReceiverOnlyTypes(s, subject, nullable(subjectResult), type, context);
	zval nullZv;
	zv::Args argv{s, subject, orNull(subjectResult, nullZv), type, context};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("createnullsafereceiveronlytypes"), 5, argv);
}

bool pt_default_narrowing_helper_call_may_have_been_skipped(zval *helper, zval *receiverResult, zval *receiverType, zval *context, bool &out)
{
	if (receiverResult != NULL && Z_TYPE_P(receiverResult) == IS_NULL) receiverResult = NULL;
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).callMayHaveBeenSkipped(receiverResult, receiverType, context, out);
	zval nullZv;
	zv::Args argv{orNull(receiverResult, nullZv), receiverType, context};
	zv::Val result = pt_type_call(Z_OBJ_P(helper), PT_LC("callmayhavebeenskipped"), 3, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

zv::Val pt_default_narrowing_helper_create_for_subject(zval *helper, zval *subject, zval *type, zval *context, zval *scope, zval *resultFor)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).createForSubject(subject, type, context, scope, nullable(resultFor));
	zval nullZv;
	zv::Args argv{subject, type, context, scope, orNull(resultFor, nullZv)};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("createforsubject"), 5, argv);
}

bool pt_default_narrowing_helper_capture_chain_results(zval *helper, zval *node, zval *storage, zval *chainResults)
{
	if (isNativeHelper(helper)) {
		zval *target = chainResults;
		ZVAL_DEREF(target);
		return DefaultNarrowingHelper(Z_OBJ_P(helper)).captureChainResults(node, storage, target);
	}
	zv::Args argv{node, storage, chainResults};
	zv::Val result = pt_type_call(Z_OBJ_P(helper), PT_LC("capturechainresults"), 3, argv);
	return !result.isUndef();
}

zv::Val pt_default_narrowing_helper_build_chain_type_reader(zval *helper, zval *chainResults, zval *s)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).buildChainTypeReader(chainResults, s);
	zv::Args argv{chainResults, s};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("buildchaintypereader"), 2, argv);
}

zv::Val pt_default_narrowing_helper_create_isset_truthy_chain_types(zval *helper, zval *s, zval *issetExpr, zval *readType, zval *rootExpr, zval *context)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).createIssetTruthyChainTypes(s, issetExpr, readType, rootExpr, context);
	zv::Args argv{s, issetExpr, readType, rootExpr, context};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("createissettruthychaintypes"), 5, argv);
}

zv::Val pt_default_narrowing_helper_create_isset_single_subject_non_true_types(zval *helper, zval *s, zval *issetExpr, zval *varResult, zval *readType, zval *context, zval *rootExpr)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).createIssetSingleSubjectNonTrueTypes(s, issetExpr, varResult, readType, context, rootExpr);
	zv::Args argv{s, issetExpr, varResult, readType, context, rootExpr};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("createissetsinglesubjectnontruetypes"), 6, argv);
}

zv::Val pt_default_narrowing_helper_specify_types_from_asserts(zval *helper, zval *context, zval *call, zval *assertions, zval *parametersAcceptor, zval *scope)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).specifyTypesFromAsserts(context, call, assertions, parametersAcceptor, scope);
	zv::Args argv{context, call, assertions, parametersAcceptor, scope};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifytypesfromasserts"), 5, argv);
}

zv::Val pt_default_narrowing_helper_specify_types_from_conditional_return_type(zval *helper, zval *context, zval *call, zval *parametersAcceptor, zval *scope)
{
	if (isNativeHelper(helper)) return DefaultNarrowingHelper(Z_OBJ_P(helper)).specifyTypesFromConditionalReturnType(context, call, parametersAcceptor, scope);
	zv::Args argv{context, call, parametersAcceptor, scope};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifytypesfromconditionalreturntype"), 4, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_DNH_THIS DefaultNarrowingHelper(Z_OBJ_P(ZEND_THIS))

void pt_register_default_narrowing_helper()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper");
	ptdecl::DefaultNarrowingHelper::declareClass(cls);
	ptdecl::DefaultNarrowingHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor (and pairs the
	 * #[AutowiredParameter] by name) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exprPrinter, *reflectionProvider;
		bool rememberPossiblyImpureFunctionValues;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Obj>(execute_data, exprPrinter, rememberPossiblyImpureFunctionValues, reflectionProvider)) RETURN_THROWS();
		PT_DNH_THIS.construct(exprPrinter, rememberPossiblyImpureFunctionValues, reflectionProvider);
	});

	cls.method(sigs::specifyTypesForNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node, *context;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, node, context)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.specifyTypesForNode(scope, node, context));
	});

	cls.method(sigs::specifyDefaultTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *context;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expr, context)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.specifyDefaultTypes(expr, context));
	});

	cls.method(sigs::specifyDefaultTypesWithPlainTwin, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *exprResult, *context, *s;
		if (!zp::parse<zp::Obj, zp::ObjOrNull, zp::Obj, zp::Obj>(execute_data, expr, exprResult, context, s)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.specifyDefaultTypesWithPlainTwin(expr, exprResult, context, s));
	});

	cls.method(sigs::toSureTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types, *evaluationScope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, types, evaluationScope)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.toSureTypes(types, evaluationScope));
	});

	cls.method(sigs::createSubjectTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *s, *subject, *subjectResult, *type, *context;
		if (!zp::parse<zp::Obj, zp::Obj, zp::ObjOrNull, zp::Obj, zp::Obj>(execute_data, s, subject, subjectResult, type, context)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.createSubjectTypes(s, subject, subjectResult, type, context));
	});

	cls.method(sigs::createSubjectTypesFromResultState, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *s, *subject, *subjectResult, *type, *context;
		if (!zp::parse<zp::Obj, zp::Obj, zp::ObjOrNull, zp::Obj, zp::Obj>(execute_data, s, subject, subjectResult, type, context)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.createSubjectTypesFromResultState(s, subject, subjectResult, type, context));
	});

	cls.method(sigs::specifyDefaultTypesWithNullsafeFan, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *context, *beforeScope;
		bool nativeTypesPromoted;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Bool>(execute_data, expr, context, beforeScope, nativeTypesPromoted)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.specifyDefaultTypesWithNullsafeFan(expr, context, beforeScope, nativeTypesPromoted));
	});

	cls.method(sigs::createNullsafeReceiverOnlyTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *s, *subject, *subjectResult, *type, *context;
		if (!zp::parse<zp::Obj, zp::Obj, zp::ObjOrNull, zp::Obj, zp::Obj>(execute_data, s, subject, subjectResult, type, context)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.createNullsafeReceiverOnlyTypes(s, subject, subjectResult, type, context));
	});

	cls.method(sigs::callMayHaveBeenSkipped, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *receiverResult, *receiverType, *context;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Obj>(execute_data, receiverResult, receiverType, context)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!PT_DNH_THIS.callMayHaveBeenSkipped(receiverResult, receiverType, context, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::createForSubject, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subject, *type, *context, *scope, *resultFor = NULL;
		ZEND_PARSE_PARAMETERS_START(4, 5)
			Z_PARAM_OBJECT(subject)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(resultFor, zend_ce_closure)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_DNH_THIS.createForSubject(subject, type, context, scope, resultFor));
	});

	cls.method(sigs::captureChainResults, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node, *storage, *chainResults;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(node)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ARRAY_EX(chainResults, 0, 1)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!PT_DNH_THIS.captureChainResults(node, storage, chainResults))) RETURN_THROWS();
	});

	cls.method(sigs::buildChainTypeReader, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *chainResults, *s;
		if (!zp::parse<zp::Arr, zp::Obj>(execute_data, chainResults, s)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.buildChainTypeReader(chainResults, s));
	});

	cls.method(sigs::createIssetTruthyChainTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *s, *issetExpr, *readType, *rootExpr, *context;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(s)
			Z_PARAM_OBJECT(issetExpr)
			Z_PARAM_OBJECT_OF_CLASS(readType, zend_ce_closure)
			Z_PARAM_OBJECT(rootExpr)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_DNH_THIS.createIssetTruthyChainTypes(s, issetExpr, readType, rootExpr, context));
	});

	cls.method(sigs::createIssetSingleSubjectNonTrueTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *s, *issetExpr, *varResult, *readType, *context, *rootExpr;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(s)
			Z_PARAM_OBJECT(issetExpr)
			Z_PARAM_OBJECT(varResult)
			Z_PARAM_ZVAL(readType)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(rootExpr)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!zend_is_callable(readType, 0, NULL))) {
			zend_argument_type_error(4, "must be of type callable, %s given", zend_zval_value_name(readType));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(PT_DNH_THIS.createIssetSingleSubjectNonTrueTypes(s, issetExpr, varResult, readType, context, rootExpr));
	});

	cls.method(sigs::specifyTypesFromAsserts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *context, *call, *assertions, *parametersAcceptor, *scope;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, context, call, assertions, parametersAcceptor, scope)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.specifyTypesFromAsserts(context, call, assertions, parametersAcceptor, scope));
	});

	cls.method(sigs::specifyTypesFromConditionalReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *context, *call, *parametersAcceptor, *scope;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, context, call, parametersAcceptor, scope)) RETURN_THROWS();
		PT_RETURN_VAL(PT_DNH_THIS.specifyTypesFromConditionalReturnType(context, call, parametersAcceptor, scope));
	});

	cls.shadow(&pt_ce_default_narrowing_helper);
}

#undef PT_DNH_THIS

/* }}} */
