/*
 * PHPStanTurbo\FloatType — native implementation of PHPStan\Type\FloatType.
 *
 * Declared as PHPStan\Type\FloatType itself at activation: not final
 * (ConstantFloatType and the PHP TemplateFloatType extend it), implementing
 * PHPStan\Type\Type.
 */

#include "TypeTraits.h"
#include "generated/FloatType.h"

namespace sigs = ptdecl::FloatType::sig;

zend_class_entry *pt_ce_float_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\FloatType. The twin has no state. */
class FloatType
{
public:
	explicit FloatType(zend_object *self) : self(self) {}

	/* new FloatType(): the constructor is empty, so instantiating the
	 * class is all `new` does; UNDEF = pending exception */
	static zv::Val create() { return pt_new_instance(pt_ce_float_type); }

	static zv::Val getReferencedClasses() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getObjectClassNames() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getObjectClassReflections() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getConstantStrings() { return zv::Val(zv::Arr::empty()); }

	/* yes for a FloatType or when $type->isInteger()->yes(), the
	 * CompoundType callback, no otherwise; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		/* $type instanceof self */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_float_type)) return pt_type_accepts_result(PT_TRI_YES);
		zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_INTEGER, 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger == PT_TRI_YES) return pt_type_accepts_result(PT_TRI_YES);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		return pt_type_accepts_result(PT_TRI_NO);
	}

	/* yes for a FloatType, the CompoundType callback, no otherwise; UNDEF =
	 * pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		/* $type instanceof self */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_float_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* get_class($type) === static::class */
	bool equals(zval *type) const { return Z_OBJCE_P(type) == self->ce; }

	static const char *describe() { return "float"; }

	/* $this */
	zv::Val toNumber() const { return thisValue(); }

	/* new IntegerType() */
	static zv::Val toBitwiseNotType() { return integerType(); }

	/* $this */
	zv::Val toAbsoluteNumber() const { return thisValue(); }

	/* $this */
	zv::Val toFloat() const { return thisValue(); }

	/* new IntegerType() */
	static zv::Val toInteger() { return integerType(); }

	/* new IntersectionType([new StringType(), new AccessoryUppercaseStringType(),
	 * new AccessoryNumericStringType()]) */
	static zv::Val toString()
	{
		zval stringZv;
		if (UNEXPECTED(!pt_string_type_new(&stringZv))) return zv::Val();
		zv::Val string = zv::Val::adopt(stringZv);
		zv::Val uppercase = pt_type_new_shadowed(pt_accessory_uppercase_string_type_new);
		if (UNEXPECTED(uppercase.isUndef())) return zv::Val();
		zv::Val numeric = pt_type_new_shadowed(pt_accessory_numeric_string_type_new);
		if (UNEXPECTED(numeric.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(3);
		types.push(std::move(string));
		types.push(std::move(uppercase));
		types.push(std::move(numeric));
		return pt_intersection_of(std::move(types));
	}

	/* new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1],
	 * isList: TrinaryLogic::createYes()) — the named argument skips
	 * $optionalKeys, whose default is [] */
	zv::Val toArray() const { return pt_type_scalar_to_array(self); }

	/* new IntegerType() */
	static zv::Val toArrayKey() { return integerType(); }

	/* TypeCombinator::union($this->toInteger(), $this, $this->toString(),
	 * $this->toBoolean()) unless strict, $this otherwise; UNDEF = pending
	 * exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		if (!strictTypes) {
			zv::Val integer = isExact() ? toInteger() : pt_type_call(self, PT_LC("tointeger"), 0, NULL);
			if (UNEXPECTED(integer.isUndef())) return zv::Val();
			zv::Val string = isExact() ? toString() : pt_type_call(self, PT_LC("tostring"), 0, NULL);
			if (UNEXPECTED(string.isUndef())) return zv::Val();
			/* UndecidedBooleanTypeTrait's toBoolean(): new BooleanType() */
			zv::Val boolean;
			if (EXPECTED(isExact())) {
				zval created;
				if (UNEXPECTED(!pt_boolean_type_new(&created))) return zv::Val();
				boolean = zv::Val::adopt(created);
			} else {
				boolean = pt_type_call(self, PT_LC("toboolean"), 0, NULL);
				if (UNEXPECTED(boolean.isUndef())) return zv::Val();
			}
			zv::Args args{integer.raw(), self, string.raw(), boolean.raw()};
			return pt_type_combinator_call(PT_LC("union"), 4, args);
		}
		return thisValue();
	}

	static zend_long isOffsetAccessLegal() { return PT_TRI_YES; }
	static zend_long isNull() { return PT_TRI_NO; }
	static zend_long isConstantValue() { return PT_TRI_NO; }
	static zend_long isConstantScalarValue() { return PT_TRI_NO; }
	static zv::Val getConstantScalarTypes() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getConstantScalarValues() { return zv::Val(zv::Arr::empty()); }
	static zend_long isTrue() { return PT_TRI_NO; }
	static zend_long isFalse() { return PT_TRI_NO; }
	static zend_long isBoolean() { return PT_TRI_NO; }
	static zend_long isFloat() { return PT_TRI_YES; }
	static zend_long isInteger() { return PT_TRI_NO; }
	static zend_long isString() { return PT_TRI_NO; }
	static zend_long isNumericString() { return PT_TRI_NO; }
	static zend_long isDecimalIntegerString() { return PT_TRI_NO; }
	static zend_long isNonEmptyString() { return PT_TRI_NO; }
	static zend_long isNonFalsyString() { return PT_TRI_NO; }
	static zend_long isLiteralString() { return PT_TRI_NO; }
	static zend_long isLowercaseString() { return PT_TRI_NO; }
	static zend_long isClassString() { return PT_TRI_NO; }
	static zend_long isUppercaseString() { return PT_TRI_NO; }
	static zv::Val getClassStringObjectType() { return pt_type_new_error_type(); }
	static zv::Val getObjectTypeOrClassStringObjectType() { return pt_type_new_error_type(); }
	static zend_long isVoid() { return PT_TRI_NO; }
	static zend_long isScalar() { return PT_TRI_YES; }

	/* new BooleanType() */
	static zv::Val looseCompare()
	{
		return pt_val_of<pt_boolean_type_new>();
	}

	/* $this */
	zv::Val traverse() const { return thisValue(); }

	/* $this */
	zv::Val traverseSimultaneously() const { return thisValue(); }

	/* ExponentiateHelper::exponentiate($this, $exponent) */
	zv::Val exponentiate(zval *exponent) const
	{
		zv::Args args{self, exponent};
		return pt_type_call_static(PT_CLASS_EXPONENTIATE_HELPER, PT_LC("exponentiate"), 2, args);
	}

	/* new IdentifierTypeNode('float') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("float", 5);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	static zv::Val getFiniteTypes() { return zv::Val(zv::Arr::empty()); }

	static bool hasTemplateOrLateResolvableType() { return false; }

private:
	zend_object *self;

	/* exactly a FloatType, none of its methods overridden: $this-calls can
	 * go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_float_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* new IntegerType() — the shadowing class */
	static zv::Val integerType()
	{
		return pt_val_of<pt_integer_type_new>();
	}
};

} // namespace phpstanturbo

