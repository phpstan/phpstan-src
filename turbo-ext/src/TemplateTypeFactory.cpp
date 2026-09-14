/*
 * PHPStanTurbo\TemplateTypeFactory — native implementation of
 * PHPStan\Type\Generic\TemplateTypeFactory.
 *
 * Declared as PHPStan\Type\Generic\TemplateTypeFactory itself at
 * activation: final, static. create() dispatches a bound to the
 * Template*Type class of its kind — an exact class test (`get_class($bound)
 * === X::class`, natively the class entry the native code holds) or a
 * template bound of that kind, in the twin's order — instantiating the
 * native classes directly; TemplateKeyOfType (a PHP class over the
 * unshadowed KeyOfType) goes through the class map.
 */

#include "TypeTraits.h"
#include "generated/TemplateTypeFactory.h"

namespace sigs = ptdecl::TemplateTypeFactory::sig;

zend_class_entry *pt_ce_template_type_factory = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeFactory (static only). */
class TemplateTypeFactory
{
public:
	/* create($scope, $name, $bound, $variance, $strategy, $default) —
	 * $bound / $strategy / $default NULL or IS_NULL for null; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *name, zval *bound, zval *variance, zval *strategy, zval *defaultType)
	{
		if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) {
			zend_argument_type_error(2, "must be of type string, %s given", zend_zval_value_name(name));
			return zv::Val();
		}
		zend_string *nameStr = Z_STR_P(name);
		zval nullValue;
		ZVAL_NULL(&nullValue);
		if (defaultType == NULL) {
			defaultType = &nullValue;
		}

		/* $strategy ??= new TemplateTypeParameterStrategy() */
		zv::Val defaultStrategy;
		if (strategy == NULL || Z_TYPE_P(strategy) == IS_NULL) {
			defaultStrategy = pt_template_type_parameter_strategy_create();
			if (UNEXPECTED(defaultStrategy.isUndef())) return zv::Val();
			strategy = defaultStrategy.raw();
		}

		if (bound == NULL || Z_TYPE_P(bound) == IS_NULL) return templateMixed(scope, strategy, variance, nameStr, defaultType);
		/* the twin's `?Type $bound` */
		bool isType = false;
		if (UNEXPECTED(Z_TYPE_P(bound) != IS_OBJECT || !pt_type_instanceof(bound, PT_CLASS_TYPE, isType) || !isType)) {
			if (!EG(exception)) {
				zend_argument_type_error(3, "must be of type ?%s, %s given", ptcls::type, zend_zval_value_name(bound));
			}
			return zv::Val();
		}

