/*
 * PHPStanTurbo\ExpressionContext — native implementation of
 * PHPStan\Analyser\ExpressionContext.
 *
 * A final value class with a private constructor: instances come only from
 * the static factories and the enter*() / with*() derivations. The state
 * lives in the twin's fourteen promoted property slots (generated
 * declarations), so the standard object handlers do GC/free/clone —
 * enterPassedToType()'s `clone $this` is the engine's clone handler.
 *
 * Native callers (the engine ports: handlers, NodeScopeResolver) use the
 * pt_expression_context_* direct entries (support.h): the slots of a native
 * context, the methods of anything else (the PHP twin under the prefixed
 * differential activation).
 */

#include "support.h"
#include "generated/ExpressionContext.h"

namespace slots = ptdecl::ExpressionContext::slot;
namespace sigs = ptdecl::ExpressionContext::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_expression_context = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExpressionContext. The handle wraps one object;
 * methods returning zv::Val use UNDEF for a pending exception. */
class ExpressionContext
{
public:
	explicit ExpressionContext(zend_object *self) : self(self) {}

	/* the private constructor's parameters with the twin's defaults: NULL
	 * stands for a null object/string argument (borrowed otherwise), -1 for
	 * the null ?bool $valueConsumed */
	struct Fields
	{
		bool isDeep;
		zval *inAssignRightSideVariableName;
		zval *inAssignRightSideExpr;
		bool inThrow = false;
		zval *inAssignRightSideType = NULL;
		zval *inAssignRightSideNativeType = NULL;
		bool resolveTemplateArguments = true;
		zval *valueFlowTarget = NULL;
		bool valueFlowDirect = false;
		bool arrayDimFetchRoot = false;
		bool unsetTarget = false;
		int valueConsumed = -1;
		zval *passedToType = NULL;
		zval *nativePassedToType = NULL;
	};

	/* the constructor body: the promoted properties */
	void construct(const Fields &f)
	{
		writeBool(slots::isDeep, f.isDeep);
		write(slots::inAssignRightSideVariableName, f.inAssignRightSideVariableName);
		write(slots::inAssignRightSideExpr, f.inAssignRightSideExpr);
		writeBool(slots::inThrow, f.inThrow);
		write(slots::inAssignRightSideType, f.inAssignRightSideType);
		write(slots::inAssignRightSideNativeType, f.inAssignRightSideNativeType);
		writeBool(slots::resolveTemplateArguments, f.resolveTemplateArguments);
		write(slots::valueFlowTarget, f.valueFlowTarget);
		writeBool(slots::valueFlowDirect, f.valueFlowDirect);
		writeBool(slots::arrayDimFetchRoot, f.arrayDimFetchRoot);
		writeBool(slots::unsetTarget, f.unsetTarget);
		zval *valueConsumed = slot(slots::valueConsumed);
		zval old;
		ZVAL_COPY_VALUE(&old, valueConsumed);
		if (f.valueConsumed < 0) {
			ZVAL_NULL(valueConsumed);
		} else {
			ZVAL_BOOL(valueConsumed, f.valueConsumed != 0);
		}
		zval_ptr_dtor(&old);
		write(slots::passedToType, f.passedToType);
		write(slots::nativePassedToType, f.nativePassedToType);
	}

	/* Mirrors createTopLevel(). */
	static zv::Val createTopLevel(bool resolveTemplateArguments)
	{
		Fields f{};
		f.isDeep = false;
		f.inAssignRightSideVariableName = NULL;
		f.inAssignRightSideExpr = NULL;
		f.resolveTemplateArguments = resolveTemplateArguments;
		return newSelf(f);
	}

	/* Mirrors createDeep(). */
	static zv::Val createDeep(bool resolveTemplateArguments)
	{
		Fields f{};
		f.isDeep = true;
		f.inAssignRightSideVariableName = NULL;
		f.inAssignRightSideExpr = NULL;
		f.resolveTemplateArguments = resolveTemplateArguments;
		return newSelf(f);
	}

