/*
 * PHPStanTurbo\NodeCallbackScope — native implementation of
 * PHPStan\Analyser\NodeCallbackScope (final, extending the native
 * MutatingScope as the twin extends the PHP one).
 *
 * NodeScopeResolver hands one to every rule and collector callback. It
 * answers type asks from the expression results the walk already stored
 * (post-order emission) instead of re-resolving: getType() / getNativeType()
 * memoize per node identity, consume a settled stored result guarded by this
 * scope's position, and fall back to the walk-flavour scope (toWalkScope(),
 * seeded by MutatingScope::toNodeCallbackScope() or built once through the
 * walk scope factory). The truthy/falsey filters a callback derives are
 * replayed onto stored results' before scopes.
 *
 * Layout. The twin's six properties follow MutatingScope's slots (generated
 * declarations). Everything inherited — the parent bodies the overrides wrap
 * (parent::filterByTruthyValue() & co.), the protected
 * findSettledStoredResult(), the walk-flavour create() — is a
 * pt_mutating_scope_* direct entry; the calls on other scopes are the
 * dispatching pt_mutating_scope_* entries (a MutatingScope's C++ body, this
 * class's for a NodeCallbackScope, the method by name otherwise), the stored
 * results' reads the pt_expression_result_* entries. WeakReference has no C
 * API for create() / get(): those two are the engine's own methods, resolved
 * once. TypeExpr stays PHP; its final getExprType() is its promoted slot.
 *
 * The class is final: $this-calls of the twin to its own methods are direct
 * C++ calls, to inherited MutatingScope methods the parent bodies.
 */

#include "support.h"
#include "generated/NodeCallbackScope.h"
#include "generated/MutatingScope.h"

namespace slots = ptdecl::NodeCallbackScope::slot;
namespace sigs = ptdecl::NodeCallbackScope::sig;
namespace msSlots = ptdecl::MutatingScope::slot;
#include "zv.h"
#include "TypeTraits.h"
#include "AnalyserValues.h"
#include "Engine.h"

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#include "zend_weakrefs.h" /* zend_ce_weakref */
#pragma GCC diagnostic pop

zend_class_entry *pt_ce_node_callback_scope = nullptr;

namespace {

/* {{{ collaborators without a native entry */

zend_function *pt_ncs_weakref_create_fn = nullptr;
zend_function *pt_ncs_weakref_get_fn = nullptr;

zv::Val weakReferenceCall(zend_function *&fn, const char *lcname, size_t len, zend_object *object, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(fn == nullptr)) {
		fn = (zend_function *) zend_hash_str_find_ptr(&zend_ce_weakref->function_table, lcname, len);
		if (UNEXPECTED(fn == nullptr)) {
			zend_throw_error(NULL, "phpstan_turbo: WeakReference::%s() does not exist", lcname);
			return zv::Val();
		}
	}
	zval ret;
	zend_call_known_function(fn, object, zend_ce_weakref, &ret, argc, argv, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}
	return zv::Val::adopt(ret);
}

/* $node instanceof TypeExpr ? $node->getExprType() : null — the final
 * class's getter is its promoted $exprType slot; NULL = not a TypeExpr,
 * UNDEF result = pending exception */
pt_property_site pt_ncs_expr_type_site;

bool isTypeExpr(zend_object *node)
{
	zend_class_entry *typeExpr = pt_class_loaded(PT_CLASS_TYPE_EXPR);
	return typeExpr != NULL && instanceof_function(node->ce, typeExpr);
}

zv::Val typeExprType(zend_object *node)
{
	zval *slot = pt_property_cached(pt_ncs_expr_type_site, node, PT_LC("exprType"));
	if (EXPECTED(slot != NULL && Z_TYPE_P(slot) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(slot));
	return pt_type_call(node, PT_LC("getexprtype"), 0, NULL);
}

/* the Error a method call on a non-object raises */
zend_object *requireObject(zval *value, const char *method)
{
	if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
		return NULL;
	}
	return Z_OBJ_P(value);
}

