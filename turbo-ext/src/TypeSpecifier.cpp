/*
 * PHPStanTurbo\TypeSpecifier — native implementation of
 * PHPStan\Analyser\TypeSpecifier.
 *
 * A DI service built by TypeSpecifierFactory (#[AutowiredService] with a
 * factory) and handed to the type-specifying extensions: the constructor keeps
 * the twin's exact arginfo, the state lives in the twin's property slots
 * (generated declarations), and every public method keeps its @api signature.
 *
 * specifyTypesInCondition() resolves the handler through the native
 * ExprHandlerRegistry and hands a handled node to the native MutatingScope;
 * create() builds the narrowing natively over the scope's getType() /
 * getMethodReflection() / resolveTypeByName() entries, the Type ops, the
 * native ExprPrinter and SpecifiedTypes, calling the reflection provider, the
 * member reflections and NullsafeOperatorHelper by name. The extension lookups
 * by class read ExtensionClassHelper's static memo natively
 * (pt_extension_class_helper_get_extension_class_names) and merge the lists
 * without a frame.
 *
 * MutatingScope::filterByTruthyValue() / filterByFalseyValue() reach
 * specifyTypesInCondition() through pt_type_specifier_specify_types_in_condition().
 */

#include "support.h"
#include "generated/TypeSpecifier.h"

namespace slots = ptdecl::TypeSpecifier::slot;
namespace sigs = ptdecl::TypeSpecifier::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "ParserVisitors.h"

zend_class_entry *pt_ce_type_specifier = NULL;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_ts_assign_var = PT_NODE_PROP(PT_CLASS_ASSIGN_EXPR, "var");
NodeProp pt_ts_assign_expr = PT_NODE_PROP(PT_CLASS_ASSIGN_EXPR, "expr");
NodeProp pt_ts_coalesce_assign_var = PT_NODE_PROP(PT_CLASS_COALESCE_ASSIGN_OP_EXPR, "var");
NodeProp pt_ts_coalesce_left = PT_NODE_PROP(PT_CLASS_COALESCE_EXPR, "left");
NodeProp pt_ts_coalesce_right = PT_NODE_PROP(PT_CLASS_COALESCE_EXPR, "right");
NodeProp pt_ts_func_call_name = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name");
NodeProp pt_ts_method_call_name = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "name");
NodeProp pt_ts_method_call_var = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "var");
NodeProp pt_ts_static_call_name = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "name");
NodeProp pt_ts_static_call_class = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "class");
NodeProp pt_ts_nullsafe_property_fetch_var = PT_NODE_PROP(PT_CLASS_NULLSAFE_PROPERTY_FETCH, "var");
NodeProp pt_ts_nullsafe_property_fetch_name = PT_NODE_PROP(PT_CLASS_NULLSAFE_PROPERTY_FETCH, "name");
NodeProp pt_ts_nullsafe_method_call_var = PT_NODE_PROP(PT_CLASS_NULLSAFE_METHOD_CALL, "var");
NodeProp pt_ts_nullsafe_method_call_name = PT_NODE_PROP(PT_CLASS_NULLSAFE_METHOD_CALL, "name");
NodeProp pt_ts_nullsafe_method_call_args = PT_NODE_PROP(PT_CLASS_NULLSAFE_METHOD_CALL, "args");
NodeProp pt_ts_property_fetch_var = PT_NODE_PROP(PT_CLASS_PROPERTY_FETCH, "var");
NodeProp pt_ts_array_dim_fetch_var = PT_NODE_PROP(PT_CLASS_ARRAY_DIM_FETCH, "var");
NodeProp pt_ts_static_property_fetch_class = PT_NODE_PROP(PT_CLASS_STATIC_PROPERTY_FETCH, "class");

/* {{{ the analyser classes still PHP: one local helper per call */

/* NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($expr) */
zv::Val nullsafeShortcircuitedExpr(zval *expr)
{
	return pt_type_call_static(PT_CLASS_NULLSAFE_OPERATOR_HELPER, PT_LC("getnullsafeshortcircuitedexpr"), 1, expr);
}

/* }}} */

/* whether the value is an object of the class-map class; false = pending
 * exception (an unresolvable class-map entry) */
[[nodiscard]] bool isA(zval *value, int classIdx, bool &out)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	out = value != NULL && Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

/* new SpecifiedTypes([], []) */
zv::Val emptySpecifiedTypes()
{
	return pt_specified_types_new(NULL, NULL);
}

/* (new SpecifiedTypes([], []))->setRootExpr($expr) */
zv::Val emptySpecifiedTypesWithRoot(zval *expr)
{
	zv::Val types = emptySpecifiedTypes();
	if (UNEXPECTED(types.isUndef())) return zv::Val();
	return pt_specified_types_set_root_expr(Z_OBJ_P(types.raw()), expr);
}

/* $result->setRootExpr($expr) of a create() result */
zv::Val withRoot(zv::Val types, zval *expr)
{
	if (UNEXPECTED(types.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(types.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(types.raw()));
		return zv::Val();
	}
	return pt_specified_types_set_root_expr(Z_OBJ_P(types.raw()), expr);
}

/* $scope->getType($expr) on a Scope; UNDEF = pending exception */
zv::Val scopeType(zval *scope, zval *expr)
{
	if (UNEXPECTED(expr == NULL || Z_TYPE_P(expr) != IS_OBJECT)) {
		zend_type_error("PHPStan\\Analyser\\Scope::getType(): Argument #1 ($node) must be of type PhpParser\\Node\\Expr, %s given", expr != NULL ? zend_zval_value_name(expr) : "null");
		return zv::Val();
	}
	zv::Val type = pt_mutating_scope_get_type(Z_OBJ_P(scope), expr);
	if (UNEXPECTED(type.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: Scope::getType() must return a Type, %s returned", zend_zval_value_name(type.raw()));
		return zv::Val();
	}
	return type;
}

/* $object->method()->yes() / ->no() of a TrinaryLogic-returning method; the
 * PT_TRI_* value, -1 = pending exception */
zend_long trinaryOf(zval *object, const char *lcname, size_t len, const char *name)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return -1;
	}
	return pt_type_call_trinary(Z_OBJ_P(object), lcname, len, 0, NULL);
}

/* array_merge([], ...$lists) of arrays (string keys overwrite, integer keys
 * renumber); the immutable empty array when nothing is merged, and — as
 * array_merge() does for two arguments of which one is empty — the one list
 * itself when it is a list or has string keys only */