	/* Mirrors enterDeep(). */
	zv::Val enterDeep() const
	{
		if (boolSlot(slots::isDeep) && isNull(slots::valueFlowTarget) && !boolSlot(slots::arrayDimFetchRoot) && !boolSlot(slots::unsetTarget) && isNull(slots::passedToType) && isNull(slots::nativePassedToType)) {
			return thisValue();
		}

		Fields f = carried();
		f.isDeep = true;
		return newSelf(f);
	}

	/* Mirrors enterDeepKeepingValueFlow(). */
	zv::Val enterDeepKeepingValueFlow() const
	{
		if (isNull(slots::valueFlowTarget)) return enterDeep();

		Fields f = carried();
		f.isDeep = true;
		f.valueFlowTarget = slot(slots::valueFlowTarget);
		f.valueFlowDirect = false;
		return newSelf(f);
	}

	/* Mirrors withoutValueFlow(). */
	zv::Val withoutValueFlow() const
	{
		if (isNull(slots::valueFlowTarget) && !boolSlot(slots::arrayDimFetchRoot) && !boolSlot(slots::unsetTarget) && isNull(slots::passedToType) && isNull(slots::nativePassedToType)) {
			return thisValue();
		}

		return newSelf(carried());
	}

	/* Mirrors enterMatchArm(). */
	zv::Val enterMatchArm() const
	{
		Fields f{};
		f.isDeep = false;
		f.inAssignRightSideVariableName = NULL;
		f.inAssignRightSideExpr = NULL;
		f.resolveTemplateArguments = boolSlot(slots::resolveTemplateArguments);
		f.valueFlowTarget = slot(slots::valueFlowTarget);
		f.valueConsumed = isValueConsumed() ? 1 : 0;
		return newSelf(f);
	}

	/* Mirrors isValueConsumed(). */
	bool isValueConsumed() const
	{
		if (!isNull(slots::valueFlowTarget)) return true;
		zval *valueConsumed = slot(slots::valueConsumed);
		if (Z_TYPE_P(valueConsumed) != IS_NULL) return Z_TYPE_P(valueConsumed) == IS_TRUE;
		return boolSlot(slots::isDeep);
	}

	/* Mirrors enterPassedToType(); $type / $nativeType NULL for null. */
	zv::Val enterPassedToType(zval *type, zval *nativeType) const
	{
		if (sameObjectOrNull(slot(slots::passedToType), type) && sameObjectOrNull(slot(slots::nativePassedToType), nativeType)) {
			return thisValue();
		}

		zend_object *clone = self->handlers->clone_obj(self);
		zval cloneValue;
		ZVAL_OBJ(&cloneValue, clone);
		zv::Val context = zv::Val::adopt(cloneValue);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		ExpressionContext cloned(clone);
		cloned.write(slots::passedToType, type);
		cloned.write(slots::nativePassedToType, nativeType);

		return context;
	}

	zv::Val getPassedToType() const { return copySlot(slots::passedToType); }
	zv::Val getNativePassedToType() const { return copySlot(slots::nativePassedToType); }
	bool isDeep() const { return boolSlot(slots::isDeep); }
	bool shouldResolveTemplateArguments() const { return boolSlot(slots::resolveTemplateArguments); }

	/* Mirrors withoutTemplateArgumentResolution(): the passed-to types are
	 * not carried, as in the twin */
	zv::Val withoutTemplateArgumentResolution() const
	{
		if (!boolSlot(slots::resolveTemplateArguments)) return thisValue();

		Fields f = carried();
		f.resolveTemplateArguments = false;
		f.valueFlowTarget = slot(slots::valueFlowTarget);
		f.valueFlowDirect = boolSlot(slots::valueFlowDirect);
		f.arrayDimFetchRoot = boolSlot(slots::arrayDimFetchRoot);
		f.unsetTarget = boolSlot(slots::unsetTarget);
		zval *valueConsumed = slot(slots::valueConsumed);
		f.valueConsumed = Z_TYPE_P(valueConsumed) == IS_NULL ? -1 : (Z_TYPE_P(valueConsumed) == IS_TRUE ? 1 : 0);
		return newSelf(f);
	}