using phpstanturbo::FloatType;

bool pt_float_type_new(zval *out)
{
	return pt_val_into(FloatType::create(), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS FloatType(Z_OBJ_P(ZEND_THIS))

void pt_register_float_type()
{
	reg::Class cls("PHPStan\\Type\\FloatType");
	ptdecl::FloatType::declareClass(cls);
	ptdecl::FloatType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method<&FloatType::getReferencedClasses>(sigs::getReferencedClasses);

	cls.method<&FloatType::getObjectClassNames>(sigs::getObjectClassNames);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return FloatType::getObjectClassNames(); });

	cls.method<&FloatType::getObjectClassReflections>(sigs::getObjectClassReflections);

	cls.method<&FloatType::getConstantStrings>(sigs::getConstantStrings);

	cls.method<&FloatType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return FloatType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method<&FloatType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &FloatType::isSuperTypeOf>();

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(PT_THIS.equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(FloatType(self).equals(argv)); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		RETURN_STRING(FloatType::describe());
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return pt_op_string(FloatType::describe()); });

	cls.method<&FloatType::toNumber>(sigs::toNumber);

	cls.method<&FloatType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&FloatType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&FloatType::toFloat>(sigs::toFloat);

	cls.method<&FloatType::toInteger>(sigs::toInteger);

	cls.method<&FloatType::toString>(sigs::toString);

	cls.method<&FloatType::toArray>(sigs::toArray);

	cls.method<&FloatType::toArrayKey>(sigs::toArrayKey);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return FloatType::toArrayKey(); });

	cls.method<&FloatType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isOffsetAccessLegal()));
	});

	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isNull()));
	});
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(FloatType::isNull()); });

	cls.method(sigs::isConstantValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isConstantValue()));
	});

	cls.method(sigs::isConstantScalarValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isConstantScalarValue()));
	});
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(FloatType::isConstantScalarValue()); });

	cls.method<&FloatType::getConstantScalarTypes>(sigs::getConstantScalarTypes);

	cls.method<&FloatType::getConstantScalarValues>(sigs::getConstantScalarValues);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return FloatType::getConstantScalarValues(); });

	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isTrue()));
	});

	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isFalse()));
	});

	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isBoolean()));
	});
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(FloatType::isBoolean()); });

	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isFloat()));
	});
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(FloatType::isFloat()); });

	cls.method(sigs::isInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isInteger()));
	});
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(FloatType::isInteger()); });

	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isString()));
	});
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(FloatType::isString()); });

	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isNumericString()));
	});

	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isDecimalIntegerString()));
	});

	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isNonEmptyString()));
	});

	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isNonFalsyString()));
	});

	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isLiteralString()));
	});

	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isLowercaseString()));
	});

	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isClassString()));
	});

	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isUppercaseString()));
	});

	cls.method<&FloatType::getClassStringObjectType>(sigs::getClassStringObjectType);

	cls.method<&FloatType::getObjectTypeOrClassStringObjectType>(sigs::getObjectTypeOrClassStringObjectType);

	cls.method(sigs::isVoid, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isVoid()));
	});
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(FloatType::isVoid()); });

	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(FloatType::isScalar()));
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(FloatType::looseCompare());
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse());
	});
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { zend_fcall_info fci; zend_fcall_info_cache fcc; if (UNEXPECTED(!pt_op_parse_callable(argv, fci, fcc))) { return pt_type_call_engine(self, "traverse", sizeof("traverse") - 1, 1, argv); } return FloatType(self).traverse(); });

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously());
	});

	cls.method<&FloatType::exponentiate, zp::Obj>(sigs::exponentiate);

	cls.method<&FloatType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&FloatType::getFiniteTypes>(sigs::getFiniteTypes);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(FloatType::hasTemplateOrLateResolvableType());
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(FloatType::hasTemplateOrLateResolvableType()); });

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::FloatType::registerTraits(cls);

	cls.shadow(&pt_ce_float_type);
}

/* }}} */