zv::Val arrayMergeAll(zval *lists, uint32_t count)
{
	if (count == 1) {
		HashTable *only = Z_ARRVAL(lists[0]);
		if (HT_IS_PACKED(only)) {
			if (HT_IS_WITHOUT_HOLES(only)) return zv::Val::copyOf(zv::Ref(&lists[0]));
		} else {
			bool stringKeysOnly = true;
			for (zv::ArrayEntry entry : zv::TableRef(only)) {
				if (!entry.hasStringKey()) {
					stringKeysOnly = false;
					break;
				}
			}
			if (stringKeysOnly) return zv::Val::copyOf(zv::Ref(&lists[0]));
		}
	}
	uint32_t hint = 0;
	for (uint32_t i = 0; i < count; i++) {
		hint += zend_hash_num_elements(Z_ARRVAL(lists[i]));
	}
	if (hint == 0) return zv::Val(zv::Arr::empty());
	zv::Arr merged = zv::Arr::create(hint);
	for (uint32_t i = 0; i < count; i++) {
		for (zv::ArrayEntry entry : zv::ArrRef(&lists[i])) {
			zval *value = entry.value().raw();
			/* array_merge() unwraps a reference nobody else holds */
			if (Z_ISREF_P(value) && Z_REFCOUNT_P(value) == 1) {
				value = Z_REFVAL_P(value);
			}
			if (entry.hasStringKey()) {
				Z_TRY_ADDREF_P(value);
				zend_hash_update(merged.table(), entry.stringKey(), value);
			} else {
				merged.push(zv::Ref(value));
			}
		}
	}
	return zv::Val(std::move(merged));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\TypeSpecifier. */
class TypeSpecifier
{
public:
	explicit TypeSpecifier(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties */
	static void construct(zend_object *object, zval *exprPrinter, zval *reflectionProvider, zval *functionTypeSpecifyingExtensions, zval *methodTypeSpecifyingExtensions, zval *staticMethodTypeSpecifyingExtensions, bool rememberPossiblyImpureFunctionValues, zval *container)
	{
		writeSlot(object, slots::exprPrinter, zv::Val::copyOf(zv::Ref(exprPrinter)));
		writeSlot(object, slots::reflectionProvider, zv::Val::copyOf(zv::Ref(reflectionProvider)));
		writeSlot(object, slots::functionTypeSpecifyingExtensions, zv::Val::copyOf(zv::Ref(functionTypeSpecifyingExtensions)));
		writeSlot(object, slots::methodTypeSpecifyingExtensions, zv::Val::copyOf(zv::Ref(methodTypeSpecifyingExtensions)));
		writeSlot(object, slots::staticMethodTypeSpecifyingExtensions, zv::Val::copyOf(zv::Ref(staticMethodTypeSpecifyingExtensions)));
		writeSlot(object, slots::rememberPossiblyImpureFunctionValues, zv::Val::boolean(rememberPossiblyImpureFunctionValues));
		writeSlot(object, slots::container, zv::Val::copyOf(zv::Ref(container)));
	}

	/* Mirrors specifyTypesInCondition(); UNDEF = pending exception */
	zv::Val specifyTypesInCondition(zval *scope, zval *expr, zval *context) const
	{
		bool isCallLike;
		if (UNEXPECTED(!isA(expr, PT_CLASS_CALL_LIKE, isCallLike))) return zv::Val();
		if (isCallLike) {
			bool firstClassCallable;
			if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(expr), firstClassCallable))) return zv::Val();
			if (firstClassCallable) return emptySpecifiedTypesWithRoot(expr);
		}

		zval *container = slot(slots::container);
		if (UNEXPECTED(Z_TYPE_P(container) != IS_OBJECT)) return uninitialized("container");
		zv::Val exprHandler = pt_expr_handler_registry_resolve(Z_OBJ_P(expr), container);
		if (UNEXPECTED(exprHandler.isUndef())) return zv::Val();
		if (Z_TYPE_P(exprHandler.raw()) != IS_NULL) {
			if (pt_ce_mutating_scope != NULL && instanceof_function(Z_OBJCE_P(scope), pt_ce_mutating_scope)) {
				return pt_mutating_scope_specify_types_of_new_world_handler_node(Z_OBJ_P(scope), Z_OBJ_P(expr), context);
			}
		}

		return specifyDefaultTypes(scope, expr, context);
	}

	/* Mirrors specifyDefaultTypes(); UNDEF = pending exception */
	zv::Val specifyDefaultTypes(zval *scope, zval *expr, zval *context) const
	{
		bool isNull;
		if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), isNull))) return zv::Val();
		if (!isNull) return handleDefaultTruthyOrFalseyContext(context, expr, scope);

		return emptySpecifiedTypesWithRoot(expr);
	}

	/* Mirrors handleDefaultTruthyOrFalseyContext(); UNDEF = pending exception */
	zv::Val handleDefaultTruthyOrFalseyContext(zval *context, zval *expr, zval *scope) const
	{
		zend_object *contextObject = Z_OBJ_P(context);
		bool isNull;
		if (UNEXPECTED(!pt_type_specifier_context_null(contextObject, isNull))) return zv::Val();
		if (isNull) return emptySpecifiedTypesWithRoot(expr);

		bool truthy;
		if (UNEXPECTED(!pt_type_specifier_context_truthy(contextObject, truthy))) return zv::Val();
		if (!truthy) {
			zv::Val type = pt_static_type_factory_truthy();
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zend_object *falseContext = pt_type_specifier_context_create_false();
			if (UNEXPECTED(falseContext == NULL)) return zv::Val();
			zval falseContextZv;
			ZVAL_OBJ(&falseContextZv, falseContext);
			return withRoot(create(expr, type.raw(), &falseContextZv, scope), expr);
		}
		bool falsey;
		if (UNEXPECTED(!pt_type_specifier_context_falsey(contextObject, falsey))) return zv::Val();
		if (!falsey) {
			zv::Val type = pt_static_type_factory_falsey();
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zend_object *falseContext = pt_type_specifier_context_create_false();
			if (UNEXPECTED(falseContext == NULL)) return zv::Val();
			zval falseContextZv;
			ZVAL_OBJ(&falseContextZv, falseContext);
			return withRoot(create(expr, type.raw(), &falseContextZv, scope), expr);
		}

		return emptySpecifiedTypesWithRoot(expr);
	}

	/* Mirrors create(); UNDEF = pending exception */
	zv::Val create(zval *exprArg, zval *type, zval *context, zval *scope) const
	{
		bool is;
		if (UNEXPECTED(!isA(exprArg, PT_CLASS_INSTANCEOF_EXPR, is))) return zv::Val();
		if (!is && UNEXPECTED(!isA(exprArg, PT_CLASS_LIST_EXPR, is))) return zv::Val();
		if (is) return emptySpecifiedTypesWithRoot(exprArg);

		zv::Arr specifiedExprs = zv::Arr::empty();
		bool isAssign;
		if (UNEXPECTED(!isA(exprArg, PT_CLASS_ASSIGN_EXPR, isAssign))) return zv::Val();
		if (isAssign) {
			zval *var = pt_ts_assign_var.of(Z_OBJ_P(exprArg));
			zval *assigned = pt_ts_assign_expr.of(Z_OBJ_P(exprArg));
			if (UNEXPECTED(var == NULL || assigned == NULL)) return malformedNode("Assign");
			specifiedExprs.push(zv::Ref(var));
			specifiedExprs.push(zv::Ref(assigned));

			zend_object *current = Z_OBJ_P(exprArg);
			for (;;) {
				zval *inner = pt_ts_assign_expr.of(current);
				bool innerIsAssign;
				if (UNEXPECTED(!isA(inner, PT_CLASS_ASSIGN_EXPR, innerIsAssign))) return zv::Val();
				if (!innerIsAssign) break;
				zval *innerVar = pt_ts_assign_var.of(Z_OBJ_P(inner));
				if (UNEXPECTED(innerVar == NULL)) return malformedNode("Assign");
				specifiedExprs.push(zv::Ref(innerVar));
				current = Z_OBJ_P(inner);
			}
		} else {
			bool isCoalesceAssign;
			if (UNEXPECTED(!isA(exprArg, PT_CLASS_COALESCE_ASSIGN_OP_EXPR, isCoalesceAssign))) return zv::Val();
			if (isCoalesceAssign) {
				zval *var = pt_ts_coalesce_assign_var.of(Z_OBJ_P(exprArg));
				if (UNEXPECTED(var == NULL)) return malformedNode("AssignOp\\Coalesce");
				specifiedExprs.push(zv::Ref(var));
			} else {
				specifiedExprs.push(zv::Ref(exprArg));
			}
		}

		zv::Val types = zv::Val::null();
		for (zv::ArrayEntry entry : zv::ArrRef(specifiedExprs.raw())) {
			zval *specifiedExpr = entry.value().raw();
			zv::Val newTypes = createForExpr(specifiedExpr, type, context, scope);
			if (UNEXPECTED(newTypes.isUndef())) return zv::Val();
			if (Z_TYPE_P(types.raw()) == IS_NULL) {
				types = std::move(newTypes);
				continue;
			}
			zv::Val united = pt_specified_types_union_with(Z_OBJ_P(types.raw()), newTypes.raw());
			if (UNEXPECTED(united.isUndef())) return zv::Val();
			types = std::move(united);
		}

		return types;
	}

	zv::Val getFunctionTypeSpecifyingExtensions() const
	{
		zval *extensions = slot(slots::functionTypeSpecifyingExtensions);
		if (UNEXPECTED(Z_TYPE_P(extensions) != IS_ARRAY)) return uninitialized("functionTypeSpecifyingExtensions");
		return zv::Val::copyOf(zv::Ref(extensions));
	}

	/* Mirrors getMethodTypeSpecifyingExtensionsForClass() /
	 * getStaticMethodTypeSpecifyingExtensionsForClass(); UNDEF = pending
	 * exception */
	zv::Val getMethodTypeSpecifyingExtensionsForClass(zend_string *className) const
	{
		return extensionsForClass(slots::methodTypeSpecifyingExtensionsByClass, slots::methodTypeSpecifyingExtensions, "methodTypeSpecifyingExtensions", className);
	}

	zv::Val getStaticMethodTypeSpecifyingExtensionsForClass(zend_string *className) const
	{
		return extensionsForClass(slots::staticMethodTypeSpecifyingExtensionsByClass, slots::staticMethodTypeSpecifyingExtensions, "staticMethodTypeSpecifyingExtensions", className);
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	/* the engine's Error for reading a never-written typed property */
	void throwUninitialized(const char *property) const
	{
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), property);
	}

	zv::Val uninitialized(const char *property) const
	{
		throwUninitialized(property);
		return zv::Val();
	}

	static zv::Val malformedNode(const char *node)
	{
		zend_throw_error(NULL, "phpstan_turbo: a %s node lacks its subnodes", node);
		return zv::Val();
	}

	static void writeSlot(zend_object *object, uint32_t index, zv::Val value)
	{
		zv::ObjRef(object).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(object, index)) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* $this->rememberPossiblyImpureFunctionValues; false = pending exception */
	[[nodiscard]] bool rememberPossiblyImpureFunctionValues(bool &out) const
	{
		zval *value = slot(slots::rememberPossiblyImpureFunctionValues);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_TRUE && Z_TYPE_P(value) != IS_FALSE)) {
			throwUninitialized("rememberPossiblyImpureFunctionValues");
			return false;
		}
		out = Z_TYPE_P(value) == IS_TRUE;
		return true;
	}

	/* the memoized by-class table of getMethodTypeSpecifyingExtensionsForClass()
	 * and its static twin, then getTypeSpecifyingExtensionsForType() */
	zv::Val extensionsForClass(uint32_t byClassSlot, uint32_t extensionsSlot, const char *extensionsProperty, zend_string *className) const
	{
		zval *byClass = slot(byClassSlot);
		if (Z_TYPE_P(byClass) != IS_ARRAY) {
			zval *extensions = slot(extensionsSlot);
			if (UNEXPECTED(Z_TYPE_P(extensions) != IS_ARRAY)) return uninitialized(extensionsProperty);
			zv::Arr built = zv::Arr::empty();
			for (zv::ArrayEntry entry : zv::ArrRef(extensions)) {
				zval *extension = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(extension) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getClass() on %s", zend_zval_value_name(extension));
					return zv::Val();
				}
				zv::Val extensionClass = pt_type_call(Z_OBJ_P(extension), PT_LC("getclass"), 0, NULL);
				if (UNEXPECTED(extensionClass.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(extensionClass.raw()) != IS_STRING)) {
					zend_type_error("phpstan_turbo: getClass() must return string, %s returned", zend_zval_value_name(extensionClass.raw()));
					return zv::Val();
				}
				/* $byClass[$extension->getClass()][] = $extension */
				built.separate();
				zval *list = zend_symtable_find(built.table(), Z_STR_P(extensionClass.raw()));
				if (list == NULL) {
					zval fresh;
					array_init(&fresh);
					list = zend_symtable_update(built.table(), Z_STR_P(extensionClass.raw()), &fresh);
				}
				SEPARATE_ARRAY(list);
				Z_TRY_ADDREF_P(extension);
				zend_hash_next_index_insert(Z_ARRVAL_P(list), extension);
			}
			zv::ObjRef(self).propAtWrite(byClassSlot, zv::Val(std::move(built)));
			byClass = slot(byClassSlot);
		}

		return getTypeSpecifyingExtensionsForType(zv::Val::copyOf(zv::Ref(byClass)), className);
	}

	/* Mirrors getTypeSpecifyingExtensionsForType(); UNDEF = pending exception */
	zv::Val getTypeSpecifyingExtensionsForType(zv::Val extensions, zend_string *className) const
	{
		zval *reflectionProvider = slot(slots::reflectionProvider);
		if (UNEXPECTED(Z_TYPE_P(reflectionProvider) != IS_OBJECT)) return uninitialized("reflectionProvider");
		zval classNameZv;
		ZVAL_STR(&classNameZv, className);
		zv::Val extensionClassNames = pt_extension_class_helper_get_extension_class_names(reflectionProvider, &classNameZv);
		if (UNEXPECTED(extensionClassNames.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(extensionClassNames.raw()) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: ExtensionClassHelper::getExtensionClassNames() must return array, %s returned", zend_zval_value_name(extensionClassNames.raw()));
			return zv::Val();
		}

		/* $extensionsForClass = [[]]; ...; array_merge(...$extensionsForClass) */
		uint32_t capacity = zend_hash_num_elements(Z_ARRVAL_P(extensionClassNames.raw()));
		zval inlineLists[8];
		zval *lists = capacity <= 8 ? inlineLists : (zval *) emalloc(sizeof(zval) * capacity);
		uint32_t count = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(extensionClassNames.raw())) {
			zval *extensionClassName = entry.value().deref().raw();
			zval *found = NULL;
			if (Z_TYPE_P(extensionClassName) == IS_STRING) {
				found = zend_symtable_find(Z_ARRVAL_P(extensions.raw()), Z_STR_P(extensionClassName));
			} else if (Z_TYPE_P(extensionClassName) == IS_LONG) {
				found = zend_hash_index_find(Z_ARRVAL_P(extensions.raw()), (zend_ulong) Z_LVAL_P(extensionClassName));
			}
			if (found == NULL) continue;
			ZVAL_DEREF(found);
			if (Z_TYPE_P(found) == IS_NULL) continue;
			if (UNEXPECTED(Z_TYPE_P(found) != IS_ARRAY)) {
				if (lists != inlineLists) {
					efree(lists);
				}
				zend_type_error("array_merge(): Argument #%u must be of type array, %s given", count + 2, zend_zval_value_name(found));
				return zv::Val();
			}
			ZVAL_COPY_VALUE(&lists[count], found);
			count++;
		}
		zv::Val merged = arrayMergeAll(lists, count);
		if (lists != inlineLists) {
			efree(lists);
		}
		return merged;
	}

	/* the null-containment probe of createForExpr(): -1 = not set (a bare
	 * variable, or a null context), else the $containsNull bool; -2 =
	 * pending exception */
	int containsNullProbe(zval *expr, zval *type, zval *context, zval *scope) const
	{
		bool isVariable;
		if (UNEXPECTED(!isA(expr, PT_CLASS_VARIABLE, isVariable))) return -2;
		if (isVariable) return -1;

		bool isTrue;
		if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), isTrue))) return -2;
		if (isTrue) {
			zend_long typeIsNull = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(typeIsNull < 0)) return -2;
			if (typeIsNull == PT_TRI_NO) return 0;
			zv::Val exprType = scopeType(scope, expr);
			if (UNEXPECTED(exprType.isUndef())) return -2;
			zend_long exprIsNull = pt_type_op_trinary(Z_OBJ_P(exprType.raw()), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(exprIsNull < 0)) return -2;
			return exprIsNull != PT_TRI_NO ? 1 : 0;
		}
		bool isFalse;
		if (UNEXPECTED(!pt_type_specifier_context_false(Z_OBJ_P(context), isFalse))) return -2;
		if (isFalse) {
			bool typeContainsNull;
			if (UNEXPECTED(!pt_type_combinator_contains_null(type, typeContainsNull))) return -2;
			if (typeContainsNull) return 0;
			zv::Val exprType = scopeType(scope, expr);
			if (UNEXPECTED(exprType.isUndef())) return -2;
			zend_long exprIsNull = pt_type_op_trinary(Z_OBJ_P(exprType.raw()), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(exprIsNull < 0)) return -2;
			return exprIsNull != PT_TRI_NO ? 1 : 0;
		}
		return -1;
	}

	/* the early return of the impure-call branches: createNullsafeTypes() when
	 * the probe set $containsNull to false, new SpecifiedTypes([], []) else */
	zv::Val impureCallResult(int containsNull, zval *originalExpr, zval *scope, zval *context, zval *type) const
	{
		if (containsNull == 0) return createNullsafeTypes(originalExpr, scope, context, type);
		return emptySpecifiedTypes();
	}

	/* isNotPure(): $hasSideEffects->yes() || (!$this->rememberPossiblyImpureFunctionValues
	 * && !$hasSideEffects->no()); -1 = pending exception, else 0 / 1 */
	int isNotPure(zend_long hasSideEffects) const
	{
		if (hasSideEffects == PT_TRI_YES) return 1;
		bool remember;
		if (UNEXPECTED(!rememberPossiblyImpureFunctionValues(remember))) return -1;
		return !remember && hasSideEffects != PT_TRI_NO ? 1 : 0;
	}

	/* $methodReflection === null || $this->isNotPure($methodReflection->hasSideEffects());
	 * -1 = pending exception, else 0 / 1 */
	int methodIsNotPure(zval *methodReflection) const
	{
		if (Z_TYPE_P(methodReflection) == IS_NULL) return 1;
		if (UNEXPECTED(Z_TYPE_P(methodReflection) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function hasSideEffects() on %s", zend_zval_value_name(methodReflection));
			return -1;
		}
		zend_long hasSideEffects = pt_extended_method_reflection_trinary(methodReflection, PT_MR_HAS_SIDE_EFFECTS);
		if (UNEXPECTED(hasSideEffects < 0)) return -1;
		return isNotPure(hasSideEffects);
	}

	/* $this->reflectionProvider->hasFunction($name, $scope); -1 = pending
	 * exception, else 0 / 1 */
	int hasFunction(zval *name, zval *scope) const
	{
		zval *reflectionProvider = slot(slots::reflectionProvider);
		if (UNEXPECTED(Z_TYPE_P(reflectionProvider) != IS_OBJECT)) {
			throwUninitialized("reflectionProvider");
			return -1;
		}
		zv::Args args{name, scope};
		zv::Val has = pt_type_call(Z_OBJ_P(reflectionProvider), PT_LC("hasfunction"), 2, args);
		if (UNEXPECTED(has.isUndef())) return -1;
		return zend_is_true(has.raw()) ? 1 : 0;
	}

	/* Mirrors createForExpr(); UNDEF = pending exception */
	zv::Val createForExpr(zval *exprArg, zval *type, zval *context, zval *scope) const
	{
		// the null-containment probe only feeds the nullsafe-shortcircuit unwrap
		// and createNullsafeTypes() - both are no-ops for a bare variable, so the
		// probe (and its type ask) is skipped for one
		int containsNull = containsNullProbe(exprArg, type, context, scope);
		if (UNEXPECTED(containsNull == -2)) return zv::Val();

		zv::Val expr = zv::Val::copyOf(zv::Ref(exprArg));
		zval *originalExpr = exprArg;
		if (containsNull == 0) {
			zv::Val shortcircuited = nullsafeShortcircuitedExpr(expr.raw());
			if (UNEXPECTED(shortcircuited.isUndef())) return zv::Val();
			expr = std::move(shortcircuited);
		}

		bool isNullContext;
		if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), isNullContext))) return zv::Val();
		bool isCoalesce;
		if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_COALESCE_EXPR, isCoalesce))) return zv::Val();
		if (!isNullContext && isCoalesce) {
			zval *right = pt_ts_coalesce_right.of(Z_OBJ_P(expr.raw()));
			zval *left = pt_ts_coalesce_left.of(Z_OBJ_P(expr.raw()));
			if (UNEXPECTED(right == NULL || left == NULL)) return malformedNode("BinaryOp\\Coalesce");
			bool unwrap = false;
			bool isTrue;
			if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), isTrue))) return zv::Val();
			if (isTrue) {
				zv::Val rightType = scopeType(scope, right);
				if (UNEXPECTED(rightType.isUndef())) return zv::Val();
				zend_long verdict = isSuperTypeOf(type, rightType.raw());
				if (UNEXPECTED(verdict < 0)) return zv::Val();
				unwrap = verdict == PT_TRI_NO;
			}
			if (!unwrap) {
				bool isFalse;
				if (UNEXPECTED(!pt_type_specifier_context_false(Z_OBJ_P(context), isFalse))) return zv::Val();
				if (isFalse) {
					zv::Val rightType = scopeType(scope, right);
					if (UNEXPECTED(rightType.isUndef())) return zv::Val();
					zend_long verdict = isSuperTypeOf(type, rightType.raw());
					if (UNEXPECTED(verdict < 0)) return zv::Val();
					unwrap = verdict == PT_TRI_YES;
				}
			}
			if (unwrap) {
				expr = zv::Val::copyOf(zv::Ref(left));
			}
		}

		bool isFuncCall;
		if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_FUNC_CALL, isFuncCall))) return zv::Val();
		if (isFuncCall) {
			zval *name = pt_ts_func_call_name.of(Z_OBJ_P(expr.raw()));
			bool nameIsName;
			if (UNEXPECTED(!isA(name, PT_CLASS_NAME, nameIsName))) return zv::Val();
			if (nameIsName) {
				int has = hasFunction(name, scope);
				if (UNEXPECTED(has < 0)) return zv::Val();
				if (!has) return emptySpecifiedTypes();
			}
		}

		bool isAlwaysRemembered;
		if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_ALWAYS_REMEMBERED_EXPR, isAlwaysRemembered))) return zv::Val();
		if (!isAlwaysRemembered) {
			int nonPure = expressionContainsNonPureCall(Z_OBJ_P(expr.raw()), scope);
			if (UNEXPECTED(nonPure < 0)) return zv::Val();
			if (nonPure) return impureCallResult(containsNull, originalExpr, scope, context, type);
		}

		zv::Arr sureTypes = zv::Arr::empty();
		zv::Arr sureNotTypes = zv::Arr::empty();
		bool isFalse;
		if (UNEXPECTED(!pt_type_specifier_context_false(Z_OBJ_P(context), isFalse))) return zv::Val();
		if (isFalse) {
			if (UNEXPECTED(!addEntries(sureNotTypes, expr.raw(), originalExpr, type))) return zv::Val();
		} else {
			bool isTrue;
			if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), isTrue))) return zv::Val();
			if (isTrue) {
				if (UNEXPECTED(!addEntries(sureTypes, expr.raw(), originalExpr, type))) return zv::Val();
			}
		}

		zv::Val types = pt_specified_types_new(sureTypes.raw(), sureNotTypes.raw());
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		if (containsNull == 0) {
			zv::Val nullsafeTypes = createNullsafeTypes(originalExpr, scope, context, type);
			if (UNEXPECTED(nullsafeTypes.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(nullsafeTypes.raw()) != IS_OBJECT)) return zv::Val();
			return pt_specified_types_union_with(Z_OBJ_P(nullsafeTypes.raw()), types.raw());
		}

		return types;
	}

	/* $type->isSuperTypeOf($other) verdict; -1 = pending exception */
	static zend_long isSuperTypeOf(zval *type, zval *other)
	{
		zv::Val result = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, other);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}

	/* $scope->getMethodReflection($calledOnType, $methodName) */
	static zv::Val methodReflectionOf(zval *scope, zval *calledOnType, zval *methodName)
	{
		if (UNEXPECTED(Z_TYPE_P(methodName) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\Scope::getMethodReflection(): Argument #2 ($methodName) must be of type string, %s given", zend_zval_value_name(methodName));
			return zv::Val();
		}
		return pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), calledOnType, Z_STR_P(methodName));
	}

	/* the state of one expressionContainsNonPureCall() walk; `base` first, as
	 * pt_find_first_recursive() reads its `failed` flag through it */
	struct NonPureCallSearch
	{
		pt_find_ctx base;
		const TypeSpecifier *self;
		zval *scope;
		zend_class_entry *callLike;
		bool containsCall;
	};

	static bool nonPureCallMatcher(zend_object *node, void *ctx)
	{
		NonPureCallSearch *search = static_cast<NonPureCallSearch *>(ctx);
		if (!instanceof_function(node->ce, search->callLike)) return false;
		search->containsCall = true;
		int notPure = search->self->callIsNotPure(node, search->scope);
		if (UNEXPECTED(notPure < 0)) {
			search->base.failed = true;
			return false;
		}
		return notPure == 1;
	}

	/* expressionContainsNonPureCall(): the pre-order search for a call that
	 * isn't known to be pure, the answer for a call-free subtree remembered on
	 * the node as the containsCall attribute; -1 = pending exception */
	int expressionContainsNonPureCall(zend_object *expr, zval *scope) const
	{
		pt_init_strs();
		zval *cached = pt_node_attribute(expr, pt_str_contains_call);
		if (cached != NULL && Z_TYPE_P(cached) == IS_FALSE) return 0;

		zend_class_entry *callLike = pt_class(PT_CLASS_CALL_LIKE);
		if (UNEXPECTED(callLike == NULL)) return -1;
		NonPureCallSearch search;
		memset(&search.base, 0, sizeof(search.base));
		search.self = this;
		search.scope = scope;
		search.callLike = callLike;
		search.containsCall = false;
		bool found = pt_find_first_recursive(expr, nonPureCallMatcher, &search) != NULL;
		if (UNEXPECTED(search.base.failed)) return -1;
		if (!search.containsCall) {
			zval noCall;
			ZVAL_FALSE(&noCall);
			if (UNEXPECTED(!pt_node_set_attribute(expr, pt_str_contains_call, &noCall))) return -1;
		}
		return found ? 1 : 0;
	}

	/* callIsNotPure(); -1 = pending exception, else 0 / 1 */
	int callIsNotPure(zend_object *call, zval *scope) const
	{
		zval callZv;
		ZVAL_OBJ(&callZv, call);

		bool isFuncCall;
		if (UNEXPECTED(!isA(&callZv, PT_CLASS_FUNC_CALL, isFuncCall))) return -1;
		if (isFuncCall) {
			zval *name = pt_ts_func_call_name.of(call);
			bool nameIsName;
			if (UNEXPECTED(!isA(name, PT_CLASS_NAME, nameIsName))) return -1;
			if (nameIsName) {
				int has = hasFunction(name, scope);
				if (has <= 0) return has;
				zval *reflectionProvider = slot(slots::reflectionProvider);
				zv::Args args{name, scope};
				zv::Val functionReflection = pt_type_call(Z_OBJ_P(reflectionProvider), PT_LC("getfunction"), 2, args);
				if (UNEXPECTED(functionReflection.isUndef())) return -1;
				zend_long hasSideEffects = trinaryOf(functionReflection.raw(), PT_LC("hassideeffects"), "hasSideEffects");
				if (UNEXPECTED(hasSideEffects < 0)) return -1;
				return isNotPure(hasSideEffects);
			}
			return callableIsNotPure(name, scope);
		}

		bool isMethodCall;
		if (UNEXPECTED(!isA(&callZv, PT_CLASS_METHOD_CALL, isMethodCall))) return -1;
		if (isMethodCall) {
			zval *name = pt_ts_method_call_name.of(call);
			bool nameIsIdentifier;
			if (UNEXPECTED(!isA(name, PT_CLASS_IDENTIFIER, nameIsIdentifier))) return -1;
			if (!nameIsIdentifier) return 1;
			zv::Val calledOnType = scopeType(scope, pt_ts_method_call_var.of(call));
			if (UNEXPECTED(calledOnType.isUndef())) return -1;
			zv::Val methodName = pt_type_call(Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
			if (UNEXPECTED(methodName.isUndef())) return -1;
			zv::Val methodReflection = methodReflectionOf(scope, calledOnType.raw(), methodName.raw());
			if (UNEXPECTED(methodReflection.isUndef())) return -1;
			return methodIsNotPure(methodReflection.raw());
		}

		bool isStaticCall;
		if (UNEXPECTED(!isA(&callZv, PT_CLASS_STATIC_CALL, isStaticCall))) return -1;
		if (isStaticCall) {
			zval *name = pt_ts_static_call_name.of(call);
			bool nameIsIdentifier;
			if (UNEXPECTED(!isA(name, PT_CLASS_IDENTIFIER, nameIsIdentifier))) return -1;
			if (!nameIsIdentifier) return 1;
			zval *classNode = pt_ts_static_call_class.of(call);
			bool classIsName;
			if (UNEXPECTED(!isA(classNode, PT_CLASS_NAME, classIsName))) return -1;
			zv::Val calledOnType = classIsName
				? pt_mutating_scope_resolve_type_by_name(Z_OBJ_P(scope), Z_OBJ_P(classNode))
				: scopeType(scope, classNode);
			if (UNEXPECTED(calledOnType.isUndef())) return -1;
			zv::Val methodName = pt_type_call(Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
			if (UNEXPECTED(methodName.isUndef())) return -1;
			zv::Val methodReflection = methodReflectionOf(scope, calledOnType.raw(), methodName.raw());
			if (UNEXPECTED(methodReflection.isUndef())) return -1;
			return methodIsNotPure(methodReflection.raw());
		}

		return 0;
	}

	/* the FuncCall-with-an-Expr-name arm of callIsNotPure(): the called
	 * value's variants combined, isNotPure() of their negated purity; -1 =
	 * pending exception */
	int callableIsNotPure(zval *name, zval *scope) const
	{
		zv::Val nameType = scopeType(scope, name);
		if (UNEXPECTED(nameType.isUndef())) return -1;
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(nameType.raw()), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return -1;
		if (isCallable != PT_TRI_YES) return 0;

		zv::Val variants = pt_type_call(Z_OBJ_P(nameType.raw()), PT_LC("getcallableparametersacceptors"), 1, scope);
		if (UNEXPECTED(variants.isUndef())) return -1;
		if (UNEXPECTED(Z_TYPE_P(variants.raw()) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: getCallableParametersAcceptors() must return array, %s returned", zend_zval_value_name(variants.raw()));
			return -1;
		}
		zend_long isPure = -1;
		for (zv::ArrayEntry entry : zv::ArrRef(variants.raw())) {
			zend_long variantIsPure = trinaryOf(entry.value().deref().raw(), PT_LC("ispure"), "isPure");
			if (UNEXPECTED(variantIsPure < 0)) return -1;
			isPure = isPure < 0 ? variantIsPure : pt_trinary_and(isPure, variantIsPure);
		}
		if (isPure < 0) return 0;
		return isNotPure(isPure == PT_TRI_YES ? PT_TRI_NO : (isPure == PT_TRI_NO ? PT_TRI_YES : PT_TRI_MAYBE));
	}

	/* $types[$printer->printExpr($expr)] = [$expr, $type], then the same for
	 * $originalExpr when the unwrap replaced it; false = pending exception */
	[[nodiscard]] bool addEntries(zv::Arr &table, zval *expr, zval *originalExpr, zval *type) const
	{
		if (UNEXPECTED(!addEntry(table, expr, type))) return false;
		if (Z_OBJ_P(expr) != Z_OBJ_P(originalExpr)) {
			return addEntry(table, originalExpr, type);
		}
		return true;
	}

	[[nodiscard]] bool addEntry(zv::Arr &table, zval *expr, zval *type) const
	{
		zval *exprPrinter = slot(slots::exprPrinter);
		if (UNEXPECTED(Z_TYPE_P(exprPrinter) != IS_OBJECT)) {
			throwUninitialized("exprPrinter");
			return false;
		}
		zv::Val exprString = printExpr(exprPrinter, expr);
		if (UNEXPECTED(exprString.isUndef())) return false;
		zv::Arr pair = zv::Arr::create(2);
		pair.push(zv::Ref(expr));
		pair.push(zv::Ref(type));
		table.set(Z_STR_P(exprString.raw()), zv::Val(std::move(pair)));
		return true;
	}

	/* $this->exprPrinter->printExpr($expr): the native printer's cache fast
	 * paths (pt_node_key's Variable and attribute reads are the same ones),
	 * the method for any other printer; UNDEF = pending exception */
	static zv::Val printExpr(zval *exprPrinter, zval *expr)
	{
		if (EXPECTED(Z_OBJCE_P(exprPrinter) == pt_ce_expr_printer)) {
			pt_init_strs();
			pt_node_class_info *info = pt_get_node_class_info(Z_OBJCE_P(expr));
			if (info != NULL && info->is_variable && info->name_offset >= 0) {
				zval *name = OBJ_PROP(Z_OBJ_P(expr), info->name_offset);
				ZVAL_DEREF(name);
				if (Z_TYPE_P(name) == IS_STRING) {
					zend_string *nameStr = Z_STR_P(name);
					zend_string *key = zend_string_alloc(ZSTR_LEN(nameStr) + 1, 0);
					ZSTR_VAL(key)[0] = '$';
					memcpy(ZSTR_VAL(key) + 1, ZSTR_VAL(nameStr), ZSTR_LEN(nameStr));
					ZSTR_VAL(key)[ZSTR_LEN(key)] = '\0';
					return zv::Val::adoptString(key);
				}
			}
			zval *cached = pt_node_attribute(Z_OBJ_P(expr), pt_str_cache_printer);
			if (cached != NULL && Z_TYPE_P(cached) == IS_STRING) return zv::Val::string(Z_STR_P(cached));
			zend_string *printed = pt_expr_printer_print_uncached(exprPrinter, Z_OBJ_P(expr));
			if (UNEXPECTED(printed == NULL)) return zv::Val();
			return zv::Val::adoptString(printed);
		}
		zv::Val printed = pt_type_call(Z_OBJ_P(exprPrinter), PT_LC("printexpr"), 1, expr);
		if (UNEXPECTED(printed.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(printed.raw()) != IS_STRING)) {
			zend_type_error("phpstan_turbo: printExpr() must return string, %s returned", zend_zval_value_name(printed.raw()));
			return zv::Val();
		}
		return printed;
	}

	/* Mirrors createNullsafeTypes(); $type NULL for null. The tail calls
	 * down a chain's receiver are a loop; UNDEF = pending exception */
	zv::Val createNullsafeTypes(zval *exprArg, zval *scope, zval *context, zval *typeArg) const
	{
		zv::Val expr = zv::Val::copyOf(zv::Ref(exprArg));
		zval *type = typeArg;
		for (;;) {
			zend_object *node = Z_OBJ_P(expr.raw());
			bool is;
			if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_NULLSAFE_PROPERTY_FETCH, is))) return zv::Val();
			if (is) {
				zval *var = pt_ts_nullsafe_property_fetch_var.of(node);
				zval *name = pt_ts_nullsafe_property_fetch_name.of(node);
				if (UNEXPECTED(var == NULL || name == NULL)) return malformedNode("NullsafePropertyFetch");
				zv::Args fetchArgs{var, name};
				zv::Val propertyFetch = pt_type_new(PT_CLASS_PROPERTY_FETCH, 2, fetchArgs);
				if (UNEXPECTED(propertyFetch.isUndef())) return zv::Val();
				return nullsafeUnion(std::move(propertyFetch), var, scope, context, type);
			}

			if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_NULLSAFE_METHOD_CALL, is))) return zv::Val();
			if (is) {
				zval *var = pt_ts_nullsafe_method_call_var.of(node);
				zval *name = pt_ts_nullsafe_method_call_name.of(node);
				zval *args = pt_ts_nullsafe_method_call_args.of(node);
				if (UNEXPECTED(var == NULL || name == NULL || args == NULL)) return malformedNode("NullsafeMethodCall");
				zv::Args callArgs{var, name, args};
				zv::Val methodCall = pt_type_new(PT_CLASS_METHOD_CALL, 3, callArgs);
				if (UNEXPECTED(methodCall.isUndef())) return zv::Val();
				return nullsafeUnion(std::move(methodCall), var, scope, context, type);
			}

			zval *next = NULL;
			if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_PROPERTY_FETCH, is))) return zv::Val();
			if (is) {
				next = pt_ts_property_fetch_var.of(node);
			} else {
				if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_METHOD_CALL, is))) return zv::Val();
				if (is) {
					next = pt_ts_method_call_var.of(node);
				} else {
					if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_ARRAY_DIM_FETCH, is))) return zv::Val();
					if (is) {
						next = pt_ts_array_dim_fetch_var.of(node);
					} else {
						bool classIsExpr;
						if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_STATIC_PROPERTY_FETCH, is))) return zv::Val();
						if (is) {
							zval *classNode = pt_ts_static_property_fetch_class.of(node);
							if (UNEXPECTED(!isA(classNode, PT_CLASS_EXPR, classIsExpr))) return zv::Val();
							if (classIsExpr) next = classNode;
						} else {
							if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_STATIC_CALL, is))) return zv::Val();
							if (is) {
								zval *classNode = pt_ts_static_call_class.of(node);
								if (UNEXPECTED(!isA(classNode, PT_CLASS_EXPR, classIsExpr))) return zv::Val();
								if (classIsExpr) next = classNode;
							}
						}
					}
				}
			}
			if (next == NULL) return emptySpecifiedTypes();
			if (UNEXPECTED(Z_TYPE_P(next) != IS_OBJECT)) {
				zend_type_error("PHPStan\\Analyser\\TypeSpecifier::createNullsafeTypes(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(next));
				return zv::Val();
			}
			// return $this->createNullsafeTypes($expr->var, $scope, $context, null)
			expr = zv::Val::copyOf(zv::Ref(next));
			type = NULL;
		}
	}

	/* the Nullsafe* cases: create($nonNullsafe, $type ?? new NullType(),
	 * $type !== null ? $context : false, $scope)->unionWith(create($var,
	 * new NullType(), false, $scope)); UNDEF = pending exception */
	zv::Val nullsafeUnion(zv::Val nonNullsafe, zval *var, zval *scope, zval *context, zval *type) const
	{
		zend_object *falseContext = pt_type_specifier_context_create_false();
		if (UNEXPECTED(falseContext == NULL)) return zv::Val();
		zval falseContextZv;
		ZVAL_OBJ(&falseContextZv, falseContext);

		zv::Val fetchTypes;
		if (type != NULL) {
			fetchTypes = create(nonNullsafe.raw(), type, context, scope);
		} else {
			zval nullType;
			if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
			zv::Val nullTypeVal = zv::Val::adopt(nullType);
			fetchTypes = create(nonNullsafe.raw(), nullTypeVal.raw(), &falseContextZv, scope);
		}
		if (UNEXPECTED(fetchTypes.isUndef())) return zv::Val();

		zval varNullType;
		if (UNEXPECTED(!pt_null_type_new(&varNullType))) return zv::Val();
		zv::Val varNullTypeVal = zv::Val::adopt(varNullType);
		zv::Val varTypes = create(var, varNullTypeVal.raw(), &falseContextZv, scope);
		if (UNEXPECTED(varTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(fetchTypes.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function unionWith() on %s", zend_zval_value_name(fetchTypes.raw()));
			return zv::Val();
		}
		return pt_specified_types_union_with(Z_OBJ_P(fetchTypes.raw()), varTypes.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeSpecifier;

/* {{{ direct entries (support.h) */

zv::Val pt_type_specifier_specify_types_in_condition(zend_object *typeSpecifier, zval *scope, zend_object *expr, zend_object *context)
{
	zval exprZv, contextZv;
	ZVAL_OBJ(&exprZv, expr);
	ZVAL_OBJ(&contextZv, context);
	if (EXPECTED(typeSpecifier->ce == pt_ce_type_specifier && Z_TYPE_P(scope) == IS_OBJECT)) return TypeSpecifier(typeSpecifier).specifyTypesInCondition(scope, &exprZv, &contextZv);
	zv::Args args{scope, expr, context};
	return pt_type_call(typeSpecifier, PT_LC("specifytypesincondition"), 3, args);
}

zv::Val pt_type_specifier_get_method_type_specifying_extensions_for_class(zend_object *typeSpecifier, zval *className)
{
	if (EXPECTED(typeSpecifier->ce == pt_ce_type_specifier && Z_TYPE_P(className) == IS_STRING)) return TypeSpecifier(typeSpecifier).getMethodTypeSpecifyingExtensionsForClass(Z_STR_P(className));
	return pt_type_call(typeSpecifier, PT_LC("getmethodtypespecifyingextensionsforclass"), 1, className);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TS_THIS TypeSpecifier(Z_OBJ_P(ZEND_THIS))

void pt_register_type_specifier()
{
	reg::Class cls("PHPStan\\Analyser\\TypeSpecifier");
	ptdecl::TypeSpecifier::declareClass(cls);
	ptdecl::TypeSpecifier::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly (README rule 6) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exprPrinter, *reflectionProvider, *functionTypeSpecifyingExtensions, *methodTypeSpecifyingExtensions, *staticMethodTypeSpecifyingExtensions, *container;
		bool rememberPossiblyImpureFunctionValues;
		/* raw zpp: seven parameters, beyond zp::parse's arity */
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(exprPrinter)
			Z_PARAM_OBJECT(reflectionProvider)
			Z_PARAM_ARRAY(functionTypeSpecifyingExtensions)
			Z_PARAM_ARRAY(methodTypeSpecifyingExtensions)
			Z_PARAM_ARRAY(staticMethodTypeSpecifyingExtensions)
			Z_PARAM_BOOL(rememberPossiblyImpureFunctionValues)
			Z_PARAM_OBJECT(container)
		ZEND_PARSE_PARAMETERS_END();
		TypeSpecifier::construct(Z_OBJ_P(ZEND_THIS), exprPrinter, reflectionProvider, functionTypeSpecifyingExtensions, methodTypeSpecifyingExtensions, staticMethodTypeSpecifyingExtensions, rememberPossiblyImpureFunctionValues, container);
	});

	cls.method<&TypeSpecifier::specifyTypesInCondition, zp::Obj, zp::Obj, zp::Obj>(sigs::specifyTypesInCondition);
	cls.method<&TypeSpecifier::specifyDefaultTypes, zp::Obj, zp::Obj, zp::Obj>(sigs::specifyDefaultTypes);
	cls.method<&TypeSpecifier::handleDefaultTruthyOrFalseyContext, zp::Obj, zp::Obj, zp::Obj>(sigs::handleDefaultTruthyOrFalseyContext);
	cls.method<&TypeSpecifier::create, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(sigs::create);
	cls.method<&TypeSpecifier::getFunctionTypeSpecifyingExtensions>(sigs::getFunctionTypeSpecifyingExtensions);
	cls.method<&TypeSpecifier::getMethodTypeSpecifyingExtensionsForClass, zp::Str>(sigs::getMethodTypeSpecifyingExtensionsForClass);
	cls.method<&TypeSpecifier::getStaticMethodTypeSpecifyingExtensionsForClass, zp::Str>(sigs::getStaticMethodTypeSpecifyingExtensionsForClass);

	cls.shadow(&pt_ce_type_specifier);
}

/* }}} */
