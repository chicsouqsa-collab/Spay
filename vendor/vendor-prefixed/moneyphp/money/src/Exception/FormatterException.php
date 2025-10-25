<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Money\Exception;

use StellarPay\Vendors\Money\Exception;

/**
 * Thrown when a Money object cannot be formatted into a string.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class FormatterException extends \RuntimeException implements Exception
{
}
