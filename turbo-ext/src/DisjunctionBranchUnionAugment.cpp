/*
 * PHPStanTurbo\DisjunctionBranchUnionAugment — native implementation of
 * PHPStan\Analyser\DisjunctionBranchUnionAugment.
 *
 * A final DeferredSpecifiedTypesAugment: the compose-time candidates live in
 * the twin's property slots (generated declarations); evaluate() runs the
 * applying-scope gates natively (MutatingScope.cpp's hasExpressionType(),
 * TypeUtils, the Type ops, the TypeCombinator) and calls the PHP
 * NodeScopeResolver / DefaultNarrowingHelper by name from local helpers.
 *
 * MutatingScope::applySpecifiedTypes() reaches evaluate() through
 * pt_disjunction_branch_union_augment_evaluate(); the narrowing helper
 * builds it through pt_disjunction_branch_union_augment_new().
 */

#include "support.h"
#include "generated/DisjunctionBranchUnionAugment.h"

namespace slots = ptdecl::DisjunctionBranchUnionAugment::slot;
namespace sigs = ptdecl::DisjunctionBranchUnionAugment::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

zend_class_entry *pt_ce_disjunction_branch_union_augment = NULL;

namespace {

/* {{{ the analyser classes still PHP: one local helper per call */

/* $nodeScopeResolver->requireScopeStateType($expr, $scope) */
zv::Val requireScopeStateType(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	zv::Args args{expr, scope};
	return pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("requirescopestatetype"), 2, args);
}

/* $defaultNarrowingHelper->createForSubject($subject, $type, $context, $scope) */
zv::Val createForSubject(zval *defaultNarrowingHelper, zval *subject, zval *type, zend_object *context, zval *scope)
{
	zv::Args args{subject, type, context, scope};
	return pt_type_call(Z_OBJ_P(defaultNarrowingHelper), PT_LC("createforsubject"), 4, args);
}

/* }}} */

/* $type->isSuperTypeOf($other)->yes(); -1 = pending exception, else 0 / 1 */
int isSuperTypeOfYes(zval *type, zval *other)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(type));
		return -1;
	}
	zv::Val result = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, other);
	if (UNEXPECTED(result.isUndef())) return -1;
	zend_long verdict = pt_type_result_trinary(result.raw());
	if (UNEXPECTED(verdict < 0)) return -1;
	return verdict == PT_TRI_YES ? 1 : 0;
}

/* $type->equals($other); -1 = pending exception, else 0 / 1 */
int typeEquals(zval *type, zval *other)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(type));
		return -1;
	}
	bool equal = pt_call_type_equals(type, other);
	if (UNEXPECTED(EG(exception))) return -1;
	return equal ? 1 : 0;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\DisjunctionBranchUnionAugment. */
