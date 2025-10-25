<?php

/**
 * StellarPay
 *
 * @package           StellarPay
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name:          StellarPay - Stripe Payment Gateway for WooCommerce
 * Plugin URI:           https://links.stellarwp.com/stellarpay
 * Description:          The ultimate Stripe payment gateway for WooCommerce, designed to help you accept payments seamlessly.
 * Version:              1.9.1
 * Requires at least:    6.2
 * Requires PHP:         7.4
 * WC requires at least: 8.0
 * WC tested up to:      9.9.5
 * Author:               StellarWP
 * Author URI:           https://links.stellarwp.com/stellarwp
 * Text Domain:          stellarpay
 * Domain Path:          /languages
 * License:              GPL v2 or later
 * License URI:          http://www.gnu.org/licenses/gpl-2.0.txt
 */

declare(strict_types=1);

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

use StellarPay\PluginSetup\Plugin;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Start StellarPay
 *
 * The main function responsible for returning the one true StellarPay instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $stellarPay = stellarPay(); ?>
 *
 * @since 1.0.0
 */
function stellarPay(): Plugin
{
    static $instance = null;

    if (null === $instance) {
        $instance = new Plugin();
    }

    return $instance;
}

// StellarPay Autoloader.
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/vendor-prefixed/autoload.php';
require __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

// Boot the plugin.
stellarPay()->boot();
