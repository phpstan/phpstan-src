/*
 * PHPStanTurbo\DisjunctionHolderProjectionAugment — native implementation of
 * PHPStan\Analyser\DisjunctionHolderProjectionAugment.
 *
 * A final DeferredSpecifiedTypesAugment: the compose-time state (the operand
 * truthy-scope thunks, the left-falsey scope, the alternative keys) lives in
 * the twin's property slots (generated declarations); evaluate() walks the
 * scopes' conditional holders natively (MutatingScope.cpp, the native holder
 * slots), resolves the thunks at most once each, runs the Type gates through
 * the native ops and TypeCombinator, and calls the PHP NodeScopeResolver by
 * name and DefaultNarrowingHelper through its direct entries, from local
 * helpers.
 *
 * MutatingScope::applySpecifiedTypes() reaches evaluate() through
 * pt_disjunction_holder_projection_augment_evaluate(); the narrowing helper
 * builds it through pt_disjunction_holder_projection_augment_new().
 */

#include "support.h"
#include "generated/DisjunctionHolderProjectionAugment.h"

namespace slots = ptdecl::DisjunctionHolderProjectionAugment::slot;
namespace sigs = ptdecl::DisjunctionHolderProjectionAugment::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

zend_class_entry *pt_ce_disjunction_holder_projection_augment = NULL;

namespace {

/* {{{ the analyser classes still PHP: one local helper per call */

/* $nodeScopeResolver->requireScopeStateType($expr, $scope) */
zv::Val requireScopeStateType(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	return pt_node_scope_resolver_require_scope_state_type(nodeScopeResolver, expr, scope);
}

/* $defaultNarrowingHelper->createSubjectTypes($scope, $subject, null, $type, $context) */
zv::Val createSubjectTypes(zval *defaultNarrowingHelper, zval *scope, zval *subject, zval *type, zend_object *context)
{
	zval contextZv;
	ZVAL_OBJ(&contextZv, context);
	return pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, scope, subject, NULL, type, &contextZv);
}

/* }}} */

/* $holder->getTypeHolder()->getExpr(): the native holders' slots, the
 * methods of anything else; UNDEF = pending exception */
zv::Val holderTargetExpr(zval *holder)
{
	if (UNEXPECTED(Z_TYPE_P(holder) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getTypeHolder() on %s", zend_zval_value_name(holder));
		return zv::Val();
	}
	zv::Val typeHolder = Z_OBJCE_P(holder) == pt_ce_cond_expr_holder
		? zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(holder), PT_CEH_PROP_TYPEHOLDER)))
		: pt_type_call(Z_OBJ_P(holder), PT_LC("gettypeholder"), 0, NULL);
	if (UNEXPECTED(typeHolder.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(typeHolder.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getExpr() on %s", zend_zval_value_name(typeHolder.raw()));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(typeHolder.raw()) == pt_ce_expr_type_holder)) return zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(typeHolder.raw()), PT_ETH_PROP_EXPR)));
	return pt_type_call(Z_OBJ_P(typeHolder.raw()), PT_LC("getexpr"), 0, NULL);
}

/* $scope->hasExpressionType($expr)->yes() on a scope value; -1 = pending
 * exception, else 0 / 1 */
int hasExpressionTypeYes(zval *scope, zval *expr)
{
	if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function hasExpressionType() on %s", zend_zval_value_name(scope));
		return -1;
	}
	zend_long verdict = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), expr);
	if (UNEXPECTED(verdict < 0)) return -1;
	return verdict == PT_TRI_YES ? 1 : 0;
}

/* !$type->equals($original) && $original->isSuperTypeOf($type)->yes(); -1 =
 * pending exception, else 0 / 1 */
int narrows(zval *type, zval *original)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT || Z_TYPE_P(original) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: requireScopeStateType() did not answer with a Type");
		return -1;
	}
	bool equal = pt_call_type_equals(type, original);
	if (UNEXPECTED(EG(exception))) return -1;
	if (equal) return 0;
	zv::Val result = pt_type_op(Z_OBJ_P(original), PT_OP_IS_SUPER_TYPE_OF, 1, type);
	if (UNEXPECTED(result.isUndef())) return -1;
	zend_long verdict = pt_type_result_trinary(result.raw());
	if (UNEXPECTED(verdict < 0)) return -1;
	return verdict == PT_TRI_YES ? 1 : 0;
}