	/* Mirrors enterThrow(). */
	zv::Val enterThrow() const
	{
		Fields f = carried();
		f.inThrow = true;
		return newSelf(f);
	}

	bool isInThrow() const { return boolSlot(slots::inThrow); }

	/* Mirrors enterRightSideAssign(). */
	zv::Val enterRightSideAssign(zval *variableName, zval *expr) const
	{
		Fields f{};
		f.isDeep = boolSlot(slots::isDeep);
		f.inAssignRightSideVariableName = variableName;
		f.inAssignRightSideExpr = expr;
		f.inThrow = boolSlot(slots::inThrow);
		f.resolveTemplateArguments = boolSlot(slots::resolveTemplateArguments);
		return newSelf(f);
	}

	zv::Val getInAssignRightSideVariableName() const { return copySlot(slots::inAssignRightSideVariableName); }
	zv::Val getInAssignRightSideExpr() const { return copySlot(slots::inAssignRightSideExpr); }

	/* Mirrors enterAssignRightSideCallArgs(): getReturnType() is asked twice
	 * for an acceptor that is not an ExtendedParametersAcceptor, as in the
	 * twin */
	zv::Val enterAssignRightSideCallArgs(zval *acceptor) const
	{
		zv::Val returnType = pt_type_call(Z_OBJ_P(acceptor), PT_LC("getreturntype"), 0, NULL);
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zv::Val inAssignRightSideType = pt_type_template_type_helper_resolve_to_bounds(returnType.raw());
		if (UNEXPECTED(inAssignRightSideType.isUndef())) return zv::Val();
		zend_class_entry *extendedCe = pt_class(PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR);
		if (UNEXPECTED(extendedCe == NULL)) return zv::Val();
		zv::Val nativeReturnType = instanceof_function(Z_OBJCE_P(acceptor), extendedCe)
			? pt_type_call(Z_OBJ_P(acceptor), PT_LC("getnativereturntype"), 0, NULL)
			: pt_type_call(Z_OBJ_P(acceptor), PT_LC("getreturntype"), 0, NULL);
		if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
		zv::Val inAssignRightSideNativeType = pt_type_template_type_helper_resolve_to_bounds(nativeReturnType.raw());
		if (UNEXPECTED(inAssignRightSideNativeType.isUndef())) return zv::Val();

		Fields f = carried();
		f.inAssignRightSideType = inAssignRightSideType.raw();
		f.inAssignRightSideNativeType = inAssignRightSideNativeType.raw();
		return newSelf(f);
	}

	zv::Val getInAssignRightSideType() const { return copySlot(slots::inAssignRightSideType); }
	zv::Val getInAssignRightSideNativeType() const { return copySlot(slots::inAssignRightSideNativeType); }

	/* Mirrors enterValueFlow(). */
	zv::Val enterValueFlow(zval *target, bool direct) const
	{
		Fields f = carried();
		f.valueFlowTarget = target;
		f.valueFlowDirect = direct;
		return newSelf(f);
	}

	zv::Val getValueFlowTarget() const { return copySlot(slots::valueFlowTarget); }
	bool isValueFlowDirect() const { return boolSlot(slots::valueFlowDirect); }

	/* Mirrors enterArrayDimFetchRoot(). */
	zv::Val enterArrayDimFetchRoot() const
	{
		Fields f = carried();
		f.valueFlowTarget = slot(slots::valueFlowTarget);
		f.valueFlowDirect = false;
		f.arrayDimFetchRoot = true;
		return newSelf(f);
	}

	bool isArrayDimFetchRoot() const { return boolSlot(slots::arrayDimFetchRoot); }

