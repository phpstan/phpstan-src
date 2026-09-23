/*
 * PHPStanTurbo\TemplateTypeVariance — native implementation of
 * PHPStan\Type\Generic\TemplateTypeVariance.
 *
 * When the extension is active, PHPStan\Type\Generic\TemplateTypeVariance is
 * this class, declared under that name at activation (final, like the
 * twin). The five singletons live where the twin keeps them — in the
 * class's private static $registry, keyed by value — so one process shares
 * one instance per variance exactly as the twin does. isValidVariance()
 * runs the twin's chain of instanceof probes and Type calls natively.
 */

#include "support.h"
#include "generated/TemplateTypeVariance.h"

namespace slots = ptdecl::TemplateTypeVariance::slot;
namespace sigs = ptdecl::TemplateTypeVariance::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_template_type_variance = NULL;

/* the twin's `private static array $registry` slot (borrowed; resolved
 * once per activated class, as VerbosityLevel.cpp resolves its statics) */
static zend_class_entry *pt_ttv_registry_ce = nullptr;
static zval *pt_ttv_registry_slot = nullptr;

static zval *pt_ttv_registry()
{
	zend_class_entry *ce = pt_ce_template_type_variance;
	if (UNEXPECTED(pt_ttv_registry_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("registry"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_ttv_registry_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_ttv_registry_ce = ce;
	}
	return pt_ttv_registry_slot;
}

/* Class::NAME — a literal class constant, borrowed; NULL = pending
 * exception */
static zval *pt_ttv_class_constant(zend_class_entry *ce, const char *name, size_t len)
{
	zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, name, len);
	if (UNEXPECTED(constant == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), name);
		return NULL;
	}
	if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return NULL;
	return &constant->value;
}

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeVariance. State lives in the PHP
 * object's $value. */
class TemplateTypeVariance
{
public:
	static constexpr zend_long INVARIANT = PT_TEMPLATE_TYPE_VARIANCE_INVARIANT;
	static constexpr zend_long COVARIANT = PT_TEMPLATE_TYPE_VARIANCE_COVARIANT;
	static constexpr zend_long CONTRAVARIANT = PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT;
	static constexpr zend_long STATIC = PT_TEMPLATE_TYPE_VARIANCE_STATIC;
	static constexpr zend_long BIVARIANT = PT_TEMPLATE_TYPE_VARIANCE_BIVARIANT;

	explicit TemplateTypeVariance(zend_object *self) : self(self) {}

