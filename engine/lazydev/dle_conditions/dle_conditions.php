<?php
/**
 * DLE Conditions Lite
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

class dleConditions
{
    private static $stringLength = [];
    private static $instance = null;
    private static $row;

    public static function construct()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function realize($template, $row)
    {
        self::$row = $row;
        if (strpos($template, '[if ') !== false) {
            $template = preg_replace_callback("#\\[if (.+?)\\](.*?)\\[/if\\]#umis",
                [self::$instance, 'conditions'],
            $template);
        }

        return $template;
    }

    public static function conditions($pregArray)
    {
        $checkIf = self::conditionsMatching($pregArray[1], $pregArray[2]);

        if ($checkIf !== false) {
            return $checkIf;
        }

        if (strpos($pregArray[0], '[elif') !== false) {
            preg_match_all("#\\[elif (.+?)\\](.+?)\\[/elif\\]#umis", $pregArray[0], $pregElif);
            for ($i = 0; $i < count($pregElif); $i++) {
                $checkElif = self::conditionsMatching($pregElif[1][$i], $pregElif[2][$i]);
                if ($checkElif !== false) {
                    return $checkElif;
                }
            }
        }

        if (strpos($pregArray[0], '[else') !== false) {
            preg_match_all("#\\[else\\](.+?)\\[/if\\]#umis", $pregArray[0], $pregElse);
            return $pregElse[1][0];
        }

        return '';
    }

    public static function conditionsMatching($condition, $return)
    {
        preg_match("#(.+?)(>=|<=|<|>|!==|!=|==|=|!~|~)(.+?)$#uis", $condition, $conditionMatching);

        if (!$conditionMatching) {
            $conditionMatching[1] = $condition;
            $conditionMatching[2] = false;
            if (dle_strpos($conditionMatching[1], '!', 'UTF-8') === 0) {
                $conditionMatching[1] = str_replace('!', '', $conditionMatching[1]);
                $conditionMatching[3] = true;
            }
        }

        self::$stringLength = [];

        if (dle_strpos($conditionMatching[1], 'xfvalue', 'UTF-8') !== false) {
            $xfields = xfieldsdataload(self::$row['xfields']);
            $conditionMatching[1] = str_replace('xfvalue_', '', $conditionMatching[1]);
            $conditionMatching[1] = stripslashes($xfields[$conditionMatching[1]]);
        }

        if (!$conditionMatching[2]) {
            if ($conditionMatching[3]) {
                return empty($conditionMatching[1]) ? $return : false;
            }
            return empty($conditionMatching[1]) ? false : $return;
        }

        if (dle_strpos($conditionMatching[3], 'xfvalue', 'UTF-8') !== false) {
            $xfields = xfieldsdataload(self::$row['xfields']);
            $conditionMatching[3] = str_replace('xfvalue_', '', $conditionMatching[3]);
            $conditionMatching[3] = stripslashes($xfields[$conditionMatching[3]]);
        }

        $conditionMatching[1] = self::returnType($conditionMatching[1]);
        $conditionMatching[3] = self::returnType($conditionMatching[3]);

        switch ($conditionMatching[2]) {
            case '>':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                $bool = $conditionMatching[1] > $conditionMatching[3];
                break;
            case '>=':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                $bool = $conditionMatching[1] >= $conditionMatching[3];
                break;
            case '<':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                $bool = $conditionMatching[1] < $conditionMatching[3];
                break;
            case '<=':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                $bool = $conditionMatching[1] <= $conditionMatching[3];
                break;
            case '==':
            case '!==':
                $conditionMatching[1] = explode(',', $conditionMatching[1]);
                $conditionMatching[3] = explode(',', $conditionMatching[3]);
                $countMatch = 0;
                foreach ($conditionMatching[3] as $valMatch) {
                    if (in_array($valMatch, $conditionMatching[1])) {
                        $countMatch++;
                    }
                }
                if ($conditionMatching[2] == '==') {
                    $bool = $countMatch == count($conditionMatching[3]);
                } else {
                    $bool = $countMatch == count($conditionMatching[3]) ? false : true;
                }
                break;
            case '=':
                $bool = $conditionMatching[1] == $conditionMatching[3];
                break;
            case '!=':
                $bool = $conditionMatching[1] != $conditionMatching[3];
                break;
            case '~':
                $bool = dle_strpos($conditionMatching[1], $conditionMatching[3], 'UTF-8') === false ? false : true;
                break;
            case '!~':
                $bool = dle_strpos($conditionMatching[1], $conditionMatching[3], 'UTF-8') === false ? true : false;
                break;
        }

        return $bool === true ? true : false;
    }

    public static function returnType($var)
    {
        if (is_numeric($var)) {
            if (is_int($var)) {
                $var = intval($var);
            } else {
                $var = floatval($var);
            }
        } elseif (is_string($var)) {
            $var = trim($var);
            self::$stringLength[] = mb_strlen($var, 'UTF-8');
        }

        return $var;
    }

    private function __construct() {}
    private function __wakeup() {}
    private function __clone() {}
    private function __sleep() {}
}