	/* Mirrors enterUnsetTarget(). */
	zv::Val enterUnsetTarget() const
	{
		Fields f = carried();
		f.unsetTarget = true;
		return newSelf(f);
	}

	bool isUnsetTarget() const { return boolSlot(slots::unsetTarget); }

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }
	bool boolSlot(uint32_t index) const { return Z_TYPE_P(slot(index)) == IS_TRUE; }
	bool isNull(uint32_t index) const { return Z_TYPE_P(slot(index)) == IS_NULL; }
	zv::Val copySlot(uint32_t index) const { return zv::Val::copyOf(zv::Ref(slot(index))); }

	zv::Val thisValue() const
	{
		zval value;
		ZVAL_OBJ_COPY(&value, self);
		return zv::Val::adopt(value);
	}

	/* $this->x = $value for a nullable slot ($value NULL or IS_NULL for null) */
	void write(uint32_t index, zval *value)
	{
		zval *p = slot(index);
		zval old;
		ZVAL_COPY_VALUE(&old, p);
		if (value == NULL || Z_TYPE_P(value) == IS_NULL) {
			ZVAL_NULL(p);
		} else {
			ZVAL_COPY(p, value);
		}
		zval_ptr_dtor(&old);
	}

	void writeBool(uint32_t index, bool value)
	{
		zval *p = slot(index);
		zval old;
		ZVAL_COPY_VALUE(&old, p);
		ZVAL_BOOL(p, value);
		zval_ptr_dtor(&old);
	}

	/* `$a === $b` for two ?object values (NULL = null) */
	static bool sameObjectOrNull(zval *a, zval *b)
	{
		bool aNull = a == NULL || Z_TYPE_P(a) == IS_NULL;
		bool bNull = b == NULL || Z_TYPE_P(b) == IS_NULL;
		if (aNull || bNull) return aNull && bNull;
		return Z_TYPE_P(a) == IS_OBJECT && Z_TYPE_P(b) == IS_OBJECT && Z_OBJ_P(a) == Z_OBJ_P(b);
	}

	/* the leading positional arguments most derivations pass: `new
	 * self($this->isDeep, $this->inAssignRightSideVariableName,
	 * $this->inAssignRightSideExpr, $this->inThrow,
	 * $this->inAssignRightSideType, $this->inAssignRightSideNativeType,
	 * $this->resolveTemplateArguments)` — the rest at their defaults */
	Fields carried() const
	{
		Fields f{};
		f.isDeep = boolSlot(slots::isDeep);
		f.inAssignRightSideVariableName = slot(slots::inAssignRightSideVariableName);
		f.inAssignRightSideExpr = slot(slots::inAssignRightSideExpr);
		f.inThrow = boolSlot(slots::inThrow);
		f.inAssignRightSideType = slot(slots::inAssignRightSideType);
		f.inAssignRightSideNativeType = slot(slots::inAssignRightSideNativeType);
		f.resolveTemplateArguments = boolSlot(slots::resolveTemplateArguments);
		return f;
	}

	/* new self(...) — the class is final */
	static zv::Val newSelf(const Fields &f)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_expression_context) != SUCCESS)) return zv::Val();
		ExpressionContext(Z_OBJ(object)).construct(f);
		return zv::Val::adopt(object);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExpressionContext;

/* {{{ direct entries for native callers */

