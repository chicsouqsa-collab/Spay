<?php

/**
 * This class registers rest api routes and processes the webhook payload in test or live the Stripe mode.
 *
 * @package StellarPay\PaymentGateways\Stripe\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\RestApi;

use Exception;
use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\WebhookRepository;
use StellarPay\PaymentGateways\Stripe\Traits\StripeClientHelpers;
use StellarPay\PaymentGateways\Stripe\Webhook\WebhookRegisterer;
use StellarPay\PaymentGateways\Stripe\Webhook\WebhookSignatureValidator;
use StellarPay\RestApi\Endpoints\ApiRoute;
use StellarPay\RestApi\HandleMultipleApiRoutes;
use StellarPay\Webhook\Models\WebhookEvent;
use StellarPay\Webhook\Repositories\WebhookEventsRepository;
use StellarPay\Core\ShutdownScheduler;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use WP_REST_Request;
use WP_REST_Server;

use function StellarPay\Core\container;

/**
 * Class Webhook
 *
 * @since 1.1.0 implement StripeClientHelpers trait
 * @since 1.0.0
 */
class Webhook extends ApiRoute
{
    use StripeClientHelpers;
    use HandleMultipleApiRoutes {
        registerAllRoutes as register;
    }

    /**
     * This property used to check the mode of webhook.
     *
     * We can use this property in even processor class to ensure webhook mode.
     *
     * @since 1.0.0
     */
    public static PaymentGatewayMode $paymentGatewayMode;

    /**
     * @var string
     */
    protected string $namespace = Constants::PLUGIN_SLUG;

    /**
     * @since 1.0.0
     */
    protected string $endpoint = 'stripe/webhook';

    /**
     * @since 1.1.0
     */
    protected WebhookEventsRepository $webhookEventsRepository;

    /**
     * @since 1.0.0
     */
    private WebhookSignatureValidator $webhookValidator;

    /**
     * @since 1.0.0
     */
    private EventDTO $webhookEvent;

    /**
     * @since 1.0.0
     */
    private WebhookRegisterer $webhookRegisterer;

    /**
     * @since 1.0.0
     */
    private WebhookRepository $webhookRepository;

    /**
     * @since 1.0.0
     */
    private SettingRepository $settingRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(
        WebhookSignatureValidator $webhookValidator,
        WebhookRegisterer $webhookRegisterer,
        WebhookRepository $webhookRepository,
        SettingRepository $settingRepository,
        WebhookEventsRepository $webhookEventsRepository
    ) {
        parent::__construct();

        $this->webhookValidator = $webhookValidator;
        $this->webhookRegisterer = $webhookRegisterer;
        $this->webhookRepository = $webhookRepository;
        $this->settingRepository = $settingRepository;
        $this->webhookEventsRepository = $webhookEventsRepository;
    }

    /**
     * This function returns an array of route arguments.
     *
     * @since 1.0.0
     */
    public function getRoutes(): array
    {
        return [
            'test' => [
                'method' => WP_REST_Server::CREATABLE,
                'callback' => 'processWebhook',
            ],
            'live' => [
                'method' => WP_REST_Server::CREATABLE,
                'callback' => 'processWebhook',
            ],
        ];
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        $requestBody = $request->get_body();
        $stripeSignature = current($request->get_headers()['stripe_signature']);

        $invokable = $this->webhookValidator;
        $this->webhookEvent = $invokable(
            $stripeSignature,
            $this->getWebhookSecretKey($request),
            $requestBody
        );

        return true;
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException|InvalidPropertyException|\StellarPay\Core\Exceptions\Primitives\Exception
     */
    public function processWebhook(WP_REST_Request $request): void
    {
        self::$paymentGatewayMode = $this->getPaymentGatewayMode($request);

        $this->setStripeClientWithServices(self::$paymentGatewayMode);

        $eventName = $this->webhookEvent->getType();
        $registeredEvents = $this->webhookRegisterer->getEvents();
        $hasEventProcessors = array_key_exists($this->webhookEvent->getType(), $registeredEvents);

        if ($hasEventProcessors) {
            container(ShutdownScheduler::class)->registerShutdownJob([$this, 'handleRestApiError']);

            try {
                $this->onWebhookReceived($this->webhookEvent);

                // Run each event process register for event.
                foreach ($registeredEvents[$eventName] as $eventProcessorClassName) {
                    $eventProcessor = container($eventProcessorClassName);
                    ($eventProcessor)($this->webhookEvent);
                }

                $this->afterWebhookProcessed();
            } catch (Exception $e) {
                $errorDetails = [
                    'Error' => $e->getMessage(),
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                ];

                $this->onWebhookProcessingError(WebhookEventRequestStatus::FAILED(), $errorDetails);
                echo wp_json_encode($errorDetails);
            }
        }
    }

    /**
     * @since 1.0.0
     */
    public function getEndpointByMode(PaymentGatewayMode $paymentGatewayMode): string
    {
        return $this->getRestApiUrl()[$paymentGatewayMode->getId()];
    }

    /**
     * @since 1.0.0
     */
    private function getPaymentGatewayMode(WP_REST_Request $request): PaymentGatewayMode
    {
        return false !== strpos($request->get_route(), "{$this->endpoint}/live")
            ? PaymentGatewayMode::live()
            : PaymentGatewayMode::test();
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     */
    private function getWebhookSecretKey(WP_REST_Request $request): string
    {
        $mode = $this->getPaymentGatewayMode($request);

        // If test mode is active and the local environment is active, return the local webhook secret key.
        // Local webhook secret key is used for testing webhook locally with the Stripe CLI.
        // Otherwise, return the webhook secret key from the database.
        if (
            $this->settingRepository->isTestModeActive()
            && $this->settingRepository->isLocalWebhookSecretKeyEnabled()
            && ( $webhookSecretKey = $this->settingRepository->getLocalWebhookSecretKey() )
        ) {
            return $webhookSecretKey;
        }

        return $this->webhookRepository->getSecretKey($mode);
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function handleRestApiError(): void
    {
        $error = error_get_last();

        if (! $error) {
            return;
        }

        $errorDetails = [
            'Error' => $error['message'],
            'File' => $error['file'],
            'Line' => $error['line'],
        ];

        $this->onWebhookProcessingError(WebhookEventRequestStatus::ERROR(), $errorDetails);
        echo wp_json_encode($errorDetails);
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    private function onWebhookReceived(EventDTO $webhookEvent): void
    {
        $webhookEvent = WebhookEventsRepository::createWebhookeventFromEventDTO($webhookEvent);
        $webhookEvent->save();
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    private function afterWebhookProcessed(): void
    {
        $webhookEventModel = WebhookEvent::findByEventId($this->webhookEvent->getId());

        $dateResponse = Temporal::getCurrentDateTime();
        $webhookEventModel->responseTime = $dateResponse;
        $webhookEventModel->responseTimeGmt = Temporal::getGMTDateTimeWithMilliseconds($dateResponse);

        $webhookEventModel->save();
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    private function onWebhookProcessingError(
        WebhookEventRequestStatus $webhookEventRequestStatus,
        array $errorDetails
    ): void {
        $dateResponse = Temporal::getCurrentDateTime();

        $event = WebhookEvent::findByEventId($this->webhookEvent->getId());

        $event->requestStatus = $webhookEventRequestStatus;
        $event->notes = array_merge($event->notes, ['errorDetails' => $errorDetails]);
        $event->responseTime = $dateResponse;
        $event->responseTimeGmt = Temporal::getGMTDateTimeWithMilliseconds($dateResponse);

        $event->save();
    }
}
