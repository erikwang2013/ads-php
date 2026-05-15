<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */


/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return array_replace_recursive(require __DIR__.'/en.php', [
    'weekdays' => ['𑄢𑄧𑄝𑄨𑄝𑄢𑄴', '𑄥𑄧𑄟𑄴𑄝𑄢𑄴', '𑄟𑄧𑄁𑄉𑄧𑄣𑄴𑄝𑄢𑄴', '𑄝𑄪𑄖𑄴𑄝𑄢𑄴', '𑄝𑄳𑄢𑄨𑄥𑄪𑄛𑄴𑄝𑄢𑄴', '𑄥𑄪𑄇𑄴𑄇𑄮𑄢𑄴𑄝𑄢𑄴', '𑄥𑄧𑄚𑄨𑄝𑄢𑄴'],
    'weekdays_short' => ['𑄢𑄧𑄝𑄨', '𑄥𑄧𑄟𑄴', '𑄟𑄧𑄁𑄉𑄧𑄣𑄴', '𑄝𑄪𑄖𑄴', '𑄝𑄳𑄢𑄨𑄥𑄪𑄛𑄴', '𑄥𑄪𑄇𑄴𑄇𑄮𑄢𑄴', '𑄥𑄧𑄚𑄨'],
    'weekdays_min' => ['𑄢𑄧𑄝𑄨', '𑄥𑄧𑄟𑄴', '𑄟𑄧𑄁𑄉𑄧𑄣𑄴', '𑄝𑄪𑄖𑄴', '𑄝𑄳𑄢𑄨𑄥𑄪𑄛𑄴', '𑄥𑄪𑄇𑄴𑄇𑄮𑄢𑄴', '𑄥𑄧𑄚𑄨'],
    'months' => ['𑄎𑄚𑄪𑄠𑄢𑄨', '𑄜𑄬𑄛𑄴𑄝𑄳𑄢𑄪𑄠𑄢𑄨', '𑄟𑄢𑄴𑄌𑄧', '𑄃𑄬𑄛𑄳𑄢𑄨𑄣𑄴', '𑄟𑄬', '𑄎𑄪𑄚𑄴', '𑄎𑄪𑄣𑄭', '𑄃𑄉𑄧𑄌𑄴𑄑𑄴', '𑄥𑄬𑄛𑄴𑄑𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄃𑄧𑄇𑄴𑄑𑄬𑄝𑄧𑄢𑄴', '𑄚𑄧𑄞𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄓𑄨𑄥𑄬𑄟𑄴𑄝𑄧𑄢𑄴'],
    'months_short' => ['𑄎𑄚𑄪', '𑄜𑄬𑄛𑄴', '𑄟𑄢𑄴𑄌𑄧', '𑄃𑄬𑄛𑄳𑄢𑄨𑄣𑄴', '𑄟𑄬', '𑄎𑄪𑄚𑄴', '𑄎𑄪𑄣𑄭', '𑄃𑄉𑄧𑄌𑄴𑄑𑄴', '𑄥𑄬𑄛𑄴𑄑𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄃𑄧𑄇𑄴𑄑𑄮𑄝𑄧𑄢𑄴', '𑄚𑄧𑄞𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄓𑄨𑄥𑄬𑄟𑄴𑄝𑄢𑄴'],
    'months_short_standalone' => ['𑄎𑄚𑄪𑄠𑄢𑄨', '𑄜𑄬𑄛𑄴𑄝𑄳𑄢𑄪𑄠𑄢𑄨', '𑄟𑄢𑄴𑄌𑄧', '𑄃𑄬𑄛𑄳𑄢𑄨𑄣𑄴', '𑄟𑄬', '𑄎𑄪𑄚𑄴', '𑄎𑄪𑄣𑄭', '𑄃𑄉𑄧𑄌𑄴𑄑𑄴', '𑄥𑄬𑄛𑄴𑄑𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄃𑄧𑄇𑄴𑄑𑄮𑄝𑄧𑄢𑄴', '𑄚𑄧𑄞𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄓𑄨𑄥𑄬𑄟𑄴𑄝𑄧𑄢𑄴'],
    'formats' => [
        'LT' => 'h:mm a',
        'LTS' => 'h:mm:ss a',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM, YYYY',
        'LLL' => 'D MMMM, YYYY h:mm a',
        'LLLL' => 'dddd, D MMMM, YYYY h:mm a',
    ],
    'first_day_of_week' => 0,
]);