namespace {

/* the twin is final: an instance of the native class entry is read
 * natively, anything else (the PHP twin under the prefixed differential
 * activation) through its method */
inline bool isNative(zval *context)
{
	return EXPECTED(Z_OBJCE_P(context) == pt_ce_expression_context);
}

[[nodiscard]] bool boolMethod(zval *context, const char *lcname, size_t len, bool &out)
{
	zv::Val result = pt_type_call(Z_OBJ_P(context), lcname, len, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

} // namespace

zv::Val pt_expression_context_create_top_level(bool resolveTemplateArguments)
{
	return ExpressionContext::createTopLevel(resolveTemplateArguments);
}

zv::Val pt_expression_context_create_deep(bool resolveTemplateArguments)
{
	return ExpressionContext::createDeep(resolveTemplateArguments);
}

zv::Val pt_expression_context_enter_deep(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterDeep();
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterdeep"), 0, NULL);
}

zv::Val pt_expression_context_enter_deep_keeping_value_flow(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterDeepKeepingValueFlow();
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterdeepkeepingvalueflow"), 0, NULL);
}

zv::Val pt_expression_context_without_value_flow(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).withoutValueFlow();
	return pt_type_call(Z_OBJ_P(context), PT_LC("withoutvalueflow"), 0, NULL);
}

zv::Val pt_expression_context_enter_match_arm(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterMatchArm();
	return pt_type_call(Z_OBJ_P(context), PT_LC("entermatcharm"), 0, NULL);
}

zv::Val pt_expression_context_without_template_argument_resolution(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).withoutTemplateArgumentResolution();
	return pt_type_call(Z_OBJ_P(context), PT_LC("withouttemplateargumentresolution"), 0, NULL);
}

zv::Val pt_expression_context_enter_throw(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterThrow();
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterthrow"), 0, NULL);
}

zv::Val pt_expression_context_enter_array_dim_fetch_root(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterArrayDimFetchRoot();
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterarraydimfetchroot"), 0, NULL);
}

zv::Val pt_expression_context_enter_unset_target(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterUnsetTarget();
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterunsettarget"), 0, NULL);
}

zv::Val pt_expression_context_enter_passed_to_type(zval *context, zval *type, zval *nativeType)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterPassedToType(type, nativeType);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{type != NULL ? type : &null, nativeType != NULL ? nativeType : &null};
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterpassedtotype"), 2, argv);
}

zv::Val pt_expression_context_enter_right_side_assign(zval *context, zend_string *variableName, zval *expr)
{
	zval name;
	ZVAL_STR(&name, variableName);
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterRightSideAssign(&name, expr);
	zv::Args argv{&name, expr};
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterrightsideassign"), 2, argv);
}

zv::Val pt_expression_context_enter_assign_right_side_call_args(zval *context, zval *acceptor)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterAssignRightSideCallArgs(acceptor);
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterassignrightsidecallargs"), 1, acceptor);
}

zv::Val pt_expression_context_enter_value_flow(zval *context, zval *target, bool direct)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).enterValueFlow(target, direct);
	zv::Args argv{target, direct};
	return pt_type_call(Z_OBJ_P(context), PT_LC("entervalueflow"), 2, argv);
}

bool pt_expression_context_is_deep(zval *context, bool &out)
{
	if (isNative(context)) {
		out = ExpressionContext(Z_OBJ_P(context)).isDeep();
		return true;
	}
	return boolMethod(context, PT_LC("isdeep"), out);
}

bool pt_expression_context_is_value_consumed(zval *context, bool &out)
{
	if (isNative(context)) {
		out = ExpressionContext(Z_OBJ_P(context)).isValueConsumed();
		return true;
	}
	return boolMethod(context, PT_LC("isvalueconsumed"), out);
}

bool pt_expression_context_should_resolve_template_arguments(zval *context, bool &out)
{
	if (isNative(context)) {
		out = ExpressionContext(Z_OBJ_P(context)).shouldResolveTemplateArguments();
		return true;
	}
	return boolMethod(context, PT_LC("shouldresolvetemplatearguments"), out);
}

bool pt_expression_context_is_in_throw(zval *context, bool &out)
{
	if (isNative(context)) {
		out = ExpressionContext(Z_OBJ_P(context)).isInThrow();
		return true;
	}
	return boolMethod(context, PT_LC("isinthrow"), out);
}

bool pt_expression_context_is_value_flow_direct(zval *context, bool &out)
{
	if (isNative(context)) {
		out = ExpressionContext(Z_OBJ_P(context)).isValueFlowDirect();
		return true;
	}
	return boolMethod(context, PT_LC("isvalueflowdirect"), out);
}