		zend_class_entry *boundClass = Z_OBJCE_P(bound);
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(bound, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();

		if (instanceof_function(boundClass, pt_ce_generic_object_type) && (boundClass == pt_ce_generic_object_type || isTemplate)) {
			return of(pt_template_generic_object_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		/* plain ObjectType and any other object subtype without a dedicated
		 * Template* class (enum-case object types), keeping the precise
		 * bound instead of widening it to TemplateMixedType */
		if (instanceof_function(boundClass, pt_ce_object_type)) return of(pt_template_object_type_new, scope, strategy, variance, nameStr, bound, defaultType);

		if (instanceof_function(boundClass, pt_ce_object_without_class_type) && (boundClass == pt_ce_object_without_class_type || isTemplate)) {
			return of(pt_template_object_without_class_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_array_type) && (boundClass == pt_ce_array_type || isTemplate)) {
			return of(pt_template_array_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_constant_array_type) && (boundClass == pt_ce_constant_array_type || isTemplate)) {
			return of(pt_template_constant_array_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_object_shape_type) && (boundClass == pt_ce_object_shape_type || isTemplate)) {
			return of(pt_template_object_shape_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_string_type) && (boundClass == pt_ce_string_type || isTemplate)) {
			return of(pt_template_string_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_constant_string_type) && (boundClass == pt_ce_constant_string_type || isTemplate)) {
			return of(pt_template_constant_string_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_integer_type) && (boundClass == pt_ce_integer_type || instanceof_function(boundClass, pt_ce_integer_range_type) || isTemplate)) {
			return of(pt_template_integer_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_constant_integer_type) && (boundClass == pt_ce_constant_integer_type || isTemplate)) {
			return of(pt_template_constant_integer_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_float_type) && (boundClass == pt_ce_float_type || isTemplate)) {
			return of(pt_template_float_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_boolean_type)) {
			/* $boundClass === BooleanType::class || $bound->isTrue()->yes() || $bound->isFalse()->yes() || $bound instanceof TemplateType — short-circuiting like the twin */
			bool matches = boundClass == pt_ce_boolean_type;
			if (!matches) {
				zend_long isTrue = pt_type_call_trinary(Z_OBJ_P(bound), PT_LC("istrue"), 0, NULL);
				if (UNEXPECTED(isTrue < 0)) return zv::Val();
				matches = isTrue == PT_TRI_YES;
			}
			if (!matches) {
				zend_long isFalse = pt_type_call_trinary(Z_OBJ_P(bound), PT_LC("isfalse"), 0, NULL);
				if (UNEXPECTED(isFalse < 0)) return zv::Val();
				matches = isFalse == PT_TRI_YES;
			}
			if (matches || isTemplate) return of(pt_template_boolean_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_mixed_type) && (boundClass == pt_ce_mixed_type || isTemplate)) {
			return of(pt_template_mixed_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_union_type)) {
			if (boundClass == pt_ce_union_type || instanceof_function(boundClass, pt_ce_template_union_type)) {
				return of(pt_template_union_type_new, scope, strategy, variance, nameStr, bound, defaultType);
			}

			if (instanceof_function(boundClass, pt_ce_benevolent_union_type)) {
				return of(pt_template_benevolent_union_type_new, scope, strategy, variance, nameStr, bound, defaultType);
			}
		}

		if (instanceof_function(boundClass, pt_ce_intersection_type)) {
			return of(pt_template_intersection_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		/* KeyOfType is native (its class entry); TemplateKeyOfType is a PHP class (the class map) */
		zend_class_entry *keyOfClass = pt_ce_key_of_type;
		if (instanceof_function(boundClass, keyOfClass) && (boundClass == keyOfClass || isTemplate)) {
			zval args[6];
			ZVAL_COPY_VALUE(&args[0], scope);
			ZVAL_COPY_VALUE(&args[1], strategy);
			ZVAL_COPY_VALUE(&args[2], variance);
			ZVAL_COPY_VALUE(&args[3], name);
			ZVAL_COPY_VALUE(&args[4], bound);
			ZVAL_COPY_VALUE(&args[5], defaultType);
			return pt_type_new(PT_CLASS_TEMPLATE_KEY_OF_TYPE, 6, args);
		}

		if (instanceof_function(boundClass, pt_ce_iterable_type) && (boundClass == pt_ce_iterable_type || isTemplate)) {
			return of(pt_template_iterable_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		if (instanceof_function(boundClass, pt_ce_null_type) && (boundClass == pt_ce_null_type || isTemplate)) {
			return of(pt_template_null_type_new, scope, strategy, variance, nameStr, bound, defaultType);
		}

		return templateMixed(scope, strategy, variance, nameStr, defaultType);
	}

	/* self::create($scope, $tag->getName(), $tag->getBound(), $tag->getVariance(), default: $tag->getDefault()) */
	static zv::Val fromTemplateTag(zval *scope, zval *tag)
	{
		zend_object *tagObject = Z_OBJ_P(tag);
		zv::Val name = pt_type_call(tagObject, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val bound = pt_type_call(tagObject, PT_LC("getbound"), 0, NULL);
		if (UNEXPECTED(bound.isUndef())) return zv::Val();
		zv::Val variance = pt_type_call(tagObject, PT_LC("getvariance"), 0, NULL);
		if (UNEXPECTED(variance.isUndef())) return zv::Val();
		zv::Val defaultType = pt_type_call(tagObject, PT_LC("getdefault"), 0, NULL);
		if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(variance.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::getVariance() must return %s", ZSTR_VAL(tagObject->ce->name), ptcls::templateTypeVariance);
			return zv::Val();
		}
		return create(scope, name.raw(), bound.raw(), variance.raw(), NULL, defaultType.raw());
	}

private:
	typedef bool (*pt_template_constructor)(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);

	/* new Template<X>Type($scope, $strategy, $variance, $name, $bound, $default) over a shadowed class's constructor */
	static zv::Val of(pt_template_constructor construct, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		zval created;
		if (UNEXPECTED(!construct(&created, scope, strategy, variance, name, bound, defaultType))) return zv::Val();
		return zv::Val::adopt(created);
	}

	/* new TemplateMixedType($scope, $strategy, $variance, $name, new MixedType(true), $default) */
	static zv::Val templateMixed(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *defaultType)
	{
		zval mixed;
		if (UNEXPECTED(!pt_mixed_type_new(&mixed, true))) return zv::Val();
		zv::Val bound = zv::Val::adopt(mixed);
		return of(pt_template_mixed_type_new, scope, strategy, variance, name, bound.raw(), defaultType);
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeFactory;

zv::Val pt_template_type_factory_create(zval *scope, zval *name, zval *bound, zval *variance, zval *strategy, zval *defaultType)
{
	return TemplateTypeFactory::create(scope, name, bound, variance, strategy, defaultType);
}

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_template_type_factory()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeFactory");
	ptdecl::TemplateTypeFactory::declareClass(cls);
	ptdecl::TemplateTypeFactory::declareProperties(cls);

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *bound, *variance, *strategy = NULL, *defaultType = NULL;
		zend_string *name;
		if (!zp::parse<zp::Obj, zp::Str, zp::ObjOrNull, zp::Obj, zp::Opt<zp::ObjOrNull>, zp::Opt<zp::ObjOrNull>>(execute_data, scope, name, bound, variance, strategy, defaultType)) RETURN_THROWS();
		zval nameValue;
		ZVAL_STR(&nameValue, name);
		PT_RETURN_VAL(TemplateTypeFactory::create(scope, &nameValue, bound, variance, strategy, defaultType));
	});

	cls.method(sigs::fromTemplateTag, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *tag;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, tag)) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeFactory::fromTemplateTag(scope, tag));
	});

	cls.shadow(&pt_ce_template_type_factory);
}

/* }}} */
