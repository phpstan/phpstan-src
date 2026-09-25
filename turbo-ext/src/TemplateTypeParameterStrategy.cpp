/*
 * PHPStanTurbo\TemplateTypeParameterStrategy — native implementation of
 * PHPStan\Type\Generic\TemplateTypeParameterStrategy.
 *
 * Declared as PHPStan\Type\Generic\TemplateTypeParameterStrategy itself at
 * activation: final, stateless, implementing the PHP TemplateTypeStrategy
 * interface. The strategy of a template type in a parameter-type
 * acceptance context: the bound decides.
 */

#include "TypeTraits.h"
#include "generated/TemplateTypeParameterStrategy.h"

namespace sigs = ptdecl::TemplateTypeParameterStrategy::sig;

zend_class_entry *pt_ce_template_type_parameter_strategy = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeParameterStrategy. The twin has
 * no state, so the bodies are static. */
class TemplateTypeParameterStrategy
{
public:
	/* new TemplateTypeParameterStrategy(); UNDEF = pending exception */
	static zv::Val create() { return pt_new_instance(pt_ce_template_type_parameter_strategy); }

	/* $right->isAcceptedBy($left, $strictTypes) for a compound $right,
	 * $left->getBound()->accepts($right, $strictTypes) otherwise; UNDEF =
	 * pending exception */
	static zv::Val accepts(zval *left, zval *right, bool strictTypes)
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(right, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		zval args[2];
		ZVAL_BOOL(&args[1], strictTypes);
		if (compound) {
			ZVAL_COPY_VALUE(&args[0], left);
			return pt_type_call(Z_OBJ_P(right), PT_LC("isacceptedby"), 2, args);
		}

		zv::Val bound = pt_type_call(Z_OBJ_P(left), PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(bound.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::getBound() must return %s", ZSTR_VAL(Z_OBJCE_P(left)->name), ptcls::type);
			return zv::Val();
		}
		ZVAL_COPY_VALUE(&args[0], right);
		return pt_type_op(Z_OBJ_P(bound.raw()), PT_OP_ACCEPTS, 2, args);
	}

	static bool isArgument() { return false; }
};

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeParameterStrategy;

bool pt_template_type_parameter_strategy_new(zval *out)
{
	return pt_val_into(TemplateTypeParameterStrategy::create(), out);
}

zv::Val pt_template_type_parameter_strategy_create()
{
	return TemplateTypeParameterStrategy::create();
}

zv::Val pt_template_type_parameter_strategy_accepts(zval *left, zval *right, bool strictTypes)
{
	return TemplateTypeParameterStrategy::accepts(left, right, strictTypes);
}

/* {{{ engine ABI glue: parameter parsing + registration */

PT_MINIT_REGISTRATION(pt_register_template_type_parameter_strategy)
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeParameterStrategy");
	ptdecl::TemplateTypeParameterStrategy::declareClass(cls);
	ptdecl::TemplateTypeParameterStrategy::declareProperties(cls);

	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *left, *right;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Bool>(execute_data, left, right, strictTypes)) RETURN_THROWS();
		/* the twin's `TemplateType $left`: an interface the class map
		 * resolves, checked as the engine checks the twin's parameter */
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(left, PT_CLASS_TEMPLATE_TYPE, isTemplate))) RETURN_THROWS();
		if (UNEXPECTED(!isTemplate)) {
			zend_argument_type_error(1, "must be of type %s, %s given", ptcls::templateType, ZSTR_VAL(Z_OBJCE_P(left)->name));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(TemplateTypeParameterStrategy::accepts(left, right, strictTypes));
	});

	cls.method(sigs::isArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(TemplateTypeParameterStrategy::isArgument());
	});

	cls.shadow(&pt_ce_template_type_parameter_strategy);
}

/* }}} */