bool pt_expression_context_is_array_dim_fetch_root(zval *context, bool &out)
{
	if (isNative(context)) {
		out = ExpressionContext(Z_OBJ_P(context)).isArrayDimFetchRoot();
		return true;
	}
	return boolMethod(context, PT_LC("isarraydimfetchroot"), out);
}

bool pt_expression_context_is_unset_target(zval *context, bool &out)
{
	if (isNative(context)) {
		out = ExpressionContext(Z_OBJ_P(context)).isUnsetTarget();
		return true;
	}
	return boolMethod(context, PT_LC("isunsettarget"), out);
}

zv::Val pt_expression_context_get_passed_to_type(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).getPassedToType();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getpassedtotype"), 0, NULL);
}

zv::Val pt_expression_context_get_native_passed_to_type(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).getNativePassedToType();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getnativepassedtotype"), 0, NULL);
}

zv::Val pt_expression_context_get_in_assign_right_side_variable_name(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).getInAssignRightSideVariableName();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getinassignrightsidevariablename"), 0, NULL);
}

zv::Val pt_expression_context_get_in_assign_right_side_expr(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).getInAssignRightSideExpr();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getinassignrightsideexpr"), 0, NULL);
}

zv::Val pt_expression_context_get_in_assign_right_side_type(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).getInAssignRightSideType();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getinassignrightsidetype"), 0, NULL);
}

zv::Val pt_expression_context_get_in_assign_right_side_native_type(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).getInAssignRightSideNativeType();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getinassignrightsidenativetype"), 0, NULL);
}

