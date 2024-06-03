<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin;

use MultipleChain\Utils\Math;
use MultipleChain\Utils as BaseUtils;

class Utils extends BaseUtils
{
    /**
     * @param int|string $value
     * @return string
     */
    public static function fromSatoshi(int|string $value): string
    {
        return rtrim(bcdiv((string) $value, '100000000', 8), '0');
    }

    /**
     * @param float $value
     * @return int
     */
    public static function toSatoshi(float $value): int
    {
        return (int) bcmul(self::toString($value), '100000000', 8);
    }
}