/* $scope->getType($node) / ->getNativeType($node) on any scope */
zv::Val typeOnScope(zval *scope, zval *node, bool native)
{
	zend_object *scopeObject = requireObject(scope, native ? "getNativeType" : "getType");
	if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
	return native ? pt_mutating_scope_get_native_type(scopeObject, node) : pt_mutating_scope_get_type(scopeObject, node);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\NodeCallbackScope. The handle wraps an instance
 * of exactly the native class. */
class NodeCallbackScope
{
public:
	explicit NodeCallbackScope(zend_object *self) : self(self) {}

	zv::Val toNodeCallbackScope() const { return selfValue(); }

	/* $this->seededWalkScope = WeakReference::create($scope); false = pending
	 * exception */
	[[nodiscard]] bool seedWalkScope(zval *scope) const
	{
		zv::Val weak = pt_weak_reference_create(scope);
		if (UNEXPECTED(weak.isUndef())) return false;
		write(slots::seededWalkScope, std::move(weak));
		return true;
	}

	zv::Val toWalkScope() const
	{
		zval *walkScope = prop(slots::walkScope);
		if (Z_TYPE_P(walkScope) != IS_NULL) return zv::Val::copyOf(zv::Ref(walkScope));

		zval *seededWalkScope = prop(slots::seededWalkScope);
		if (Z_TYPE_P(seededWalkScope) != IS_NULL) {
			zv::Val seeded = pt_weak_reference_get(Z_OBJ_P(seededWalkScope));
			if (UNEXPECTED(seeded.isUndef())) return zv::Val();
			if (!seeded.isNull()) return seeded;
		}

		/* $this->walkScope = $this->scopeFactory->toWalkScopeFactory()->create(...) */
		zv::Val created = pt_mutating_scope_create_walk_scope(self);
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		write(slots::walkScope, zv::Val::copyOf(created.ref()));
		return created;
	}

	/* getType() / getNativeType(): TypeExpr answers itself, the rest is
	 * memoized per node identity — $this->askedTypes[spl_object_id($node)] =
	 * [$node, $type], the node kept so a reused object id misses */
	zv::Val getType(zend_object *node, bool native) const
	{
		if (isTypeExpr(node)) return typeExprType(node);

		uint32_t memoSlot = native ? slots::askedNativeTypes : slots::askedTypes;
		zval *memo = prop(memoSlot);
		if (EXPECTED(Z_TYPE_P(memo) == IS_ARRAY)) {
			zval *entry = zend_hash_index_find(Z_ARRVAL_P(memo), (zend_ulong) node->handle);
			if (entry != NULL && Z_TYPE_P(entry) == IS_ARRAY) {
				zval *askedNode = zend_hash_index_find(Z_ARRVAL_P(entry), 0);
				if (askedNode != NULL && Z_TYPE_P(askedNode) == IS_OBJECT && Z_OBJ_P(askedNode) == node) {
					zval *askedType = zend_hash_index_find(Z_ARRVAL_P(entry), 1);
					if (EXPECTED(askedType != NULL)) return zv::Val::copyOf(zv::Ref(askedType));
				}
			}
		}

		zv::Val type = doGetType(node, native);
		if (UNEXPECTED(type.isUndef())) return zv::Val();

		/* re-read: the ask may have re-entered this scope */
		memo = prop(memoSlot);
		if (EXPECTED(Z_TYPE_P(memo) == IS_ARRAY)) {
			zval nodeZv;
			ZVAL_OBJ(&nodeZv, node);
			zv::Arr pair = zv::Arr::create(2);
			pair.push(zv::Ref(&nodeZv));
			pair.push(type.ref());
			SEPARATE_ARRAY(memo);
			zval pairZv = pair.take();
			zend_hash_index_update(Z_ARRVAL_P(memo), (zend_ulong) node->handle, &pairZv);
		}
		return type;
	}

	/* getScopeType() / getScopeNativeType(): $this->toWalkScope()->getType($expr) */
	zv::Val getScopeType(zval *expr, bool native) const
	{
		zv::Val walkScope = toWalkScope();
		if (UNEXPECTED(walkScope.isUndef())) return zv::Val();
		return typeOnScope(walkScope.raw(), expr, native);
	}

	zv::Val getKeepVoidType(zend_object *node) const
	{
		zv::Val storedResult = pt_mutating_scope_find_settled_stored_result(self, node);
		if (UNEXPECTED(storedResult.isUndef())) return zv::Val();
		zv::Val scope;
		if (!storedResult.isNull()) {
			scope = preprocessStoredResultScope(storedResult.raw());
		} else {
			scope = toWalkScope();
		}
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zend_object *scopeObject = requireObject(scope.raw(), "getKeepVoidType");
		if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		return pt_mutating_scope_get_keep_void_type(scopeObject, &nodeZv);
	}

	/* filterByTruthyValue() / filterByFalseyValue(): the parent body, then
	 * the filter lists carried over and extended on the resulting scope (which
	 * may be this scope itself) */
	zv::Val filterByValue(zend_object *expr, bool truthy) const
	{
		zv::Val scope = pt_mutating_scope_parent_filter_by_value(self, expr, truthy);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		if (UNEXPECTED(!carryFilterLists(scope.raw()))) return zv::Val();
		zval exprZv;
		ZVAL_OBJ(&exprZv, expr);
		if (UNEXPECTED(!appendFilter(scope.raw(), truthy, &exprZv))) return zv::Val();
		return scope;
	}

	zv::Val pushInFunctionCall(zval *reflection, zval *parameter, bool rememberTypes) const
	{
		zv::Val scope = pt_mutating_scope_parent_push_in_function_call(self, reflection, parameter, rememberTypes);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		if (UNEXPECTED(!carryFilterLists(scope.raw()))) return zv::Val();
		return scope;
	}

	zv::Val popInFunctionCall() const
	{
		/* $stack = $this->inFunctionCallsStack; array_pop($stack); — a
		 * local copy, only the read of the typed property is observable */
		if (UNEXPECTED(Z_TYPE_P(prop(msSlots::inFunctionCallsStack)) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property PHPStan\\Analyser\\MutatingScope::$inFunctionCallsStack must not be accessed before initialization");
			return zv::Val();
		}
		zv::Val scope = pt_mutating_scope_parent_pop_in_function_call(self);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		if (UNEXPECTED(!carryFilterLists(scope.raw()))) return zv::Val();
		return scope;
	}

	zv::Val getParentScope() const
	{
		zv::Val parent = pt_mutating_scope_parent_get_parent_scope(self);
		if (UNEXPECTED(parent.isUndef()) || parent.isNull()) return parent;
		return pt_mutating_scope_to_node_callback_scope(Z_OBJ_P(parent.raw()));
	}

private:
	zend_object *self;

	zval *prop(uint32_t slot) const { return OBJ_PROP_NUM(self, slot); }

	zv::Val selfValue() const
	{
		zval z;
		ZVAL_OBJ_COPY(&z, self);
		return zv::Val::adopt(z);
	}

	void write(uint32_t slot, zv::Val value) const { zv::Ref(prop(slot)).assign(std::move(value)); }

	bool filtersEmpty() const
	{
		zval *truthy = prop(slots::truthyValueExprs);
		zval *falsey = prop(slots::falseyValueExprs);
		return zend_hash_num_elements(Z_ARRVAL_P(truthy)) == 0 && zend_hash_num_elements(Z_ARRVAL_P(falsey)) == 0;
	}

	/* doGetType() / doGetNativeType() */
	zv::Val doGetType(zend_object *node, bool native) const
	{
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(self, promoted))) return zv::Val();
		if (!promoted && filtersEmpty()) {
			zv::Val storedResult = pt_mutating_scope_find_settled_stored_result(self, node);
			if (UNEXPECTED(storedResult.isUndef())) return zv::Val();
			if (!storedResult.isNull()) return getStoredResultTypeOnThisScope(storedResult.raw(), &nodeZv, native);

			// post-order emission means every real subnode is already stored -
			// an unstored ask is a synthetic node or a node ahead of the walk,
			// answered on demand through the MutatingScope path
			zv::Val walkScope = toWalkScope();
			if (UNEXPECTED(walkScope.isUndef())) return zv::Val();
			return typeOnScope(walkScope.raw(), &nodeZv, native);
		}

		zv::Val storedResult = pt_mutating_scope_find_settled_stored_result(self, node);
		if (UNEXPECTED(storedResult.isUndef())) return zv::Val();
		if (!storedResult.isNull()) {
			zv::Val scope = preprocessStoredResultScope(storedResult.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			return typeOnScope(scope.raw(), &nodeZv, native);
		}

		// the filters/promotion already narrowed this scope's own tables
		zv::Val walkScope = toWalkScope();
		if (UNEXPECTED(walkScope.isUndef())) return zv::Val();
		return typeOnScope(walkScope.raw(), &nodeZv, native);
	}

	zv::Val getStoredResultTypeOnThisScope(zval *result, zval *node, bool native) const
	{
		zv::Val scope = toWalkScope();
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		bool canResolveOwnType;
		if (UNEXPECTED(!pt_expression_result_can_resolve_own_type(Z_OBJ_P(result), canResolveOwnType))) return zv::Val();
		if (canResolveOwnType) {
			bool matches;
			if (UNEXPECTED(!pt_expression_result_ask_scope_variable_state_matches(result, scope.raw(), native, matches, true))) return zv::Val();
			if (matches) return native ? pt_expression_result_get_native_type(result) : pt_expression_result_get_type(result);
		}

		return typeOnScope(scope.raw(), node, native);
	}

	/* $this->preprocessScope($storedResult->getBeforeScope()) */
	zv::Val preprocessStoredResultScope(zval *storedResult) const
	{
		zv::Val hold;
		zval *beforeScope = pt_expression_result_before_scope(storedResult, hold);
		if (UNEXPECTED(beforeScope == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(beforeScope) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(beforeScope), pt_ce_mutating_scope))) {
			zend_type_error("PHPStan\\Analyser\\NodeCallbackScope::preprocessScope(): Argument #1 ($scope) must be of type PHPStan\\Analyser\\MutatingScope, %s given", zend_zval_value_name(beforeScope));
			return zv::Val();
		}
		return preprocessScope(Z_OBJ_P(beforeScope));
	}

	zv::Val preprocessScope(zend_object *beforeScope) const
	{
		// a nested walk a rule started from its NodeCallbackScope may have
		// anchored results to callback scopes - re-entering this class's ask
		// paths from here would derive scopes without end
		zv::Val scope = pt_mutating_scope_to_walk_scope(beforeScope);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(self, promoted))) return zv::Val();
		if (promoted) {
			zend_object *scopeObject = requireObject(scope.raw(), "doNotTreatPhpDocTypesAsCertain");
			if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
			scope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(scopeObject);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}

		for (bool truthy : { true, false }) {
			/* foreach iterates the array as it was when the loop started */
			zv::Val filters = zv::Val::copyOf(zv::Ref(prop(truthy ? slots::truthyValueExprs : slots::falseyValueExprs)));
			for (zv::ArrayEntry entry : zv::ArrRef(filters.raw())) {
				zv::Ref expr = entry.value().deref();
				zend_object *scopeObject = requireObject(scope.raw(), truthy ? "filterByTruthyValue" : "filterByFalseyValue");
				if (UNEXPECTED(scopeObject == NULL)) return zv::Val();
				if (UNEXPECTED(!expr.isObject())) {
					zend_type_error("phpstan_turbo: a NodeCallbackScope filter is not an expression, %s given", zend_zval_value_name(expr.raw()));
					return zv::Val();
				}
				scope = pt_mutating_scope_filter_by_value(scopeObject, expr.asObject(), truthy);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		return scope;
	}

	/* $scope->truthyValueExprs = $this->truthyValueExprs;
	 * $scope->falseyValueExprs = $this->falseyValueExprs; */
	[[nodiscard]] bool carryFilterLists(zval *scope) const
	{
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to assign property \"truthyValueExprs\" on %s", zend_zval_value_name(scope));
			return false;
		}
		zend_object *target = Z_OBJ_P(scope);
		if (EXPECTED(target->ce == self->ce)) {
			if (target == self) return true;
			NodeCallbackScope(target).write(slots::truthyValueExprs, zv::Val::copyOf(zv::Ref(prop(slots::truthyValueExprs))));
			NodeCallbackScope(target).write(slots::falseyValueExprs, zv::Val::copyOf(zv::Ref(prop(slots::falseyValueExprs))));
			return true;
		}
		zend_update_property(self->ce, target, PT_LC("truthyValueExprs"), prop(slots::truthyValueExprs));
		if (UNEXPECTED(EG(exception))) return false;
		zend_update_property(self->ce, target, PT_LC("falseyValueExprs"), prop(slots::falseyValueExprs));
		return EXPECTED(EG(exception) == NULL);
	}

	/* $scope->truthyValueExprs[] = $expr; (or falseyValueExprs) */
	[[nodiscard]] bool appendFilter(zval *scope, bool truthy, zval *expr) const
	{
		zend_object *target = Z_OBJ_P(scope);
		if (EXPECTED(target->ce == self->ce)) {
			zval *list = OBJ_PROP_NUM(target, truthy ? slots::truthyValueExprs : slots::falseyValueExprs);
			SEPARATE_ARRAY(list);
			Z_ADDREF_P(expr);
			if (UNEXPECTED(zend_hash_next_index_insert(Z_ARRVAL_P(list), expr) == NULL)) {
				Z_DELREF_P(expr);
				zend_throw_error(NULL, "Cannot add element to the array as the next element is already occupied");
				return false;
			}
			return true;
		}
		const char *name = truthy ? "truthyValueExprs" : "falseyValueExprs";
		zval *list = zend_read_property(self->ce, target, name, strlen(name), false, NULL);
		if (UNEXPECTED(EG(exception))) return false;
		zval copy;
		if (list != NULL && Z_TYPE_P(list) == IS_ARRAY) {
			ZVAL_ARR(&copy, zend_array_dup(Z_ARRVAL_P(list)));
		} else {
			array_init(&copy);
		}
		Z_ADDREF_P(expr);
		zend_hash_next_index_insert(Z_ARRVAL(copy), expr);
		zend_update_property(self->ce, target, name, strlen(name), &copy);
		zval_ptr_dtor(&copy);
		return EXPECTED(EG(exception) == NULL);
	}
};

} // namespace phpstanturbo