zv::Val pt_expression_context_get_value_flow_target(zval *context)
{
	if (isNative(context)) return ExpressionContext(Z_OBJ_P(context)).getValueFlowTarget();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getvalueflowtarget"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_expression_context()
{
	reg::Class cls("PHPStan\\Analyser\\ExpressionContext");
	ptdecl::ExpressionContext::declareClass(cls);
	ptdecl::ExpressionContext::declareProperties(cls);

	/* private like the twin's: `new ExpressionContext(...)` from userland
	 * fails the same way; the native derivations fill the slots without it */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ExpressionContext::Fields f{};
		zend_string *variableName = NULL;
		zval *expr;
		bool valueConsumedIsNull = true;
		bool valueConsumed = false;
		ZEND_PARSE_PARAMETERS_START(3, 14)
			Z_PARAM_BOOL(f.isDeep)
			Z_PARAM_STR_OR_NULL(variableName)
			Z_PARAM_OBJECT_OR_NULL(expr)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(f.inThrow)
			Z_PARAM_OBJECT_OR_NULL(f.inAssignRightSideType)
			Z_PARAM_OBJECT_OR_NULL(f.inAssignRightSideNativeType)
			Z_PARAM_BOOL(f.resolveTemplateArguments)
			Z_PARAM_OBJECT_OR_NULL(f.valueFlowTarget)
			Z_PARAM_BOOL(f.valueFlowDirect)
			Z_PARAM_BOOL(f.arrayDimFetchRoot)
			Z_PARAM_BOOL(f.unsetTarget)
			Z_PARAM_BOOL_OR_NULL(valueConsumed, valueConsumedIsNull)
			Z_PARAM_OBJECT_OR_NULL(f.passedToType)
			Z_PARAM_OBJECT_OR_NULL(f.nativePassedToType)
		ZEND_PARSE_PARAMETERS_END();
		zval name;
		if (variableName != NULL) {
			ZVAL_STR(&name, variableName);
		} else {
			ZVAL_NULL(&name);
		}
		f.inAssignRightSideVariableName = &name;
		f.inAssignRightSideExpr = expr;
		f.valueConsumed = valueConsumedIsNull ? -1 : (valueConsumed ? 1 : 0);
		ExpressionContext(Z_OBJ_P(ZEND_THIS)).construct(f);
	});

	cls.method(sigs::createTopLevel, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool resolveTemplateArguments = true;
		if (!zp::parse<zp::Opt<zp::Bool>>(execute_data, resolveTemplateArguments)) RETURN_THROWS();
		PT_RETURN_VAL(ExpressionContext::createTopLevel(resolveTemplateArguments));
	});

	cls.method(sigs::createDeep, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool resolveTemplateArguments = true;
		if (!zp::parse<zp::Opt<zp::Bool>>(execute_data, resolveTemplateArguments)) RETURN_THROWS();
		PT_RETURN_VAL(ExpressionContext::createDeep(resolveTemplateArguments));
	});

	cls.method<&ExpressionContext::enterDeep>(sigs::enterDeep);
	cls.method<&ExpressionContext::enterDeepKeepingValueFlow>(sigs::enterDeepKeepingValueFlow);
	cls.method<&ExpressionContext::withoutValueFlow>(sigs::withoutValueFlow);
	cls.method<&ExpressionContext::enterMatchArm>(sigs::enterMatchArm);

	cls.method(sigs::isValueConsumed, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).isValueConsumed());
	});

	cls.method(sigs::enterPassedToType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *nativeType;
		if (!zp::parse<zp::ObjOrNull, zp::ObjOrNull>(execute_data, type, nativeType)) RETURN_THROWS();
		PT_RETURN_VAL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).enterPassedToType(type, nativeType));
	});

	cls.method<&ExpressionContext::getPassedToType>(sigs::getPassedToType);
	cls.method<&ExpressionContext::getNativePassedToType>(sigs::getNativePassedToType);

	cls.method(sigs::isDeep, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).isDeep());
	});

	cls.method(sigs::shouldResolveTemplateArguments, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).shouldResolveTemplateArguments());
	});

	cls.method<&ExpressionContext::withoutTemplateArgumentResolution>(sigs::withoutTemplateArgumentResolution);
	cls.method<&ExpressionContext::enterThrow>(sigs::enterThrow);

	cls.method(sigs::isInThrow, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).isInThrow());
	});

	cls.method(sigs::enterRightSideAssign, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *variableName;
		zval *expr;
		if (!zp::parse<zp::Str, zp::Obj>(execute_data, variableName, expr)) RETURN_THROWS();
		zval name;
		ZVAL_STR(&name, variableName);
		PT_RETURN_VAL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).enterRightSideAssign(&name, expr));
	});

	cls.method<&ExpressionContext::getInAssignRightSideVariableName>(sigs::getInAssignRightSideVariableName);
	cls.method<&ExpressionContext::getInAssignRightSideExpr>(sigs::getInAssignRightSideExpr);
	cls.method<&ExpressionContext::enterAssignRightSideCallArgs, zp::Obj>(sigs::enterAssignRightSideCallArgs);
	cls.method<&ExpressionContext::getInAssignRightSideType>(sigs::getInAssignRightSideType);
	cls.method<&ExpressionContext::getInAssignRightSideNativeType>(sigs::getInAssignRightSideNativeType);
	cls.method<&ExpressionContext::enterValueFlow, zp::Obj, zp::Bool>(sigs::enterValueFlow);
	cls.method<&ExpressionContext::getValueFlowTarget>(sigs::getValueFlowTarget);

	cls.method(sigs::isValueFlowDirect, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).isValueFlowDirect());
	});

	cls.method<&ExpressionContext::enterArrayDimFetchRoot>(sigs::enterArrayDimFetchRoot);

	cls.method(sigs::isArrayDimFetchRoot, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).isArrayDimFetchRoot());
	});

	cls.method<&ExpressionContext::enterUnsetTarget>(sigs::enterUnsetTarget);

	cls.method(sigs::isUnsetTarget, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionContext(Z_OBJ_P(ZEND_THIS)).isUnsetTarget());
	});

	cls.shadow(&pt_ce_expression_context);
}

/* }}} */