class DisjunctionBranchUnionAugment
{
public:
	explicit DisjunctionBranchUnionAugment(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties */
	static void construct(zend_object *object, zval *nodeScopeResolver, zval *defaultNarrowingHelper, zval *candidates)
	{
		writeSlot(object, slots::nodeScopeResolver, zv::Val::copyOf(zv::Ref(nodeScopeResolver)));
		writeSlot(object, slots::defaultNarrowingHelper, zv::Val::copyOf(zv::Ref(defaultNarrowingHelper)));
		writeSlot(object, slots::candidates, zv::Val::copyOf(zv::Ref(candidates)));
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *nodeScopeResolver, zval *defaultNarrowingHelper, zval *candidates)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_disjunction_branch_union_augment) != SUCCESS)) return zv::Val();
		construct(Z_OBJ(object), nodeScopeResolver, defaultNarrowingHelper, candidates);
		return zv::Val::adopt(object);
	}

	/* Mirrors evaluate(); the SpecifiedTypes or PHP null, UNDEF = pending
	 * exception */
	zv::Val evaluate(zval *scope) const
	{
		zval *candidates = slot(slots::candidates);
		if (UNEXPECTED(Z_TYPE_P(candidates) != IS_ARRAY)) return uninitialized("candidates");

		zv::Val result = zv::Val::null();
		for (zv::ArrayEntry entry : zv::ArrRef(candidates)) {
			zval *tuple = entry.value().deref().raw();
			zval *targetExpr = tupleAt(tuple, 0);
			zval *leftType = tupleAt(tuple, 1);
			zval *rightType = tupleAt(tuple, 2);
			if (UNEXPECTED(targetExpr == NULL || Z_TYPE_P(targetExpr) != IS_OBJECT || leftType == NULL || rightType == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: a DisjunctionBranchUnionAugment candidate is malformed");
				return zv::Val();
			}

			zend_long hasExpressionType = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), targetExpr);
			if (UNEXPECTED(hasExpressionType < 0)) return zv::Val();
			if (hasExpressionType != PT_TRI_YES) continue;

			// the guard above pins the target as tracked on the applying scope
			zval *nodeScopeResolver = slot(slots::nodeScopeResolver);
			if (UNEXPECTED(Z_TYPE_P(nodeScopeResolver) != IS_OBJECT)) return uninitialized("nodeScopeResolver");
			zv::Val originalType = requireScopeStateType(nodeScopeResolver, targetExpr, scope);
			if (UNEXPECTED(originalType.isUndef())) return zv::Val();
			// re-pinning eagerly priced branch forms of a template-typed subject
			// stacks the template inside its own bound (`T of T of ...` - the
			// pin intersects with the declared template); its narrowing already
			// flows through the operands' exact merge
			bool containsTemplateType;
			if (UNEXPECTED(!pt_type_utils_contains_template_type(originalType.raw(), containsTemplateType))) return zv::Val();
			if (containsTemplateType) continue;

			int leftEquals = typeEquals(leftType, originalType.raw());
			if (UNEXPECTED(leftEquals < 0)) return zv::Val();
			if (leftEquals) continue;
			int leftIsSubType = isSuperTypeOfYes(originalType.raw(), leftType);
			if (UNEXPECTED(leftIsSubType < 0)) return zv::Val();
			if (!leftIsSubType) continue;

			int rightEquals = typeEquals(rightType, originalType.raw());
			if (UNEXPECTED(rightEquals < 0)) return zv::Val();
			if (rightEquals) continue;
			int rightIsSubType = isSuperTypeOfYes(originalType.raw(), rightType);
			if (UNEXPECTED(rightIsSubType < 0)) return zv::Val();
			if (!rightIsSubType) continue;

			zv::Args unionArgs{leftType, rightType};
			zv::Val unionType = pt_type_combinator_union(2, unionArgs);
			if (UNEXPECTED(unionType.isUndef())) return zv::Val();
			// a union that covers the whole original type gains no narrowing -
			// pinning it would only stack a redundant intersection on the
			// expression (e.g. re-wrapping a template type in its own bound)
			int covers = isSuperTypeOfYes(unionType.raw(), originalType.raw());
			if (UNEXPECTED(covers < 0)) return zv::Val();
			if (covers) continue;

			zend_object *context = pt_type_specifier_context_create_true();
			if (UNEXPECTED(context == NULL)) return zv::Val();
			zval *defaultNarrowingHelper = slot(slots::defaultNarrowingHelper);
			if (UNEXPECTED(Z_TYPE_P(defaultNarrowingHelper) != IS_OBJECT)) return uninitialized("defaultNarrowingHelper");
			zv::Val created = createForSubject(defaultNarrowingHelper, targetExpr, unionType.raw(), context, scope);
			if (UNEXPECTED(created.isUndef())) return zv::Val();
			if (Z_TYPE_P(result.raw()) == IS_NULL) {
				result = std::move(created);
				continue;
			}
			zv::Val united = pt_specified_types_union_with(Z_OBJ_P(result.raw()), created.raw());
			if (UNEXPECTED(united.isUndef())) return zv::Val();
			result = std::move(united);
		}

		return result;
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	/* $tuple[$index] (dereferenced), NULL when the tuple is no array or lacks it */
	static zval *tupleAt(zval *tuple, zend_ulong index)
	{
		if (Z_TYPE_P(tuple) != IS_ARRAY) return NULL;
		zval *value = zend_hash_index_find(Z_ARRVAL_P(tuple), index);
		if (value == NULL) return NULL;
		ZVAL_DEREF(value);
		return value;
	}

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

using phpstanturbo::DisjunctionBranchUnionAugment;

/* {{{ direct entries (support.h) */

zv::Val pt_disjunction_branch_union_augment_new(zval *nodeScopeResolver, zval *defaultNarrowingHelper, zval *candidates)
{
	if (UNEXPECTED(pt_ce_disjunction_branch_union_augment == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: DisjunctionBranchUnionAugment used before the shadowing classes were activated");
		return zv::Val();
	}
	return DisjunctionBranchUnionAugment::create(nodeScopeResolver, defaultNarrowingHelper, candidates);
}

zv::Val pt_disjunction_branch_union_augment_evaluate(zend_object *augment, zval *scope)
{
	if (EXPECTED(augment->ce == pt_ce_disjunction_branch_union_augment && Z_TYPE_P(scope) == IS_OBJECT)) return DisjunctionBranchUnionAugment(augment).evaluate(scope);
	return pt_type_call(augment, PT_LC("evaluate"), 1, scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_disjunction_branch_union_augment()
{
	reg::Class cls("PHPStan\\Analyser\\DisjunctionBranchUnionAugment");
	ptdecl::DisjunctionBranchUnionAugment::declareClass(cls);
	ptdecl::DisjunctionBranchUnionAugment::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *defaultNarrowingHelper, *candidates;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Arr>(execute_data, nodeScopeResolver, defaultNarrowingHelper, candidates)) RETURN_THROWS();
		DisjunctionBranchUnionAugment::construct(Z_OBJ_P(ZEND_THIS), nodeScopeResolver, defaultNarrowingHelper, candidates);
	});

	cls.method(sigs::evaluate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		if (!zp::parse<zp::Obj>(execute_data, scope)) RETURN_THROWS();
		PT_RETURN_VAL(DisjunctionBranchUnionAugment(Z_OBJ_P(ZEND_THIS)).evaluate(scope));
	});

	cls.shadow(&pt_ce_disjunction_branch_union_augment);
}

/* }}} */
