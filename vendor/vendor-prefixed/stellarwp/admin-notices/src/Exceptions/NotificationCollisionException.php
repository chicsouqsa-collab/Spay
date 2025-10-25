<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);


namespace StellarPay\Vendors\StellarWP\AdminNotices\Exceptions;

use RuntimeException;
use StellarPay\Vendors\StellarWP\AdminNotices\AdminNotice;

class NotificationCollisionException extends RuntimeException
{
    protected $notificationId;

    protected $notification;

    public function __construct(string $notificationId, AdminNotice $notification)
    {
        $this->notificationId = $notificationId;
        $this->notification = $notification;

        parent::__construct("Notification with ID $notificationId already exists.");
    }
}