using phpstanturbo::NodeCallbackScope;

/* {{{ WeakReference::create($referent) / $weakReference->get() (support.h):
 * the engine's internal methods — there is no C API for either — their
 * zend_functions resolved once (an internal class's function table lives as
 * long as the process) */

zv::Val pt_weak_reference_create(zval *referent)
{
	return weakReferenceCall(pt_ncs_weakref_create_fn, PT_LC("create"), NULL, 1, referent);
}

zv::Val pt_weak_reference_get(zend_object *weakReference)
{
	return weakReferenceCall(pt_ncs_weakref_get_fn, PT_LC("get"), weakReference, 0, NULL);
}

/* }}} */

/* {{{ direct entries (support.h): the receiver is exactly the native class */

bool pt_node_callback_scope_seed_walk_scope(zend_object *scope, zval *walkScope)
{
	return NodeCallbackScope(scope).seedWalkScope(walkScope);
}

zv::Val pt_node_callback_scope_get_type(zend_object *scope, zend_object *node)
{
	return NodeCallbackScope(scope).getType(node, false);
}

zv::Val pt_node_callback_scope_get_native_type(zend_object *scope, zend_object *node)
{
	return NodeCallbackScope(scope).getType(node, true);
}

zv::Val pt_node_callback_scope_get_keep_void_type(zend_object *scope, zend_object *node)
{
	return NodeCallbackScope(scope).getKeepVoidType(node);
}

