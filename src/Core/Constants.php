<?php

/**
 * Constants
 *
 * This class is used to manage the application constants.
 *
 * @package StellarPay\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core;

/**
 * Class Constants
 *
 * @since 1.0.0
 */
class Constants
{
    /**
     * @var string
     * @since 1.0.0
     */
    public const TEXT_DOMAIN = 'stellarpay';

    /**
     * @var string
     * @since 1.0.0
     */
    public const VERSION = '1.9.1';

    /**
     * @var string
     * @since 1.0.0
     */
    public const PLUGIN_SLUG = 'stellarpay';

    /**
     * @var string
     * @since 1.0.0
     */
    public const NONCE_NAME = 'stellarpay-nonce';

    /**
     * @since 1.0.0
     */
    public static ?string $PLUGIN_URL;

    /**
     * @since 1.0.0
     */
    public static ?string $PLUGIN_DIR;

    /**
     * @since 1.0.0
     */
    public static ?string $PLUGIN_ROOT_FILE_RELATIVE_PATH;

    /**
     * @var string
     * @since 1.0.0
     */
    public static ?string $PLUGIN_ROOT_FILE;

    /**
     * Constants constructor.
     * @since 1.0.0
     */
    public function __construct()
    {
        $pluginFile = sprintf(
            dirname(__DIR__, 2) . '/%1$s.php',
            self::PLUGIN_SLUG
        );

        self::$PLUGIN_URL = untrailingslashit(plugins_url('', $pluginFile));
        self::$PLUGIN_DIR = untrailingslashit(plugin_dir_path($pluginFile));
        self::$PLUGIN_ROOT_FILE_RELATIVE_PATH = plugin_basename($pluginFile);
        self::$PLUGIN_ROOT_FILE = $pluginFile;
    }

    /**
     * @since 1.0.0
     */
    public static function slugPrefixed(string $name): string
    {
        return self::PLUGIN_SLUG . $name;
    }
}