/* isset($table[$key]) for a key taken from another array */
bool issetKey(zval *table, zend_string *skey, zend_ulong h)
{
	if (Z_TYPE_P(table) != IS_ARRAY) return false;
	zval *found = pt_ht_find(Z_ARRVAL_P(table), skey, h);
	if (found == NULL) return false;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) != IS_NULL;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\DisjunctionHolderProjectionAugment. */
class DisjunctionHolderProjectionAugment
{
public:
	explicit DisjunctionHolderProjectionAugment(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties */
	static void construct(zend_object *object, zval *nodeScopeResolver, zval *defaultNarrowingHelper, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightTruthyScope, zval *alternativeKeys)
	{
		writeSlot(object, slots::nodeScopeResolver, zv::Val::copyOf(zv::Ref(nodeScopeResolver)));
		writeSlot(object, slots::defaultNarrowingHelper, zv::Val::copyOf(zv::Ref(defaultNarrowingHelper)));
		writeSlot(object, slots::leftTruthyScope, zv::Val::copyOf(zv::Ref(leftTruthyScope).deref()));
		writeSlot(object, slots::leftFalseyScope, zv::Val::copyOf(zv::Ref(leftFalseyScope)));
		writeSlot(object, slots::rightTruthyScope, zv::Val::copyOf(zv::Ref(rightTruthyScope).deref()));
		writeSlot(object, slots::alternativeKeys, zv::Val::copyOf(zv::Ref(alternativeKeys)));
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *nodeScopeResolver, zval *defaultNarrowingHelper, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightTruthyScope, zval *alternativeKeys)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_disjunction_holder_projection_augment) != SUCCESS)) return zv::Val();
		construct(Z_OBJ(object), nodeScopeResolver, defaultNarrowingHelper, leftTruthyScope, leftFalseyScope, rightTruthyScope, alternativeKeys);
		return zv::Val::adopt(object);
	}

	/* Mirrors evaluate(); the SpecifiedTypes or PHP null, UNDEF = pending
	 * exception */
	zv::Val evaluate(zval *scope) const
	{
		zv::Val result = zv::Val::null();
		zv::ScratchTable seen(8);
		zv::Val leftTruthyScope;
		zv::Val rightTruthyScope;

		zval *leftFalseyScopeSlot = slot(slots::leftFalseyScope);
		if (UNEXPECTED(Z_TYPE_P(leftFalseyScopeSlot) != IS_OBJECT)) return uninitialized("leftFalseyScope");
		/* [$scope, $this->leftFalseyScope], built before the loop */
		zv::Val leftFalseyScope = zv::Val::copyOf(zv::Ref(leftFalseyScopeSlot));
		zval *sourceScopes[2] = { scope, leftFalseyScope.raw() };

		for (zval *sourceScope : sourceScopes) {
			zv::Val conditionalExpressions = pt_mutating_scope_get_conditional_expressions(Z_OBJ_P(sourceScope));
			if (UNEXPECTED(conditionalExpressions.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(conditionalExpressions.raw()) != IS_ARRAY)) {
				zend_type_error("phpstan_turbo: getConditionalExpressions() must return array, %s returned", zend_zval_value_name(conditionalExpressions.raw()));
				return zv::Val();
			}
			for (zv::ArrayEntry entry : zv::ArrRef(conditionalExpressions.raw())) {
				zend_string *rootExprString = entry.stringKeyOrNull();
				zend_ulong rootExprIndex = entry.indexKey();
				if (pt_ht_find(seen.table(), rootExprString, rootExprIndex) != NULL) continue;
				zval *holders = entry.value().deref().raw();
				if (Z_TYPE_P(holders) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(holders)) == 0) continue;
				zval flag;
				ZVAL_TRUE(&flag);
				pt_ht_update(seen.table(), rootExprString, rootExprIndex, &flag);
				if (UNEXPECTED(Z_TYPE_P(holders) != IS_ARRAY)) {
					zend_type_error("array_key_first(): Argument #1 ($array) must be of type array, %s given", zend_zval_value_name(holders));
					return zv::Val();
				}
				zval *firstHolder = NULL;
				for (zv::ArrayEntry holderEntry : zv::ArrRef(holders)) {
					firstHolder = holderEntry.value().deref().raw();
					break;
				}
				zv::Val targetExpr = holderTargetExpr(firstHolder);
				if (UNEXPECTED(targetExpr.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(targetExpr.raw()) != IS_OBJECT)) {
					zend_type_error("PHPStan\\Analyser\\MutatingScope::hasExpressionType(): Argument #1 ($node) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(targetExpr.raw()));
					return zv::Val();
				}

				zval *alternativeKeys = slot(slots::alternativeKeys);
				if (UNEXPECTED(Z_TYPE_P(alternativeKeys) != IS_ARRAY)) return uninitialized("alternativeKeys");
				if (issetKey(alternativeKeys, rootExprString, rootExprIndex)) continue;

				// Only project when the target stays Yes-defined in the original
				// scope and in both filtered branches. A sure type implicitly
				// raises certainty to Yes, which would wrongly upgrade Maybe-defined
				// variables — `if (empty($a['bar']))` for instance leaves `$a`
				// Maybe-defined because `empty()` tolerates undefined offsets.
				int inScope = hasExpressionTypeYes(scope, targetExpr.raw());
				if (UNEXPECTED(inScope < 0)) return zv::Val();
				if (!inScope) continue;
				if (leftTruthyScope.isNull()) {
					leftTruthyScope = pt_type_call_callable(slot(slots::leftTruthyScope), 0, NULL);
					if (UNEXPECTED(leftTruthyScope.isUndef())) return zv::Val();
				}
				if (rightTruthyScope.isNull()) {
					rightTruthyScope = pt_type_call_callable(slot(slots::rightTruthyScope), 0, NULL);
					if (UNEXPECTED(rightTruthyScope.isUndef())) return zv::Val();
				}
				int inLeft = hasExpressionTypeYes(leftTruthyScope.raw(), targetExpr.raw());
				if (UNEXPECTED(inLeft < 0)) return zv::Val();
				if (!inLeft) continue;
				int inRight = hasExpressionTypeYes(rightTruthyScope.raw(), targetExpr.raw());
				if (UNEXPECTED(inRight < 0)) return zv::Val();
				if (!inRight) continue;

				// the guards above pin the target as tracked on all three scopes -
				// scope state answers without a walk
				zval *nodeScopeResolver = slot(slots::nodeScopeResolver);
				if (UNEXPECTED(Z_TYPE_P(nodeScopeResolver) != IS_OBJECT)) return uninitialized("nodeScopeResolver");
				zv::Val origType = requireScopeStateType(nodeScopeResolver, targetExpr.raw(), scope);
				if (UNEXPECTED(origType.isUndef())) return zv::Val();

				zv::Val leftType = requireScopeStateType(nodeScopeResolver, targetExpr.raw(), leftTruthyScope.raw());
				if (UNEXPECTED(leftType.isUndef())) return zv::Val();
				int leftNarrowed = narrows(leftType.raw(), origType.raw());
				if (UNEXPECTED(leftNarrowed < 0)) return zv::Val();
				if (!leftNarrowed) continue;

				zv::Val rightType = requireScopeStateType(nodeScopeResolver, targetExpr.raw(), rightTruthyScope.raw());
				if (UNEXPECTED(rightType.isUndef())) return zv::Val();
				int rightNarrowed = narrows(rightType.raw(), origType.raw());
				if (UNEXPECTED(rightNarrowed < 0)) return zv::Val();
				if (!rightNarrowed) continue;

				zv::Args unionArgs{leftType.raw(), rightType.raw()};
				zv::Val unionType = pt_type_combinator_union(2, unionArgs);
				if (UNEXPECTED(unionType.isUndef())) return zv::Val();
				bool equal = pt_call_type_equals(unionType.raw(), origType.raw());
				if (UNEXPECTED(EG(exception))) return zv::Val();
				if (equal) continue;

				zend_object *context = pt_type_specifier_context_create_true();
				if (UNEXPECTED(context == NULL)) return zv::Val();
				zval *defaultNarrowingHelper = slot(slots::defaultNarrowingHelper);
				if (UNEXPECTED(Z_TYPE_P(defaultNarrowingHelper) != IS_OBJECT)) return uninitialized("defaultNarrowingHelper");
				zv::Val created = createSubjectTypes(defaultNarrowingHelper, scope, targetExpr.raw(), unionType.raw(), context);
				if (UNEXPECTED(created.isUndef())) return zv::Val();
				if (Z_TYPE_P(result.raw()) == IS_NULL) {
					result = std::move(created);
					continue;
				}
				zv::Val united = pt_specified_types_union_with(Z_OBJ_P(result.raw()), created.raw());
				if (UNEXPECTED(united.isUndef())) return zv::Val();
				result = std::move(united);
			}
		}

		return result;
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	/* the engine's Error for reading a never-written typed property */
	zv::Val uninitialized(const char *property) const
	{
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), property);
		return zv::Val();
	}

	static void writeSlot(zend_object *object, uint32_t index, zv::Val value)
	{
		zv::ObjRef(object).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(object, index)) = 0; /* no longer IS_PROP_UNINIT */
	}
};

} // namespace phpstanturbo

