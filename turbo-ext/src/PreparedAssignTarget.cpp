/*
 * PHPStanTurbo\PreparedAssignTarget — native implementation of
 * PHPStan\Analyser\PreparedAssignTarget.
 *
 * The pre-value half of an assignment AssignHandler::prepareTarget()
 * captures and applyWrite() reads back (~590K getter calls per
 * self-analysis). State lives in the twin's 26 promoted property slots, in
 * its order; the getters of the kind-specific parts throw the twin's
 * ShouldNotHappenException when the slot is null. Native creators use
 * pt_prepared_assign_target_new() with the constructor's positional
 * arguments.
 */

#include "support.h"
#include "generated/PreparedAssignTarget.h"

namespace slots = ptdecl::PreparedAssignTarget::slot;
namespace sigs = ptdecl::PreparedAssignTarget::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_prepared_assign_target = nullptr;

/* the constructor's positional parameter count and the required ones */
#define PT_PAT_ARG_COUNT 26
#define PT_PAT_REQUIRED_ARG_COUNT 11

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\PreparedAssignTarget. */
class PreparedAssignTarget
{
public:
	explicit PreparedAssignTarget(zend_object *self) : self(self) {}

	/* the constructor: argv holds the 26 positional arguments in the twin's
	 * order, an UNDEF slot for an omitted optional one (its default: null,
	 * [] for $targetChainResults) */
	void construct(zval *argv) const
	{
		for (uint32_t i = 0; i < PT_PAT_ARG_COUNT; i++) {
			zval value = {};
			if (Z_TYPE(argv[i]) != IS_UNDEF) {
				pt_write_slot(self, i, &argv[i]);
			} else {
				if (i == slots::targetChainResults) {
					ZVAL_EMPTY_ARRAY(&value);
				} else {
					ZVAL_NULL(&value);
				}
				pt_write_slot(self, i, &value);
			}
		}
	}

	/* new self(...$argv); UNDEF = pending exception */
	static zv::Val create(zval *argv)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_prepared_assign_target) != SUCCESS)) return zv::Val();
		PreparedAssignTarget(Z_OBJ(object)).construct(argv);
		return zv::Val::adopt(object);
	}

	zv::Val getKind() const { return read(slots::kind, "kind"); }
	zv::Val getVar() const { return read(slots::var, "var"); }
	zv::Val getAssignedExpr() const { return read(slots::assignedExpr, "assignedExpr"); }
	zv::Val getBeforeScope() const { return read(slots::beforeScope, "beforeScope"); }
	zv::Val getScope() const { return read(slots::scope, "scope"); }
	zv::Val enterExpressionAssign() const { return read(slots::enterExpressionAssign, "enterExpressionAssign"); }
	zv::Val isAssignOp() const { return read(slots::isAssignOp, "isAssignOp"); }
	zv::Val hasYield() const { return read(slots::hasYield, "hasYield"); }
	zv::Val getThrowPoints() const { return read(slots::throwPoints, "throwPoints"); }
	zv::Val getImpurePoints() const { return read(slots::impurePoints, "impurePoints"); }
	zv::Val isAlwaysTerminating() const { return read(slots::isAlwaysTerminating, "isAlwaysTerminating"); }
	zv::Val getRootVar() const { return readRequired(slots::rootVar, "rootVar"); }
	zv::Val getVarResult() const { return readRequired(slots::varResult, "varResult"); }
	zv::Val getDimFetchStack() const { return readRequired(slots::dimFetchStack, "dimFetchStack"); }
	zv::Val getAssignedPropertyExpr() const { return readRequired(slots::assignedPropertyExpr, "assignedPropertyExpr"); }
	zv::Val getOffsetTypes() const { return readRequired(slots::offsetTypes, "offsetTypes"); }
	zv::Val getOffsetNativeTypes() const { return readRequired(slots::offsetNativeTypes, "offsetNativeTypes"); }
	zv::Val getExistingOffsetTypes() const { return readRequired(slots::existingOffsetTypes, "existingOffsetTypes"); }
	zv::Val getExistingOffsetNativeTypes() const { return readRequired(slots::existingOffsetNativeTypes, "existingOffsetNativeTypes"); }
	zv::Val getOffsetSetTargetResult() const { return readRequired(slots::offsetSetTargetResult, "offsetSetTargetResult"); }
	zv::Val getObjectResult() const { return readRequired(slots::objectResult, "objectResult"); }
	zv::Val getPropertyName() const { return read(slots::propertyName, "propertyName"); }
	zv::Val getPropertyHolderType() const { return readRequired(slots::propertyHolderType, "propertyHolderType"); }
	zv::Val getTargetReadResult() const { return readRequired(slots::targetReadResult, "targetReadResult"); }
	zv::Val getTargetChainResults() const { return read(slots::targetChainResults, "targetChainResults"); }
	zv::Val getVariableNameResult() const { return read(slots::variableNameResult, "variableNameResult"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}

	/* the slot, or the twin's ShouldNotHappenException when it is null */
	zv::Val readRequired(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(value) == IS_NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return zv::Val::copyOf(zv::Ref(value));
	}
};

} // namespace phpstanturbo

