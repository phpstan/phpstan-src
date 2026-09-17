/*
 * PHPStanTurbo\TrivialParametersAcceptor — native implementation of
 * PHPStan\Reflection\TrivialParametersAcceptor.
 *
 * The acceptor of a callable nothing is known about: a final class over the
 * callable's name, answering every query with the twin's constants — a new
 * MixedType per return-type query and a new SimpleImpurePoint per
 * getImpurePoints(), as the twin allocates them. The Type kernel creates
 * instances with pt_trivial_parameters_acceptor_new(); native callers query
 * them through pt_parameters_acceptor_call() (AcceptorValues.h).
 */

#include "support.h"
#include "generated/TrivialParametersAcceptor.h"

namespace slots = ptdecl::TrivialParametersAcceptor::slot;
namespace sigs = ptdecl::TrivialParametersAcceptor::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AcceptorValues.h"

#include "zend_smart_str.h"

zend_class_entry *pt_ce_trivial_parameters_acceptor = NULL;

namespace {

/* the interned 'functionCall' / 'callable' literals (module startup) */
zend_string *pt_tpa_function_call = NULL;
zend_string *pt_tpa_callable = NULL;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\TrivialParametersAcceptor; UNDEF = pending
 * exception. */
class TrivialParametersAcceptor
{
public:
	explicit TrivialParametersAcceptor(zend_object *self) : self(self) {}

	void construct(zend_string *callableName) const
	{
		zval value;
		ZVAL_STR(&value, callableName);
		pt_write_slot(self, slots::callableName, &value);
	}

	static zv::Val create(zend_string *callableName)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_trivial_parameters_acceptor) != SUCCESS)) return zv::Val();
		TrivialParametersAcceptor(Z_OBJ(object)).construct(callableName != NULL ? callableName : pt_tpa_callable);
		return zv::Val::adopt(object);
	}

	/* TemplateTypeMap::createEmpty() */
	zv::Val getTemplateTypeMap() const
	{
		zval map;
		if (UNEXPECTED(!pt_template_type_map_empty(&map))) return zv::Val();
		return zv::Val::adopt(map);
	}

	zv::Val getResolvedTemplateTypeMap() const { return getTemplateTypeMap(); }

	/* TemplateTypeVarianceMap::createEmpty() */
	zv::Val getCallSiteVarianceMap() const
	{
		zval map;
		if (UNEXPECTED(!pt_template_type_variance_map_empty(&map))) return zv::Val();
		return zv::Val::adopt(map);
	}

	zv::Val getParameters() const { return emptyArray(); }
	zv::Val isVariadic() const { return zv::Val::boolean(true); }

	/* new MixedType() */
	zv::Val getReturnType() const { return pt_type_new_mixed_type(); }
	zv::Val getPhpDocReturnType() const { return pt_type_new_mixed_type(); }
	zv::Val getNativeReturnType() const { return pt_type_new_mixed_type(); }

	zv::Val getThrowPoints() const { return emptyArray(); }
	zv::Val isPure() const { return trinary(PT_TRI_MAYBE); }

	/* [new SimpleImpurePoint('functionCall', sprintf('call to a %s',
	 * $this->callableName), false)] */
	zv::Val getImpurePoints() const
	{
		zval *callableName = pt_typed_slot(self, slots::callableName, pt_ce_trivial_parameters_acceptor, "callableName");
		if (UNEXPECTED(callableName == NULL)) return zv::Val();
		smart_str description = {NULL, 0};
		smart_str_appends(&description, "call to a ");
		smart_str_append(&description, Z_STR_P(callableName));
		zv::Str descriptionString = zv::Str::adopt(smart_str_extract(&description));
		zv::Val point = pt_simple_impure_point_new(pt_tpa_function_call, descriptionString.get(), false);
		if (UNEXPECTED(point.isUndef())) return zv::Val();
		zv::Arr points = zv::Arr::create(1);
		points.push(std::move(point));
		return zv::Val(std::move(points));
	}

	zv::Val getInvalidateExpressions() const { return emptyArray(); }
	zv::Val getUsedVariables() const { return emptyArray(); }
	zv::Val acceptsNamedArguments() const { return trinary(PT_TRI_YES); }
	zv::Val mustUseReturnValue() const { return trinary(PT_TRI_MAYBE); }

	/* Assertions::createEmpty() */
	zv::Val getAsserts() const
	{
		return pt_assertions_create_empty();
	}

	zv::Val isStaticClosure() const { return trinary(PT_TRI_MAYBE); }

private:
	zend_object *self;

	static zv::Val emptyArray()
	{
		zval array;
		ZVAL_EMPTY_ARRAY(&array);
		return zv::Val::adopt(array);
	}

	static zv::Val trinary(zend_long value)
	{
		return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value)));
	}
};

} // namespace phpstanturbo

using phpstanturbo::TrivialParametersAcceptor;

/* {{{ direct entries (support.h) */

zv::Val pt_trivial_parameters_acceptor_new(zend_string *callableName)
{
	return TrivialParametersAcceptor::create(callableName);
}