zv::Val pt_node_callback_scope_to_walk_scope(zend_object *scope)
{
	return NodeCallbackScope(scope).toWalkScope();
}

zv::Val pt_node_callback_scope_filter_by_value(zend_object *scope, zend_object *expr, bool truthy)
{
	return NodeCallbackScope(scope).filterByValue(expr, truthy);
}

zv::Val pt_node_callback_scope_push_in_function_call(zend_object *scope, zval *reflection, zval *parameter, bool rememberTypes)
{
	return NodeCallbackScope(scope).pushInFunctionCall(reflection, parameter, rememberTypes);
}

zv::Val pt_node_callback_scope_pop_in_function_call(zend_object *scope)
{
	return NodeCallbackScope(scope).popInFunctionCall();
}

zv::Val pt_node_callback_scope_get_parent_scope(zend_object *scope)
{
	return NodeCallbackScope(scope).getParentScope();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

namespace {

#define PT_NCS_THIS NodeCallbackScope(Z_OBJ_P(ZEND_THIS))

#define PT_NCS_PARSE_EXPR(var) \
	zval *var; \
	do { \
		zend_class_entry *exprCe_ = pt_class(PT_CLASS_EXPR); \
		if (UNEXPECTED(exprCe_ == NULL)) { \
			RETURN_THROWS(); \
		} \
		ZEND_PARSE_PARAMETERS_START(1, 1) \
			Z_PARAM_OBJECT_OF_CLASS(var, exprCe_) \
		ZEND_PARSE_PARAMETERS_END(); \
	} while (0)

} // namespace

void pt_register_node_callback_scope()
{
	reg::Class cls("PHPStan\\Analyser\\NodeCallbackScope");
	ptdecl::NodeCallbackScope::declareClass(cls);
	ptdecl::NodeCallbackScope::declareProperties(cls);

	cls.method(sigs::toNodeCallbackScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_NCS_THIS.toNodeCallbackScope());
	});

	cls.method(sigs::seedWalkScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(scope, pt_ce_mutating_scope)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!PT_NCS_THIS.seedWalkScope(scope))) RETURN_THROWS();
	});

	cls.method(sigs::toWalkScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_NCS_THIS.toWalkScope());
	});

	cls.method(sigs::getType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_NCS_PARSE_EXPR(node);
		PT_RETURN_VAL(PT_NCS_THIS.getType(Z_OBJ_P(node), false));
	});

	cls.method(sigs::getScopeType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_NCS_PARSE_EXPR(expr);
		PT_RETURN_VAL(PT_NCS_THIS.getScopeType(expr, false));
	});

	cls.method(sigs::getScopeNativeType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_NCS_PARSE_EXPR(expr);
		PT_RETURN_VAL(PT_NCS_THIS.getScopeType(expr, true));
	});

	cls.method(sigs::getNativeType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_NCS_PARSE_EXPR(expr);
		PT_RETURN_VAL(PT_NCS_THIS.getType(Z_OBJ_P(expr), true));
	});

	cls.method(sigs::getKeepVoidType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_NCS_PARSE_EXPR(node);
		PT_RETURN_VAL(PT_NCS_THIS.getKeepVoidType(Z_OBJ_P(node)));
	});

	cls.method(sigs::filterByTruthyValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_NCS_PARSE_EXPR(expr);
		PT_RETURN_VAL(PT_NCS_THIS.filterByValue(Z_OBJ_P(expr), true));
	});

	cls.method(sigs::filterByFalseyValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_NCS_PARSE_EXPR(expr);
		PT_RETURN_VAL(PT_NCS_THIS.filterByValue(Z_OBJ_P(expr), false));
	});

	cls.method(sigs::pushInFunctionCall, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflection, *parameter;
		bool rememberTypes;
		if (!zp::parse<zp::Zval, zp::ObjOrNull, zp::Bool>(execute_data, reflection, parameter, rememberTypes)) RETURN_THROWS();
		ZVAL_DEREF(reflection);
		zval nullZv;
		if (parameter == NULL) {
			ZVAL_NULL(&nullZv);
			parameter = &nullZv;
		}
		PT_RETURN_VAL(PT_NCS_THIS.pushInFunctionCall(reflection, parameter, rememberTypes));
	});

	cls.method(sigs::popInFunctionCall, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_NCS_THIS.popInFunctionCall());
	});

	cls.method(sigs::getParentScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_NCS_THIS.getParentScope());
	});

	cls.shadow(&pt_ce_node_callback_scope);
}

/* }}} */