using phpstanturbo::PreparedAssignTarget;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_prepared_assign_target_new(uint32_t argc, zval *argv)
{
	zval all[PT_PAT_ARG_COUNT];
	for (uint32_t i = 0; i < PT_PAT_ARG_COUNT; i++) {
		if (i < argc && Z_TYPE(argv[i]) != IS_UNDEF) {
			ZVAL_COPY_VALUE(&all[i], &argv[i]);
		} else {
			ZVAL_UNDEF(&all[i]);
		}
	}
	return PreparedAssignTarget::create(all);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_prepared_assign_target)
{
	reg::Class cls("PHPStan\\Analyser\\PreparedAssignTarget");
	ptdecl::PreparedAssignTarget::declareClass(cls);
	cls.publicClassConstantString("KIND_VARIABLE", "variable");
	cls.publicClassConstantString("KIND_ARRAY_DIM_FETCH", "arrayDimFetch");
	cls.publicClassConstantString("KIND_PROPERTY_FETCH", "propertyFetch");
	cls.publicClassConstantString("KIND_STATIC_PROPERTY_FETCH", "staticPropertyFetch");
	cls.publicClassConstantString("KIND_LIST", "list");
	cls.publicClassConstantString("KIND_EXISTING_ARRAY_DIM_FETCH", "existingArrayDimFetch");
	cls.publicClassConstantString("KIND_FALLBACK", "fallback");
	ptdecl::PreparedAssignTarget::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval argv[PT_PAT_ARG_COUNT];
		for (uint32_t i = 0; i < PT_PAT_ARG_COUNT; i++) {
			ZVAL_UNDEF(&argv[i]);
		}
		zval *objects[PT_PAT_ARG_COUNT] = {};
		zend_string *kindStr = NULL, *propertyNameStr = NULL;
		bool enterExpressionAssignBool, isAssignOpBool, hasYieldBool, isAlwaysTerminatingBool;
		ZEND_PARSE_PARAMETERS_START(PT_PAT_REQUIRED_ARG_COUNT, PT_PAT_ARG_COUNT)
			Z_PARAM_STR(kindStr)
			Z_PARAM_OBJECT(objects[1])
			Z_PARAM_OBJECT(objects[2])
			Z_PARAM_OBJECT(objects[3])
			Z_PARAM_OBJECT(objects[4])
			Z_PARAM_BOOL(enterExpressionAssignBool)
			Z_PARAM_BOOL(isAssignOpBool)
			Z_PARAM_BOOL(hasYieldBool)
			Z_PARAM_ARRAY(objects[8])
			Z_PARAM_ARRAY(objects[9])
			Z_PARAM_BOOL(isAlwaysTerminatingBool)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(objects[11])
			Z_PARAM_OBJECT_OR_NULL(objects[12])
			Z_PARAM_ARRAY_OR_NULL(objects[13])
			Z_PARAM_OBJECT_OR_NULL(objects[14])
			Z_PARAM_ARRAY_OR_NULL(objects[15])
			Z_PARAM_ARRAY_OR_NULL(objects[16])
			Z_PARAM_ARRAY_OR_NULL(objects[17])
			Z_PARAM_ARRAY_OR_NULL(objects[18])
			Z_PARAM_OBJECT_OR_NULL(objects[19])
			Z_PARAM_OBJECT_OR_NULL(objects[20])
			Z_PARAM_STR_OR_NULL(propertyNameStr)
			Z_PARAM_OBJECT_OR_NULL(objects[22])
			Z_PARAM_OBJECT_OR_NULL(objects[23])
			Z_PARAM_ARRAY(objects[24])
			Z_PARAM_OBJECT_OR_NULL(objects[25])
		ZEND_PARSE_PARAMETERS_END();
		zval scalar;
		ZVAL_STR(&scalar, kindStr);
		ZVAL_COPY_VALUE(&argv[slots::kind], &scalar);
		ZVAL_BOOL(&argv[slots::enterExpressionAssign], enterExpressionAssignBool);
		ZVAL_BOOL(&argv[slots::isAssignOp], isAssignOpBool);
		ZVAL_BOOL(&argv[slots::hasYield], hasYieldBool);
		ZVAL_BOOL(&argv[slots::isAlwaysTerminating], isAlwaysTerminatingBool);
		if (ZEND_NUM_ARGS() > slots::propertyName) {
			if (propertyNameStr != NULL) {
				ZVAL_STR(&argv[slots::propertyName], propertyNameStr);
			} else {
				ZVAL_NULL(&argv[slots::propertyName]);
			}
		}
		for (uint32_t i = 0; i < PT_PAT_ARG_COUNT; i++) {
			if (objects[i] != NULL) {
				ZVAL_COPY_VALUE(&argv[i], objects[i]);
			} else if (Z_TYPE(argv[i]) == IS_UNDEF && i < ZEND_NUM_ARGS()) {
				ZVAL_NULL(&argv[i]);
			}
		}
		PreparedAssignTarget(Z_OBJ_P(ZEND_THIS)).construct(argv);
	});

	cls.method<&PreparedAssignTarget::getKind>(sigs::getKind);

	cls.method<&PreparedAssignTarget::getVar>(sigs::getVar);

	cls.method<&PreparedAssignTarget::getAssignedExpr>(sigs::getAssignedExpr);

	cls.method<&PreparedAssignTarget::getBeforeScope>(sigs::getBeforeScope);

	cls.method<&PreparedAssignTarget::getScope>(sigs::getScope);

	cls.method<&PreparedAssignTarget::enterExpressionAssign>(sigs::enterExpressionAssign);

	cls.method<&PreparedAssignTarget::isAssignOp>(sigs::isAssignOp);

	cls.method<&PreparedAssignTarget::hasYield>(sigs::hasYield);

	cls.method<&PreparedAssignTarget::getThrowPoints>(sigs::getThrowPoints);

	cls.method<&PreparedAssignTarget::getImpurePoints>(sigs::getImpurePoints);

	cls.method<&PreparedAssignTarget::isAlwaysTerminating>(sigs::isAlwaysTerminating);

	cls.method<&PreparedAssignTarget::getRootVar>(sigs::getRootVar);

	cls.method<&PreparedAssignTarget::getVarResult>(sigs::getVarResult);

	cls.method<&PreparedAssignTarget::getDimFetchStack>(sigs::getDimFetchStack);

	cls.method<&PreparedAssignTarget::getAssignedPropertyExpr>(sigs::getAssignedPropertyExpr);

	cls.method<&PreparedAssignTarget::getOffsetTypes>(sigs::getOffsetTypes);

	cls.method<&PreparedAssignTarget::getOffsetNativeTypes>(sigs::getOffsetNativeTypes);

	cls.method<&PreparedAssignTarget::getExistingOffsetTypes>(sigs::getExistingOffsetTypes);

	cls.method<&PreparedAssignTarget::getExistingOffsetNativeTypes>(sigs::getExistingOffsetNativeTypes);

	cls.method<&PreparedAssignTarget::getOffsetSetTargetResult>(sigs::getOffsetSetTargetResult);

	cls.method<&PreparedAssignTarget::getObjectResult>(sigs::getObjectResult);

	cls.method<&PreparedAssignTarget::getPropertyName>(sigs::getPropertyName);

	cls.method<&PreparedAssignTarget::getPropertyHolderType>(sigs::getPropertyHolderType);

	cls.method<&PreparedAssignTarget::getTargetReadResult>(sigs::getTargetReadResult);

	cls.method<&PreparedAssignTarget::getTargetChainResults>(sigs::getTargetChainResults);

	cls.method<&PreparedAssignTarget::getVariableNameResult>(sigs::getVariableNameResult);

	cls.shadow(&pt_ce_prepared_assign_target);
}

/* }}} */
