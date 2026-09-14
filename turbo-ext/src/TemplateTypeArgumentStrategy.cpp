/*
 * PHPStanTurbo\TemplateTypeArgumentStrategy — native implementation of
 * PHPStan\Type\Generic\TemplateTypeArgumentStrategy.
 *
 * Declared as PHPStan\Type\Generic\TemplateTypeArgumentStrategy itself at
 * activation: final, stateless, implementing the PHP TemplateTypeStrategy
 * interface. The strategy of a template type in a return-type acceptance
 * context: a bound that only maybe accepts the argument gets the reason
 * about the broken contract appended.
 */

#include "TypeTraits.h"
#include "generated/TemplateTypeArgumentStrategy.h"

namespace sigs = ptdecl::TemplateTypeArgumentStrategy::sig;

zend_class_entry *pt_ce_template_type_argument_strategy = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeArgumentStrategy. The twin has
 * no state, so the bodies are static. */
class TemplateTypeArgumentStrategy
{
public:
	/* new TemplateTypeArgumentStrategy(); UNDEF = pending exception */
	static zv::Val create() { return pt_new_instance(pt_ce_template_type_argument_strategy); }

	/* $right->isAcceptedBy($left, $strictTypes) for a compound $right;
	 * $left->getBound()->accepts($right, $strictTypes)->and(createMaybe())
	 * otherwise, with the contract reason appended when that is maybe;
	 * UNDEF = pending exception */
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
		zv::Val boundAccepts = pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("accepts"), 2, args);
		if (UNEXPECTED(boundAccepts.isUndef())) return zv::Val();
		zv::Val maybe = pt_type_accepts_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		zv::Val accepts = pt_type_result_and(std::move(boundAccepts), maybe.raw());
		if (UNEXPECTED(accepts.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(accepts.raw()).isObject())) {
			zend_type_error("phpstan_turbo: AcceptsResult::and() must return %s", ZSTR_VAL(pt_ce_accepts_result->name));
			return zv::Val();
		}
		zend_long value = pt_type_result_trinary(accepts.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value != PT_TRI_MAYBE) return accepts;

		/* new AcceptsResult($accepts->result, array_merge($accepts->reasons, [sprintf('Type %s is not always the same as %s. It breaks the contract for some argument types, typically subtypes.', $right->describe(VerbosityLevel::getRecommendedLevelByType($left, $right)), $left->getName())])) */
		zv::Val verbosity = pt_type_verbosity_recommended(left, right);
		if (UNEXPECTED(verbosity.isUndef())) return zv::Val();
		zv::Val rightDescription = pt_type_call(Z_OBJ_P(right), PT_LC("describe"), 1, verbosity.raw());
		if (UNEXPECTED(rightDescription.isUndef())) return zv::Val();
		zv::Val leftName = pt_type_call(Z_OBJ_P(left), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(leftName.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(rightDescription.raw()).isString() || !zv::Ref(leftName.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() and getName() must return string");
			return zv::Val();
		}
		zend_string *reason = zend_strpprintf(0, "Type %s is not always the same as %s. It breaks the contract for some argument types, typically subtypes.", Z_STRVAL_P(rightDescription.raw()), Z_STRVAL_P(leftName.raw()));
		zv::ObjRef acceptsObject(accepts.raw());
		zv::Ref result = acceptsObject.prop(PT_LC("result"));
		zv::Ref reasons = acceptsObject.prop(PT_LC("reasons"));
		if (UNEXPECTED(result.raw() == NULL || reasons.raw() == NULL || !reasons.isArray())) {
			zend_string_release(reason);
			zend_type_error("phpstan_turbo: %s carries no result and reasons", ZSTR_VAL(acceptsObject.ce()->name));
			return zv::Val();
		}
		zv::Arr merged = zv::Arr::create(zv::ArrRef(reasons.raw()).size() + 1);
		for (zv::ArrayEntry entry : zv::ArrRef(reasons.raw())) {
			if (entry.hasStringKey()) {
				merged.set(entry.stringKey(), zv::Val::copyOf(entry.value()));
			} else {
				merged.push(entry.value());
			}
		}
		merged.push(zv::Val::adoptString(reason));
		zval created;
		zval mergedValue = merged.take();
		if (UNEXPECTED(!pt_accepts_result_create(&created, result.raw(), &mergedValue))) return zv::Val();
		return zv::Val::adopt(created);
	}

	static bool isArgument() { return true; }
};

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeArgumentStrategy;

bool pt_template_type_argument_strategy_new(zval *out)
{
	return pt_val_into(TemplateTypeArgumentStrategy::create(), out);
}

zv::Val pt_template_type_argument_strategy_create()
{
	return TemplateTypeArgumentStrategy::create();
}

zv::Val pt_template_type_argument_strategy_accepts(zval *left, zval *right, bool strictTypes)
{
	return TemplateTypeArgumentStrategy::accepts(left, right, strictTypes);
}

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_template_type_argument_strategy()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeArgumentStrategy");
	ptdecl::TemplateTypeArgumentStrategy::declareClass(cls);
	ptdecl::TemplateTypeArgumentStrategy::declareProperties(cls);

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
		PT_RETURN_VAL(TemplateTypeArgumentStrategy::accepts(left, right, strictTypes));
	});

	cls.method(sigs::isArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(TemplateTypeArgumentStrategy::isArgument());
	});

	cls.shadow(&pt_ce_template_type_argument_strategy);
}

/* }}} */