zv::Val pt_trivial_parameters_acceptor_call(zend_object *acceptor, pt_parameters_acceptor_member member)
{
	TrivialParametersAcceptor trivial(acceptor);
	switch (member) {
		case PT_PA_GET_TEMPLATE_TYPE_MAP: return trivial.getTemplateTypeMap();
		case PT_PA_GET_RESOLVED_TEMPLATE_TYPE_MAP: return trivial.getResolvedTemplateTypeMap();
		case PT_PA_GET_PARAMETERS: return trivial.getParameters();
		case PT_PA_IS_VARIADIC: return trivial.isVariadic();
		case PT_PA_GET_RETURN_TYPE: return trivial.getReturnType();
		case PT_PA_GET_PHPDOC_RETURN_TYPE: return trivial.getPhpDocReturnType();
		case PT_PA_GET_NATIVE_RETURN_TYPE: return trivial.getNativeReturnType();
		case PT_PA_GET_CALL_SITE_VARIANCE_MAP: return trivial.getCallSiteVarianceMap();
		case PT_PA_GET_THROW_POINTS: return trivial.getThrowPoints();
		case PT_PA_IS_PURE: return trivial.isPure();
		case PT_PA_GET_IMPURE_POINTS: return trivial.getImpurePoints();
		case PT_PA_GET_INVALIDATE_EXPRESSIONS: return trivial.getInvalidateExpressions();
		case PT_PA_GET_USED_VARIABLES: return trivial.getUsedVariables();
		case PT_PA_ACCEPTS_NAMED_ARGUMENTS: return trivial.acceptsNamedArguments();
		case PT_PA_MUST_USE_RETURN_VALUE: return trivial.mustUseReturnValue();
		case PT_PA_GET_ASSERTS: return trivial.getAsserts();
		case PT_PA_IS_STATIC_CLOSURE: return trivial.isStaticClosure();
		case PT_PA_GET_ORIGINAL_PARAMETERS_ACCEPTOR:
		case PT_PA_GET_RETURN_TYPE_WITH_UNRESOLVABLE_TEMPLATE_TYPES:
			return pt_parameters_acceptor_call_method(acceptor, member);
		case PT_PA_MEMBER_COUNT: break;
	}
	ZEND_UNREACHABLE();
	return zv::Val();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TPA_THIS TrivialParametersAcceptor(Z_OBJ_P(ZEND_THIS))

void pt_register_trivial_parameters_acceptor()
{
	pt_tpa_function_call = zend_string_init_interned(ZEND_STRL("functionCall"), 1);
	pt_tpa_callable = zend_string_init_interned(ZEND_STRL("callable"), 1);

	reg::Class cls("PHPStan\\Reflection\\TrivialParametersAcceptor");
	ptdecl::TrivialParametersAcceptor::declareClass(cls);
	ptdecl::TrivialParametersAcceptor::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *callableName = pt_tpa_callable;
		ZEND_PARSE_PARAMETERS_START(0, 1)
			Z_PARAM_OPTIONAL
			Z_PARAM_STR(callableName)
		ZEND_PARSE_PARAMETERS_END();
		PT_TPA_THIS.construct(callableName);
	});

	cls.method<&TrivialParametersAcceptor::getTemplateTypeMap>(sigs::getTemplateTypeMap);
	cls.method<&TrivialParametersAcceptor::getResolvedTemplateTypeMap>(sigs::getResolvedTemplateTypeMap);
	cls.method<&TrivialParametersAcceptor::getCallSiteVarianceMap>(sigs::getCallSiteVarianceMap);
	cls.method<&TrivialParametersAcceptor::getParameters>(sigs::getParameters);
	cls.method<&TrivialParametersAcceptor::isVariadic>(sigs::isVariadic);
	cls.method<&TrivialParametersAcceptor::getReturnType>(sigs::getReturnType);
	cls.method<&TrivialParametersAcceptor::getPhpDocReturnType>(sigs::getPhpDocReturnType);
	cls.method<&TrivialParametersAcceptor::getNativeReturnType>(sigs::getNativeReturnType);
	cls.method<&TrivialParametersAcceptor::getThrowPoints>(sigs::getThrowPoints);
	cls.method<&TrivialParametersAcceptor::isPure>(sigs::isPure);
	cls.method<&TrivialParametersAcceptor::getImpurePoints>(sigs::getImpurePoints);
	cls.method<&TrivialParametersAcceptor::getInvalidateExpressions>(sigs::getInvalidateExpressions);
	cls.method<&TrivialParametersAcceptor::getUsedVariables>(sigs::getUsedVariables);
	cls.method<&TrivialParametersAcceptor::acceptsNamedArguments>(sigs::acceptsNamedArguments);
	cls.method<&TrivialParametersAcceptor::mustUseReturnValue>(sigs::mustUseReturnValue);
	cls.method<&TrivialParametersAcceptor::getAsserts>(sigs::getAsserts);
	cls.method<&TrivialParametersAcceptor::isStaticClosure>(sigs::isStaticClosure);

	cls.shadow(&pt_ce_trivial_parameters_acceptor);
}

/* }}} */