	/* $this->value; -1 with an Error pending when uninitialized */
	[[nodiscard]] zend_long value() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_LONG)) {
			zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(pt_ce_template_type_variance->name));
			return -1;
		}
		return Z_LVAL_P(slot);
	}

	void construct(zend_long value) { ZVAL_LONG(OBJ_PROP_NUM(self, slots::value), value); }

	/* new self($value); UNDEF = pending exception */
	static zv::Val create_(zend_long value)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_template_type_variance) != SUCCESS)) return zv::Val();
		ZVAL_LONG(OBJ_PROP_NUM(Z_OBJ(object), slots::value), value);
		return zv::Val::adopt(object);
	}

	/* self::$registry[$value] ??= new self($value) — the singleton,
	 * borrowed; NULL = pending exception */
	[[nodiscard]] static zval *create(zend_long value)
	{
		if (UNEXPECTED(pt_ce_template_type_variance == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: TemplateTypeVariance used before the shadowing classes were activated");
			return NULL;
		}
		zval *registry = pt_ttv_registry();
		if (Z_TYPE_P(registry) != IS_ARRAY) {
			/* the uninitialized typed static array a dim write initializes */
			array_init(registry);
		}
		SEPARATE_ARRAY(registry);
		zval *entry = zend_hash_index_find(Z_ARRVAL_P(registry), (zend_ulong) value);
		if (entry == NULL || Z_TYPE_P(entry) == IS_NULL) {
			zv::Val created = create_(value);
			if (UNEXPECTED(created.isUndef())) return NULL;
			zval v = created.take();
			entry = zend_hash_index_update(Z_ARRVAL_P(registry), (zend_ulong) value, &v);
		}
		return entry;
	}

	static zval *createInvariant() { return create(INVARIANT); }
	static zval *createCovariant() { return create(COVARIANT); }
	static zval *createContravariant() { return create(CONTRAVARIANT); }
	static zval *createStatic() { return create(STATIC); }
	static zval *createBivariant() { return create(BIVARIANT); }

	bool invariant() const { return value() == INVARIANT; }
	bool covariant() const { return value() == COVARIANT; }
	bool contravariant() const { return value() == CONTRAVARIANT; }
	bool static_() const { return value() == STATIC; }
	bool bivariant() const { return value() == BIVARIANT; }

	/* the $value of any variance instance: the slot of the native class,
	 * the is-queries of anything else (the PHP twin declared next to the
	 * native class in the differential tests); false = pending exception */
	[[nodiscard]] static bool valueOf(zval *variance, zend_long &out)
	{
		if (UNEXPECTED(Z_TYPE_P(variance) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected %s, %s given", pt_ce_template_type_variance != NULL ? ZSTR_VAL(pt_ce_template_type_variance->name) : "PHPStan\\Type\\Generic\\TemplateTypeVariance", zend_zval_value_name(variance));
			return false;
		}
		if (EXPECTED(Z_OBJCE_P(variance) == pt_ce_template_type_variance)) {
			zend_long value = TemplateTypeVariance(Z_OBJ_P(variance)).value();
			if (UNEXPECTED(value < 0)) return false;
			out = value;
			return true;
		}
		static const struct { const char *lcname; size_t len; zend_long value; } queries[] = {
			{ PT_LC("invariant"), INVARIANT },
			{ PT_LC("covariant"), COVARIANT },
			{ PT_LC("contravariant"), CONTRAVARIANT },
			{ PT_LC("static"), STATIC },
			{ PT_LC("bivariant"), BIVARIANT },
		};
		for (const auto &query : queries) {
			zv::Val answer = pt_type_call(Z_OBJ_P(variance), query.lcname, query.len, 0, NULL);
			if (UNEXPECTED(answer.isUndef())) return false;
			if (zend_is_true(answer.raw())) {
				out = query.value;
				return true;
			}
		}
		zend_throw_error(NULL, "phpstan_turbo: %s answers no variance query", ZSTR_VAL(Z_OBJCE_P(variance)->name));
		return false;
	}

	/* compose(): the singleton for the composed variance, or $other itself;
	 * an owned value, UNDEF = pending exception */
	zv::Val compose(zval *other) const
	{
		zend_long thisValue = value();
		if (UNEXPECTED(thisValue < 0)) return zv::Val();
		zend_long otherValue = 0;
		if (UNEXPECTED(!valueOf(other, otherValue))) return zv::Val();
		zval *result = NULL;
		if (thisValue == CONTRAVARIANT) {
			if (otherValue == CONTRAVARIANT) {
				result = createCovariant();
			} else if (otherValue == COVARIANT) {
				result = createContravariant();
			} else if (otherValue == BIVARIANT) {
				result = createBivariant();
			} else {
				result = createInvariant();
			}
		} else if (thisValue == COVARIANT) {
			if (otherValue == CONTRAVARIANT) {
				result = createContravariant();
			} else if (otherValue == COVARIANT) {
				result = createCovariant();
			} else if (otherValue == BIVARIANT) {
				result = createBivariant();
			} else {
				result = createInvariant();
			}
		} else if (thisValue == INVARIANT) {
			result = createInvariant();
		} else if (thisValue == BIVARIANT) {
			result = createBivariant();
		} else {
			return zv::Val::copyOf(zv::Ref(other));
		}
		if (UNEXPECTED(result == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(result));
	}

	zv::Val isValidVariance(zval *templateType, zval *a, zval *b, bool strict) const;

	bool equals(zval *other, bool &out) const
	{
		zend_long thisValue = value();
		if (UNEXPECTED(thisValue < 0)) return false;
		zend_long otherValue = 0;
		if (UNEXPECTED(!valueOf(other, otherValue))) return false;
		out = otherValue == thisValue;
		return true;
	}

	bool validPosition(zval *other, bool &out) const
	{
		zend_long thisValue = value();
		if (UNEXPECTED(thisValue < 0)) return false;
		zend_long otherValue = 0;
		if (UNEXPECTED(!valueOf(other, otherValue))) return false;
		out = otherValue == thisValue
			|| otherValue == INVARIANT
			|| thisValue == BIVARIANT
			|| thisValue == STATIC;
		return true;
	}

	/* an owned string; UNDEF = pending exception */
	zv::Val describe() const
	{
		switch (value()) {
			case INVARIANT:
				return zv::Val::string(PT_LC("invariant"));
			case COVARIANT:
				return zv::Val::string(PT_LC("covariant"));
			case CONTRAVARIANT:
				return zv::Val::string(PT_LC("contravariant"));
			case STATIC:
				return zv::Val::string(PT_LC("static"));
			case BIVARIANT:
				return zv::Val::string(PT_LC("bivariant"));
			case -1:
				return zv::Val();
		}
		pt_throw_should_not_happen();
		return zv::Val();
	}

	/* GenericTypeNode::VARIANCE_*; an owned string, UNDEF = pending
	 * exception */
	zv::Val toPhpDocNodeVariance() const
	{
		const char *name = NULL;
		size_t len = 0;
		switch (value()) {
			case INVARIANT:
				name = "VARIANCE_INVARIANT";
				len = sizeof("VARIANCE_INVARIANT") - 1;
				break;
			case COVARIANT:
				name = "VARIANCE_COVARIANT";
				len = sizeof("VARIANCE_COVARIANT") - 1;
				break;
			case CONTRAVARIANT:
				name = "VARIANCE_CONTRAVARIANT";
				len = sizeof("VARIANCE_CONTRAVARIANT") - 1;
				break;
			case BIVARIANT:
				name = "VARIANCE_BIVARIANT";
				len = sizeof("VARIANCE_BIVARIANT") - 1;
				break;
			case -1:
				return zv::Val();
			default:
				pt_throw_should_not_happen();
				return zv::Val();
		}
		zend_class_entry *nodeCe = pt_class(PT_CLASS_GENERIC_TYPE_NODE);
		if (UNEXPECTED(nodeCe == NULL)) return zv::Val();
		zval *constant = pt_ttv_class_constant(nodeCe, name, len);
		if (UNEXPECTED(constant == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(constant) != IS_STRING)) {
			zend_type_error("phpstan_turbo: %s::%s must be a string", ZSTR_VAL(nodeCe->name), name);
			return zv::Val();
		}
		return zv::Val::copyOf(zv::Ref(constant));
	}

private:
	zend_object *self;
};

/* $a->isSuperTypeOf($b)'s trinary value; -1 = pending exception */
[[nodiscard]] static zend_long isSuperTypeOfValue(zval *a, zval *b)
{
	zv::Val result = pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_result_trinary(result.raw());
}

/* $x instanceof MixedType && !$x instanceof TemplateType; false with an
 * exception pending when the interface cannot be resolved */
static bool isPlainMixed(zval *x, bool &out)
{
	if (!zv::Ref(x).instanceOf(pt_ce_mixed_type)) {
		out = false;
		return true;
	}
	bool isTemplate;
	if (UNEXPECTED(!pt_type_instanceof(x, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return false;
	out = !isTemplate;
	return true;
}

/* $type->getScope() (an object); UNDEF = pending exception */
static zv::Val scopeOf(zval *type)
{
	zv::Val scope = pt_type_call(Z_OBJ_P(type), PT_LC("getscope"), 0, NULL);
	if (UNEXPECTED(scope.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(scope.raw()).isObject())) {
		zend_type_error("phpstan_turbo: getScope() must return an object");
		return zv::Val();
	}
	return scope;
}

/* $type->getName() (a string); UNDEF = pending exception */
static zv::Val nameOf(zval *type)
{
	zv::Val name = pt_type_call(Z_OBJ_P(type), PT_LC("getname"), 0, NULL);
	if (UNEXPECTED(name.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
		zend_type_error("phpstan_turbo: getName() must return string");
		return zv::Val();
	}
	return name;
}

zv::Val TemplateTypeVariance::isValidVariance(zval *templateType, zval *a, zval *b, bool strict) const
{
	zend_long thisValue = value();
	if (UNEXPECTED(thisValue < 0)) return zv::Val();

	if (zv::Ref(b).instanceOf(pt_ce_never_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);

	bool aIsPlainMixed, bIsPlainMixed;
	if (UNEXPECTED(!isPlainMixed(a, aIsPlainMixed) || !isPlainMixed(b, bIsPlainMixed))) return zv::Val();
	bool aIsBenevolent = zv::Ref(a).instanceOf(pt_ce_benevolent_union_type);
	bool bIsBenevolent = zv::Ref(b).instanceOf(pt_ce_benevolent_union_type);

	if (!strict) {
		if (aIsPlainMixed) return pt_type_is_super_type_of_result(PT_TRI_YES);

		if (aIsBenevolent) {
			zend_long isSuperType = isSuperTypeOfValue(a, b);
			if (UNEXPECTED(isSuperType < 0)) return zv::Val();
			if (isSuperType != PT_TRI_NO) return pt_type_is_super_type_of_result(PT_TRI_YES);
		}

		if (bIsBenevolent) {
			zend_long isSuperType = isSuperTypeOfValue(b, a);
			if (UNEXPECTED(isSuperType < 0)) return zv::Val();
			if (isSuperType != PT_TRI_NO) return pt_type_is_super_type_of_result(PT_TRI_YES);
		}

		if (bIsPlainMixed) return pt_type_is_super_type_of_result(PT_TRI_YES);
	}

	if (thisValue == INVARIANT) {
		bool aIsTemplate, bIsTemplate;
		if (UNEXPECTED(!pt_type_instanceof(a, PT_CLASS_TEMPLATE_TYPE, aIsTemplate) || !pt_type_instanceof(b, PT_CLASS_TEMPLATE_TYPE, bIsTemplate))) {
			return zv::Val();
		}
		if (aIsTemplate && bIsTemplate) {
			zv::Val aScope = scopeOf(a);
			if (UNEXPECTED(aScope.isUndef())) return zv::Val();
			zv::Val bScope = scopeOf(b);
			if (UNEXPECTED(bScope.isUndef())) return zv::Val();
			bool scopesEqual;
			if (UNEXPECTED(!pt_template_type_scope_equals(aScope.raw(), bScope.raw(), scopesEqual))) return zv::Val();
			if (scopesEqual) {
				zv::Val aName = nameOf(a);
				if (UNEXPECTED(aName.isUndef())) return zv::Val();
				zv::Val bName = nameOf(b);
				if (UNEXPECTED(bName.isUndef())) return zv::Val();
				if (zend_string_equals(Z_STR_P(aName.raw()), Z_STR_P(bName.raw()))) return pt_type_is_super_type_of_result(PT_TRI_YES);
			}
		}

		if (strict) {
			if (aIsPlainMixed) return pt_type_is_super_type_of_result(PT_TRI_YES);

			if (aIsBenevolent) {
				zend_long isSuperType = isSuperTypeOfValue(a, b);
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				if (isSuperType != PT_TRI_NO) return pt_type_is_super_type_of_result(PT_TRI_YES);
			}

			if (bIsBenevolent) {
				zend_long isSuperType = isSuperTypeOfValue(b, a);
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				if (isSuperType != PT_TRI_NO) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			}

			if (bIsPlainMixed) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		}

		bool result = pt_call_type_equals(a, b);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		zv::Arr reasons = zv::Arr::empty();
		if (!result) {
			zv::Val scope = scopeOf(templateType);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			zv::Val className = pt_type_call(Z_OBJ_P(scope.raw()), PT_LC("getclassname"), 0, NULL);
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			if (!className.isNull()) {
				zend_long isSuperType = isSuperTypeOfValue(a, b);
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				if (isSuperType == PT_TRI_YES) {
					if (UNEXPECTED(!zv::Ref(className.raw()).isString())) {
						zend_type_error("phpstan_turbo: getClassName() must return ?string");
						return zv::Val();
					}
					zv::Val templateName = nameOf(templateType);
					if (UNEXPECTED(templateName.isUndef())) return zv::Val();
					zend_string *reason = zend_strpprintf(0, "Template type %s on class %s is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>", Z_STRVAL_P(templateName.raw()), Z_STRVAL_P(className.raw()));
					reasons = zv::Arr::create(1);
					reasons.push(zv::Val::adoptString(reason));
				}
			}
		}

		/* new IsSuperTypeOfResult(TrinaryLogic::createFromBoolean($result), $reasons) */
		zv::Args args{pt_trinary_singleton(result ? PT_TRI_YES : PT_TRI_NO), reasons.raw()};
		return pt_type_new_ce(pt_ce_is_super_type_of_result, 2, args);
	}

	if (thisValue == COVARIANT) return pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);

	if (thisValue == CONTRAVARIANT) return pt_type_op(Z_OBJ_P(b), PT_OP_IS_SUPER_TYPE_OF, 1, a);

	if (thisValue == BIVARIANT) return pt_type_is_super_type_of_result(PT_TRI_YES);

	pt_throw_should_not_happen();
	return zv::Val();
}

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeVariance;

/* {{{ exported helpers */

zval *pt_template_type_variance_singleton(zend_long value)
{
	switch (value) {
		case PT_TEMPLATE_TYPE_VARIANCE_INVARIANT:
		case PT_TEMPLATE_TYPE_VARIANCE_COVARIANT:
		case PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT:
		case PT_TEMPLATE_TYPE_VARIANCE_STATIC:
		case PT_TEMPLATE_TYPE_VARIANCE_BIVARIANT:
			return TemplateTypeVariance::create(value);
		default:
			zend_throw_error(NULL, "phpstan_turbo: no TemplateTypeVariance with value " ZEND_LONG_FMT, value);
			return NULL;
	}
}

bool pt_template_type_variance_value_of(zval *variance, zend_long &out)
{
	return TemplateTypeVariance::valueOf(variance, out);
}

bool pt_template_type_variance_compose(zval *out, zval *self, zval *other)
{
	zv::Val result;
	if (EXPECTED(Z_TYPE_P(self) == IS_OBJECT && Z_OBJCE_P(self) == pt_ce_template_type_variance)) {
		result = TemplateTypeVariance(Z_OBJ_P(self)).compose(other);
	} else if (Z_TYPE_P(self) == IS_OBJECT) {
		/* the PHP twin declared next to the native class in the
		 * differential tests: its compose() */
		result = pt_type_call(Z_OBJ_P(self), PT_LC("compose"), 1, other);
	} else {
		zend_type_error("phpstan_turbo: expected %s, %s given", pt_ce_template_type_variance != NULL ? ZSTR_VAL(pt_ce_template_type_variance->name) : "PHPStan\\Type\\Generic\\TemplateTypeVariance", zend_zval_value_name(self));
		return false;
	}
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

zv::Val pt_type_template_type_variance(zend_long value)
{
	zval *variance = pt_template_type_variance_singleton(value);
	if (UNEXPECTED(variance == NULL)) return zv::Val();
	return zv::Val::copyOf(zv::Ref(variance));
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TTV_THIS TemplateTypeVariance(Z_OBJ_P(ZEND_THIS))
#define PT_TTV_CLASS "PHPStan\\Type\\Generic\\TemplateTypeVariance"

/* the singleton factories: the borrowed singleton copied into the return
 * value */
static void pt_ttv_return_singleton(zval *return_value, zval *variance)
{
	if (UNEXPECTED(variance == NULL)) RETURN_THROWS();
	RETURN_COPY(variance);
}

/* the is-queries: false with an Error pending when uninitialized */
static void pt_ttv_return_is(zval *return_value, zval *thisZv, zend_long variance)
{
	zend_long value = TemplateTypeVariance(Z_OBJ_P(thisZv)).value();
	if (UNEXPECTED(value < 0)) RETURN_THROWS();
	RETURN_BOOL(value == variance);
}

void pt_register_template_type_variance()
{

	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeVariance");
	ptdecl::TemplateTypeVariance::declareClass(cls);
	cls.privateClassConstantLong("INVARIANT", TemplateTypeVariance::INVARIANT);
	cls.privateClassConstantLong("COVARIANT", TemplateTypeVariance::COVARIANT);
	cls.privateClassConstantLong("CONTRAVARIANT", TemplateTypeVariance::CONTRAVARIANT);
	cls.privateClassConstantLong("STATIC", TemplateTypeVariance::STATIC);
	cls.privateClassConstantLong("BIVARIANT", TemplateTypeVariance::BIVARIANT);
	ptdecl::TemplateTypeVariance::declareProperties(cls);
	/* "value" must stay the first declared instance property (OBJ_PROP_NUM slot 0) */

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		if (!zp::parse<zp::Long>(execute_data, value)) RETURN_THROWS();
		PT_TTV_THIS.construct(value);
	});

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		if (!zp::parse<zp::Long>(execute_data, value)) RETURN_THROWS();
		pt_ttv_return_singleton(return_value, TemplateTypeVariance::create(value));
	});

	cls.method(sigs::createInvariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_singleton(return_value, TemplateTypeVariance::createInvariant());
	});

	cls.method(sigs::createCovariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_singleton(return_value, TemplateTypeVariance::createCovariant());
	});

	cls.method(sigs::createContravariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_singleton(return_value, TemplateTypeVariance::createContravariant());
	});

	cls.method(sigs::createStatic, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_singleton(return_value, TemplateTypeVariance::createStatic());
	});

	cls.method(sigs::createBivariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_singleton(return_value, TemplateTypeVariance::createBivariant());
	});

	cls.method(sigs::invariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_is(return_value, ZEND_THIS, TemplateTypeVariance::INVARIANT);
	});

	cls.method(sigs::covariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_is(return_value, ZEND_THIS, TemplateTypeVariance::COVARIANT);
	});

	cls.method(sigs::contravariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_is(return_value, ZEND_THIS, TemplateTypeVariance::CONTRAVARIANT);
	});

	cls.method(sigs::static_, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_is(return_value, ZEND_THIS, TemplateTypeVariance::STATIC);
	});

	cls.method(sigs::bivariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_ttv_return_is(return_value, ZEND_THIS, TemplateTypeVariance::BIVARIANT);
	});

	cls.method(sigs::compose, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_template_type_variance)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_TTV_THIS.compose(other));
	});

	cls.method(sigs::isValidVariance, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *templateType, *a, *b;
		bool strict = false;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Opt<zp::Bool>>(execute_data, templateType, a, b, strict)) RETURN_THROWS();
		PT_RETURN_VAL(PT_TTV_THIS.isValidVariance(templateType, a, b, strict));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_template_type_variance)
		ZEND_PARSE_PARAMETERS_END();
		bool result;
		if (UNEXPECTED(!PT_TTV_THIS.equals(other, result))) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::validPosition, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_template_type_variance)
		ZEND_PARSE_PARAMETERS_END();
		bool result;
		if (UNEXPECTED(!PT_TTV_THIS.validPosition(other, result))) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTV_THIS.describe());
	});

	cls.method(sigs::toPhpDocNodeVariance, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTV_THIS.toPhpDocNodeVariance());
	});

	cls.shadow(&pt_ce_template_type_variance);
}

/* }}} */
