/*
 * PHPStanTurbo\NonexistentParentClassType — native implementation of
 * PHPStan\Type\NonexistentParentClassType.
 *
 * Declared as PHPStan\Type\NonexistentParentClassType itself at
 * activation: not final, implementing PHPStan\Type\Type, without state.
 * The ten traits the twin is composed of come from the shared registrars in
 * TypeTraits.cpp, run after the class's own methods so the class body wins
 * over the traits exactly as in PHP (isObject(), getClassStringType(),
 * toObjectTypeForIsACheck(), ... are the class's own).
 *
 * The bodies are the twin's one-liners: the registration below is the
 * engine ABI glue around them; the only `$this->method()` the twin makes
 * (toGetClassResultType() calling getClassStringType()) goes through the
 * object's class entry — a subclass may have overridden it.
 */

#include "TypeTraits.h"
#include "generated/NonexistentParentClassType.h"

namespace sigs = ptdecl::NonexistentParentClassType::sig;

zend_class_entry *pt_ce_nonexistent_parent_class_type = nullptr;

bool pt_nonexistent_parent_class_type_new(zval *out)
{
	if (UNEXPECTED(object_init_ex(out, pt_ce_nonexistent_parent_class_type) != SUCCESS)) return false;
	return true;
}

/* {{{ the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL npcNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL npcNo1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL npcEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL npcError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL npcError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL npcError2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL npcShouldNotHappen1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

static void ZEND_FASTCALL npcShouldNotHappen2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

/* }}} */

/* {{{ registration */

void pt_register_nonexistent_parent_class_type()
{
	reg::Class cls("PHPStan\\Type\\NonexistentParentClassType");
	ptdecl::NonexistentParentClassType::declareClass(cls);
	ptdecl::NonexistentParentClassType::declareProperties(cls);

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("parent", 6);
	});

	cls.method(sigs::getTemplateType, npcError2);

	cls.method(sigs::isObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY(PT_TRI_YES);
	});

	cls.method(sigs::getClassStringType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ClassStringType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_class_string_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::isEnum, npcNo0);
	cls.method(sigs::canAccessProperties, npcNo0);
	cls.method(sigs::hasProperty, npcNo1);
	cls.method(sigs::getProperty, npcShouldNotHappen2);
	cls.method(sigs::getUnresolvedPropertyPrototype, npcShouldNotHappen2);
	cls.method(sigs::hasInstanceProperty, npcNo1);
	cls.method(sigs::getInstanceProperty, npcShouldNotHappen2);
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, npcShouldNotHappen2);
	cls.method(sigs::hasStaticProperty, npcNo1);
	cls.method(sigs::getStaticProperty, npcShouldNotHappen2);
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, npcShouldNotHappen2);
	cls.method(sigs::canCallMethods, npcNo0);
	cls.method(sigs::hasMethod, npcNo1);
	cls.method(sigs::getMethod, npcShouldNotHappen2);
	cls.method(sigs::getUnresolvedMethodPrototype, npcShouldNotHappen2);
	cls.method(sigs::canAccessConstants, npcNo0);
	cls.method(sigs::hasConstant, npcNo1);
	cls.method(sigs::getConstant, npcShouldNotHappen1);
	cls.method(sigs::getConstantStrings, npcEmptyArray0);
	cls.method(sigs::isCloneable, npcNo0);
	cls.method(sigs::toNumber, npcError0);
	cls.method(sigs::toBitwiseNotType, npcError0);

	cls.method(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* $this->getClassStringType() — through the object's class */
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("getclassstringtype"), 0, NULL));
	});

	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		/* new IntersectionType([$this->getClassStringType(), new AccessoryLiteralStringType()]) */
		zv::Val classString = pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("getclassstringtype"), 0, NULL);
		if (UNEXPECTED(classString.isUndef())) RETURN_THROWS();
		zv::Val literal = pt_type_new(PT_CLASS_ACCESSORY_LITERAL_STRING_TYPE, 0, NULL);
		if (UNEXPECTED(literal.isUndef())) RETURN_THROWS();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(classString));
		types.push(std::move(literal));
		PT_RETURN_VAL(pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw()));
	});

	cls.method(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ClassNameToObjectTypeResult($this, true) */
		zv::Args args{ZEND_THIS, true};
		PT_RETURN_VAL(pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args));
	});

	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		PT_RETURN_VAL(pt_type_object_type_for_is_a_check(allowString));
	});

	cls.method(sigs::toAbsoluteNumber, npcError0);
	cls.method(sigs::toString, npcError0);
	cls.method(sigs::toInteger, npcError0);
	cls.method(sigs::toFloat, npcError0);
	cls.method(sigs::toArray, npcError0);
	cls.method(sigs::toArrayKey, npcError0);
	cls.method(sigs::toCoercedArgumentType, npcError1);
	cls.method(sigs::isOffsetAccessLegal, npcNo0);
	cls.method(sigs::isScalar, npcNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::getEnumCases, npcEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.method(sigs::exponentiate, npcError1);
	cls.method(sigs::getFiniteTypes, npcEmptyArray0);

	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new IdentifierTypeNode('parent') */
		zv::Val name = zv::Val::string("parent", 6);
		PT_RETURN_VAL(pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw()));
	});

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::NonexistentParentClassType::registerTraits(cls);

	cls.shadow(&pt_ce_nonexistent_parent_class_type);
}

/* }}} */
