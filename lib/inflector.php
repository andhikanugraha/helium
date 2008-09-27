<?php

// Helium framework
// class Inflector
// class I extends Inflector
// (called statically)
// forked from Ruby on Rails' Inflections

class Inflector {
    public static $plurals = array();
    protected static $singulars = array();
    protected static $uncountables = array();

    public static function plural($pattern, $replace) {
        self::$plurals[$pattern] = $replace;
    }

    public static function singular($pattern, $replace) {
        self::$singulars[$pattern] = $replace;
    }

    public static function irregular($singular, $plural) {
        self::plural('/' . $singular . '/', $plural);
        self::singular('/' . $plural . '/', $singular);
    }

    public static function uncountable($list) {
        $list = explode(' ', $list);
        foreach ($list as $word) {
            self::plural('/' . $word . '/', $word);
            self::singular('/' . $word . '/', $word);
            self::$uncountables[] = $word;
        }
    }

    public static function pluralize($singular) {
        if (in_array($singular, self::$uncountables))
            return $singular;

        foreach (array_reverse(self::$plurals) as $p => $r) {
            $plural = preg_replace($p, $r, $singular);
            if ($plural != $singular)
                return $plural;
        }
    }

    public static function singularize($plural) {
        if (in_array($plural, self::$uncountables))
            return $plural;

        foreach (array_reverse(self::$singulars) as $p => $r) {
            $singular = preg_replace($p, $r, $plural);
            if ($singular != $plural)
                return $plural;
        }
    }

    public static function camelize($lower_case_and_underscored_word, $first_letter_in_uppercase = true) {
        $boom = explode('_', strtolower($lower_case_and_underscored_word));
        $return = '';

        if (!$first_letter_in_uppercase)
            $return = array_shift($boom);

        foreach ($boom as $word) {
            $word{0} = strtoupper($word{0});
            $return .= $word;
        }

		return $return;
    }

    public static function underscore($camel_cased_word) {
        $return = preg_replace(array("/([A-Z]+)([A-Z][a-z])/", "/([a-z\d])([A-Z])/"), '\1_\2', $camel_cased_word);
        $return = str_replace('-', '_', $return);
        $return = strtolower($return);
        return $return;
    }

    public static function dasherize($underscored_word) {
        return str_replace('_', '-', $underscored_word);
    }

    public static function humanize($lower_case_and_underscored_word) {
        return preg_replace(array("/_id$/", "/_/"), array('', ' '), $lower_case_and_underscored_word);
    }

    public static function tableize($class_name) {
        return self::pluralize(self::underscore($class_name));
    }

    public static function classify($table_name) {
        return self::camelize(self::singularize(preg_replace("/.*\./", '', $table_name)));
    }

    public static function ordinalize($number) {
        $digits = (string) $number;
        $int = (int) $number;

        if ($int > 11 && $int < 13)
            $suffix = 'th';
        else {
            $last = $digits{strlen($digits) - 1};
            switch ($last) {
            case 1:
                $suffix = 'st';
                break;
            case 2:
                $suffix = 'nd';
                break;
            case 3:
                $suffix = 'rd';
                break;
            default:
                $suffix = 'th';
            }
        }

        return $digits . $suffix;
    }
}

final class I extends Inflector {}

Inflector::plural("/$/", 's');
Inflector::plural("/s$/i", 's');
Inflector::plural("/(ax|test)is$/i", '\1es');
Inflector::plural("/(octop|vir)us$/i", '\1i');
Inflector::plural("/(alias|status)$/i", '\1es');
Inflector::plural("/(bu)s$/i", '\1ses');
Inflector::plural("/(buffal|tomat)o$/i", '\1oes');
Inflector::plural("/([ti])um$/i", '\1a');
Inflector::plural("/sis$/i", 'ses');
Inflector::plural("/(?:([^f])fe|([lr])f)$/i", '\1\2ves');
Inflector::plural("/(hive)$/i", '\1s');
Inflector::plural("/([^aeiouy]|qu)y$/i", '\1ies');
Inflector::plural("/(x|ch|ss|sh)$/i", '\1es');
Inflector::plural("/(matr|vert|ind)ix|ex$/i", '\1ices');
Inflector::plural("/([m|l])ouse$/i", '\1ice');
Inflector::plural("/^(ox)$/i", '\1en');
Inflector::plural("/(quiz)$/i", '\1zes');

Inflector::singular("/s$/i", '');
Inflector::singular("/(n)ews$/i", '\1ews');
Inflector::singular("/([ti])a$/i", '\1um');
Inflector::singular("/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i", '\1\2sis');
Inflector::singular("/(^analy)ses$/i", '\1sis');
Inflector::singular("/([^f])ves$/i", '\1fe');
Inflector::singular("/(hive)s$/i", '\1');
Inflector::singular("/(tive)s$/i", '\1');
Inflector::singular("/([lr])ves$/i", '\1f');
Inflector::singular("/([^aeiouy]|qu)ies$/i", '\1y');
Inflector::singular("/(s)eries$/i", '\1eries');
Inflector::singular("/(m)ovies$/i", '\1ovie');
Inflector::singular("/(x|ch|ss|sh)es$/i", '\1');
Inflector::singular("/([m|l])ice$/i", '\1ouse');
Inflector::singular("/(bus)es$/i", '\1');
Inflector::singular("/(o)es$/i", '\1');
Inflector::singular("/(shoe)s$/i", '\1');
Inflector::singular("/(cris|ax|test)es$/i", '\1is');
Inflector::singular("/(octop|vir)i$/i", '\1us');
Inflector::singular("/(alias|status)es$/i", '\1');
Inflector::singular("/^(ox)en/i", '\1');
Inflector::singular("/(vert|ind)ices$/i", '\1ex');
Inflector::singular("/(matr)ices$/i", '\1ix');
Inflector::singular("/(quiz)zes$/i", '\1');

Inflector::irregular('person', 'people');
Inflector::irregular('man', 'men');
Inflector::irregular('child', 'children');
Inflector::irregular('sex', 'sexes');
Inflector::irregular('move', 'moves');

Inflector::uncountable('equipment information rice money species series fish sheep');
