<?php

namespace Natalnet\Relex;

class Types
{
    const NUMBER_TYPE = 1;
    const STRING_TYPE = 2;
    const BOOLEAN_TYPE = 3;

    /**
     * @param int $type
     * @return string
     */
    public static function getTypeName($type)
    {
        switch ($type) {
            case self::NUMBER_TYPE:
                return 'number';
            case self::STRING_TYPE:
                return 'string';
            case self::BOOLEAN_TYPE:
                return 'boolean';
            default:
                return '';
        }
    }
}
