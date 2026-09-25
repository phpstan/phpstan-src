/*
 * PHPStanTurbo\VoidType — native implementation of PHPStan\Type\VoidType.
 *
 * The twin makes no `$this->method()` calls of its own.
 */

#include "TypeTraits.h"
#include "generated/VoidType.h"

namespace sigs = ptdecl::VoidType::sig;

zend_class_entry *pt_ce_void_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\VoidType. The twin has no state. */
class VoidType
{
public:
	explicit VoidType(zend_object *self) : self(self) {}

	/* new VoidType(): the constructor is empty, so instantiating the class
	 * is all `new` does; UNDEF = pending exception */
	static zv::Val create() { return pt_new_instance(pt_ce_void_type); }

	static zv::Val getReferencedClasses() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getObjectClassNames() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getObjectClassReflections() { return zv::Val(zv::Arr::empty()); }

	/* the CompoundType callback, else new AcceptsResult($type->isVoid()->or($type->isNull()), [])
	 * — TrinaryLogic::or() is the bitwise or of the values; UNDEF =
	 * pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_VOID, 0, NULL);
		if (UNEXPECTED(isVoid < 0)) return zv::Val();
		zend_long isNull = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return zv::Val();
		zval reasons;
		ZVAL_EMPTY_ARRAY(&reasons);
		zval result;
		if (UNEXPECTED(!pt_accepts_result_create(&result, pt_trinary_singleton(isVoid | isNull), &reasons))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* yes for a VoidType, the CompoundType callback, no otherwise; UNDEF =
	 * pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		/* $type instanceof self */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_void_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $type instanceof self */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_void_type); }

	static const char *describe() { return "void"; }

	static zv::Val toNumber() { return pt_type_new_error_type(); }
	static zv::Val toBitwiseNotType() { return pt_type_new_error_type(); }
	static zv::Val toAbsoluteNumber() { return pt_type_new_error_type(); }
	static zv::Val toString() { return pt_type_new_error_type(); }
	static zv::Val toInteger() { return pt_type_new_error_type(); }
	static zv::Val toFloat() { return pt_type_new_error_type(); }
	static zv::Val toArray() { return pt_type_new_error_type(); }
	static zv::Val toArrayKey() { return pt_type_new_error_type(); }

	/* new NullType() — the shadowing class */
	static zv::Val toCoercedArgumentType()
	{
		return pt_val_of<pt_null_type_new>();
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
	static zend_long isFloat() { return PT_TRI_NO; }
	static zend_long isInteger() { return PT_TRI_NO; }
	static zend_long isString() { return PT_TRI_NO; }
	static zend_long isNumericString() { return PT_TRI_NO; }
	static zend_long isDecimalIntegerString() { return PT_TRI_NO; }
	static zend_long isNonEmptyString() { return PT_TRI_NO; }
	static zend_long isNonFalsyString() { return PT_TRI_NO; }
	static zend_long isLiteralString() { return PT_TRI_NO; }
	static zend_long isLowercaseString() { return PT_TRI_NO; }
	static zend_long isUppercaseString() { return PT_TRI_NO; }
	static zend_long isClassString() { return PT_TRI_NO; }
	static zv::Val getClassStringObjectType() { return pt_type_new_error_type(); }
	static zv::Val getObjectTypeOrClassStringObjectType() { return pt_type_new_error_type(); }
	static zend_long isVoid() { return PT_TRI_YES; }
	static zend_long isScalar() { return PT_TRI_NO; }

	/* new BooleanType() */
	static zv::Val looseCompare()
	{
		return pt_val_of<pt_boolean_type_new>();
	}

	/* $this */
	zv::Val traverse() const { return thisValue(); }

	/* $this */
	zv::Val traverseSimultaneously() const { return thisValue(); }

	static zv::Val exponentiate() { return pt_type_new_error_type(); }

	static zv::Val getFiniteTypes() { return zv::Val(zv::Arr::empty()); }

	/* new IdentifierTypeNode('void') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("void", 4);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	static bool hasTemplateOrLateResolvableType() { return false; }

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }
};

} // namespace phpstanturbo

using phpstanturbo::VoidType;

bool pt_void_type_new(zval *out)
{
	return pt_val_into(VoidType::create(), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS VoidType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_void_type)
{
	reg::Class cls("PHPStan\\Type\\VoidType");
	ptdecl::VoidType::declareClass(cls);
	ptdecl::VoidType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method<&VoidType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op(PT_OP_GET_REFERENCED_CLASSES, PT_OP_LAMBDA { return VoidType::getReferencedClasses(); });

	cls.method<&VoidType::getObjectClassNames>(sigs::getObjectClassNames);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return VoidType::getObjectClassNames(); });

	cls.method<&VoidType::getObjectClassReflections>(sigs::getObjectClassReflections);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return VoidType::getObjectClassReflections(); });

	cls.method<&VoidType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return VoidType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method<&VoidType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &VoidType::isSuperTypeOf>();

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::TypeObj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(VoidType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(VoidType::equals(argv)); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		RETURN_STRING(VoidType::describe());
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return pt_op_string(VoidType::describe()); });

	cls.method<&VoidType::toNumber>(sigs::toNumber);

	cls.method<&VoidType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&VoidType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&VoidType::toString>(sigs::toString);

	cls.method<&VoidType::toInteger>(sigs::toInteger);

	cls.method<&VoidType::toFloat>(sigs::toFloat);

	cls.method<&VoidType::toArray>(sigs::toArray);

	cls.method<&VoidType::toArrayKey>(sigs::toArrayKey);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return VoidType::toArrayKey(); });

	cls.method(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool strictTypes;
		if (!zp::parse<zp::Bool>(execute_data, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(VoidType::toCoercedArgumentType());
	});

	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isOffsetAccessLegal()));
	});

	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isNull()));
	});
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(VoidType::isNull()); });

	cls.method(sigs::isConstantValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isConstantValue()));
	});

	cls.method(sigs::isConstantScalarValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isConstantScalarValue()));
	});
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(VoidType::isConstantScalarValue()); });

	cls.method<&VoidType::getConstantScalarTypes>(sigs::getConstantScalarTypes);

	cls.method<&VoidType::getConstantScalarValues>(sigs::getConstantScalarValues);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return VoidType::getConstantScalarValues(); });

	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isTrue()));
	});

	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isFalse()));
	});

	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isBoolean()));
	});
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(VoidType::isBoolean()); });

	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isFloat()));
	});
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(VoidType::isFloat()); });

	cls.method(sigs::isInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isInteger()));
	});
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(VoidType::isInteger()); });

	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isString()));
	});
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(VoidType::isString()); });

	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isNumericString()));
	});

	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isDecimalIntegerString()));
	});

	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isNonEmptyString()));
	});

	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isNonFalsyString()));
	});

	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isLiteralString()));
	});

	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isLowercaseString()));
	});

	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isUppercaseString()));
	});

	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isClassString()));
	});

	cls.method<&VoidType::getClassStringObjectType>(sigs::getClassStringObjectType);

	cls.method<&VoidType::getObjectTypeOrClassStringObjectType>(sigs::getObjectTypeOrClassStringObjectType);

	cls.method(sigs::isVoid, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isVoid()));
	});
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(VoidType::isVoid()); });

	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(VoidType::isScalar()));
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(VoidType::looseCompare());
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse());
	});
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { zend_fcall_info fci; zend_fcall_info_cache fcc; if (UNEXPECTED(!pt_op_parse_callable(argv, fci, fcc))) { return pt_type_call_engine(self, "traverse", sizeof("traverse") - 1, 1, argv); } return VoidType(self).traverse(); });

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

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exponent;
		if (!zp::parse<zp::Obj>(execute_data, exponent)) RETURN_THROWS();
		PT_RETURN_VAL(VoidType::exponentiate());
	});

	cls.method<&VoidType::getFiniteTypes>(sigs::getFiniteTypes);

	cls.method<&VoidType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(VoidType::hasTemplateOrLateResolvableType());
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(VoidType::hasTemplateOrLateResolvableType()); });

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::VoidType::registerTraits(cls);

	cls.shadow(&pt_ce_void_type);
}

/* }}} */
