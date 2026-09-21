/*
 * PHPStanTurbo\ClassStringType — native implementation of
 * PHPStan\Type\ClassStringType.
 *
 * Declared as PHPStan\Type\ClassStringType itself at activation: not final
 * (GenericClassStringType extends it), extending the native StringType
 * (declared first — Shadow.cpp materialises a parent plan before its
 * child). The twin has no state and uses no traits of its own; everything
 * it does not declare is inherited from StringType.
 */

#include "TypeTraits.h"
#include "generated/ClassStringType.h"

namespace sigs = ptdecl::ClassStringType::sig;

zend_class_entry *pt_ce_class_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\ClassStringType. The twin has no state. */
class ClassStringType
{
public:
	explicit ClassStringType(zend_object *self) : self(self) {}

	/* new ClassStringType(): the constructor only calls StringType's empty
	 * one, so instantiating the class is all `new` does; UNDEF = pending
	 * exception */
	static zv::Val create() { return pt_new_instance(pt_ce_class_string_type); }

	static const char *describe() { return "class-string"; }

	/* the CompoundType callback, else new AcceptsResult($type->isClassString(), []);
	 * UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		zend_long isClassString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isclassstring"), 0, NULL);
		if (UNEXPECTED(isClassString < 0)) return zv::Val();
		return pt_type_accepts_result(isClassString);
	}

	/* the CompoundType callback, else new IsSuperTypeOfResult($type->isClassString(), []);
	 * UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}

		zend_long isClassString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isclassstring"), 0, NULL);
		if (UNEXPECTED(isClassString < 0)) return zv::Val();
		return pt_type_is_super_type_of_result(isClassString);
	}

	static zend_long isString() { return PT_TRI_YES; }
	static zend_long isNumericString() { return PT_TRI_NO; }
	static zend_long isDecimalIntegerString() { return PT_TRI_NO; }
	static zend_long isNonEmptyString() { return PT_TRI_YES; }
	static zend_long isNonFalsyString() { return PT_TRI_YES; }
	static zend_long isLiteralString() { return PT_TRI_MAYBE; }
	static zend_long isLowercaseString() { return PT_TRI_MAYBE; }
	static zend_long isUppercaseString() { return PT_TRI_MAYBE; }
	static zend_long isClassString() { return PT_TRI_YES; }

	/* new ObjectWithoutClassType() */
	static zv::Val getClassStringObjectType() { return pt_type_new(PT_CLASS_OBJECT_WITHOUT_CLASS_TYPE, 0, NULL); }
	static zv::Val getObjectTypeOrClassStringObjectType() { return pt_type_new(PT_CLASS_OBJECT_WITHOUT_CLASS_TYPE, 0, NULL); }

	/* new IdentifierTypeNode('class-string') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("class-string", sizeof("class-string") - 1);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ClassStringType;

bool pt_class_string_type_new(zval *out)
{
	return pt_val_into(ClassStringType::create(), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ClassStringType(Z_OBJ_P(ZEND_THIS))

void pt_register_class_string_type()
{
	reg::Class cls("PHPStan\\Type\\ClassStringType");
	ptdecl::ClassStringType::declareClass(cls);
	ptdecl::ClassStringType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* parent::__construct() — StringType's empty constructor */
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		RETURN_STRING(ClassStringType::describe());
	});

	cls.method<&ClassStringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method<&ClassStringType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isString()));
	});

	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isNumericString()));
	});

	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isDecimalIntegerString()));
	});

	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isNonEmptyString()));
	});

	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isNonFalsyString()));
	});

	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isLiteralString()));
	});

	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isLowercaseString()));
	});

	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isUppercaseString()));
	});

	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ClassStringType::isClassString()));
	});

	cls.method<&ClassStringType::getClassStringObjectType>(sigs::getClassStringObjectType);

	cls.method<&ClassStringType::getObjectTypeOrClassStringObjectType>(sigs::getObjectTypeOrClassStringObjectType);

	cls.method<&ClassStringType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.shadow(&pt_ce_class_string_type);
}

/* }}} */
