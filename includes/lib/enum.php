<?php

// Basic enumeration solution.
// Credit / Source: https://stackoverflow.com/a/254543

abstract class BasicEnum {
	private static $const_cache_array = NULL;

	private static function getConstants() {
		if (self::$const_cache_array == NULL) {
			self::$const_cache_array = [];
		}
		$called_class = get_called_class();
		if (!array_key_exists($called_class, self::$const_cache_array)) {
			$reflect = new ReflectionClass($called_class);
			self::$const_cache_array[$called_class] = $reflect->getConstants();
		}
		return self::$const_cache_array[$called_class];
	}

	public static function isValidName($name, $strict = false) {
		$constants = self::getConstants();

		if ($strict) {
			return array_key_exists($name, $constants);
		}

		$keys = array_map('strtolower', array_keys($constants));
		return in_array(strtolower($name), $keys);
	}

	public static function isValidValue($value, $strict = true) {
		$values = array_values(self::getConstants());
		return in_array($value, $values, $strict);
	}
}