using phpstanturbo::DisjunctionHolderProjectionAugment;

/* {{{ direct entries (support.h) */

zv::Val pt_disjunction_holder_projection_augment_new(zval *nodeScopeResolver, zval *defaultNarrowingHelper, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightTruthyScope, zval *alternativeKeys)
{
	if (UNEXPECTED(pt_ce_disjunction_holder_projection_augment == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: DisjunctionHolderProjectionAugment used before the shadowing classes were activated");
		return zv::Val();
	}
	return DisjunctionHolderProjectionAugment::create(nodeScopeResolver, defaultNarrowingHelper, leftTruthyScope, leftFalseyScope, rightTruthyScope, alternativeKeys);
}

zv::Val pt_disjunction_holder_projection_augment_evaluate(zend_object *augment, zval *scope)
{
	if (EXPECTED(augment->ce == pt_ce_disjunction_holder_projection_augment && Z_TYPE_P(scope) == IS_OBJECT)) return DisjunctionHolderProjectionAugment(augment).evaluate(scope);
	return pt_type_call(augment, PT_LC("evaluate"), 1, scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_disjunction_holder_projection_augment)
{
	reg::Class cls("PHPStan\\Analyser\\DisjunctionHolderProjectionAugment");
	ptdecl::DisjunctionHolderProjectionAugment::declareClass(cls);
	ptdecl::DisjunctionHolderProjectionAugment::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *defaultNarrowingHelper, *leftTruthyScope, *leftFalseyScope, *rightTruthyScope, *alternativeKeys;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Zval, zp::Obj, zp::Zval, zp::Arr>(execute_data, nodeScopeResolver, defaultNarrowingHelper, leftTruthyScope, leftFalseyScope, rightTruthyScope, alternativeKeys)) RETURN_THROWS();
		DisjunctionHolderProjectionAugment::construct(Z_OBJ_P(ZEND_THIS), nodeScopeResolver, defaultNarrowingHelper, leftTruthyScope, leftFalseyScope, rightTruthyScope, alternativeKeys);
	});

	cls.method(sigs::evaluate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		if (!zp::parse<zp::Obj>(execute_data, scope)) RETURN_THROWS();
		PT_RETURN_VAL(DisjunctionHolderProjectionAugment(Z_OBJ_P(ZEND_THIS)).evaluate(scope));
	});

	cls.shadow(&pt_ce_disjunction_holder_projection_augment);
}

/* }}} */
