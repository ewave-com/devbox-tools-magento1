<?php
/**
 * @author    Ewave <https://ewave.com/>
 * @copyright 2018-2019 NASKO TRADING PTY LTD
 * @license   https://ewave.com/wp-content/uploads/2018/07/eWave-End-User-License-Agreement.pdf BSD Licence
 */

namespace MagentoOneDevBox\Command\Options;

use CoreDevBoxScripts\Command\Options\AbstractOptions;

/**
 * Container for Redis options
 */
class Redis extends AbstractOptions
{
    const CACHE_SETUP = 'redis-cache-setup';
    const SESSION_SETUP = 'redis-session-setup';
    const REDIS_CLASS_NAME = 'redis-tmp-path';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::CACHE_SETUP => [
                'boolean' => true,
                'default' => static::getDefaultValue('USE_REDIS_CACHE', true),
                'description' => 'Whether to use Redis as Magento default cache.',
                'question' => 'Do you want to use Redis as Magento default cache? %default%'
            ],
            static::SESSION_SETUP => [
                'boolean' => true,
                'default' => static::getDefaultValue('USE_REDIS_SESSIONS', true),
                'description' => 'Whether to use Redis for storing sessions.',
                'question' => 'Do you want to use Redis for storing sessions? %default%'
            ],
            static::REDIS_CLASS_NAME => [
                'default' => 'Cm_RedisSession_Model_Session',
                'description' => 'Redis session class name',
                'question' => 'Redis session class name. %default%'
            ]
        ];
    }
}
