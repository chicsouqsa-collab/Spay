<?php

/**
 * Options API registration class.
 *
 * @package StellarPay
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\RestApi;

use StellarPay\AdminDashboard\Repositories\OptionsRepository;
use StellarPay\AdminDashboard\Exceptions\InvalidSettingValueException;
use StellarPay\RestApi\Endpoints\ApiRoute;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class OptionsAPI
 */
class Options extends ApiRoute
{
    /**
     * @since 1.0.0
     */
    protected string $endpoint = 'options';

    /**
     * Options repository.
     *
     * @var OptionsRepository
     */
    private OptionsRepository $optionsRepository;

    /**
     * Constructor.
     */
    public function __construct(OptionsRepository $optionsRepository)
    {
        parent::__construct();

        $this->optionsRepository = $optionsRepository;
    }

    /**
     * This function returns an array of route arguments.
     *
     * @since 1.0.0
     */
    public function getRoutes(): array
    {
        return [
            'get' => [
                'method' => WP_REST_Server::READABLE,
                'callback' => 'getOption',
            ],
            'set' => [
                'method' => WP_REST_Server::CREATABLE,
                'callback' => 'setOption',
            ],
            'delete' => [
                'method' => WP_REST_Server::CREATABLE,
                'callback' => 'deleteOption',
            ],
        ];
    }

    /**
     * Register REST routes.
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        $routes = $this->getRoutes();

        foreach ($routes as $route => $details) {
            register_rest_route(
                $this->getNamespace(),
                $this->getEndpoint($route),
                [
                    'methods' => $details['method'],
                    'callback' => [$this, $details['callback']],
                    'permission_callback' => [$this, 'permissionCheck'],
                    'args' => [],
                ]
            );
        }
    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        return parent::permissionCheck($request) &&
            current_user_can('manage_options');
    }

    /**
     * Get option or options.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @since 1.0.0
     * @return WP_Error|WP_REST_Response
     */
    public function getOption(WP_REST_Request $request)
    {
        $key = $request->get_param('key');

        if (!empty($key)) {
            if ($this->optionsRepository->has($key)) {
                $result = $this->optionsRepository->get($key);
            } else {
                return new WP_Error('option_error', esc_html__('Invalid or expired option name.', 'stellarpay'));
            }
        } else {
            $result = $this->optionsRepository->get();
        }

        return new WP_REST_Response($result, 200);
    }

    /**
     * Set an option or options.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @since 1.0.0
     * @return WP_Error|WP_REST_Response
     */
    public function setOption(WP_REST_Request $request)
    {
        $settings = $request->get_param('options');

        if (!empty($settings) && is_array($settings)) {
            // Validate fields.
            foreach ($settings as $key => $value) {
                if (!$this->shouldProcessSetting($key, $settings)) {
                    continue;
                }

                $error = $this->validateField($key, $value);

                if (is_wp_error($error)) {
                    return $error;
                }

                // Save settings.
                $this->optionsRepository->set($key, $value);
            }
        } else {
            return new WP_Error('settings_error', esc_html__('No settings provided.', 'stellarpay'));
        }

        return new WP_REST_Response(
            [
                'message' => esc_html__('Settings updated.', 'stellarpay'),
            ],
            200
        );
    }

    /**
     * Delete option
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function deleteOption(WP_REST_Request $request)
    {
        $key = $request->get_param('key');

        if (!empty($key)) {
            if ($this->optionsRepository->has($key)) {
                $this->optionsRepository->delete($key);
            } else {
                return new WP_Error('option_error', esc_html__('Invalid or expired option name.', 'stellarpay'));
            }
        } else {
            return new WP_Error('option_error', esc_html__('No option key is provided.', 'stellarpay'));
        }

        return new WP_REST_Response(
            [
                'message' => esc_html__('Setting deleted.', 'stellarpay'),
            ],
            200
        );
    }

    /**
     * Validate field.
     *
     * @return WP_Error|bool
     */
    private function validateField(string $key, $value)
    {
        try {
            switch ($key) {
                case OptionsRepository::OPTION_NAME_STRIPE_STATEMENT_DESCRIPTOR:
                    $this->validateStatementDescriptor($value);
                    break;

                case OptionsRepository::PERCENTAGE_FEE_RECOVERY:
                    $this->validateNumber($value, esc_html__('Percentage-based processing fee', 'stellarpay'));
                    break;

                case OptionsRepository::FLAT_AMOUNT_FEE_RECOVERY:
                    $this->validateNumber($value, esc_html__('Flat amount processing fee', 'stellarpay'));
                    break;

                default:
                    break;
            }
        } catch (InvalidSettingValueException $e) {
            return new WP_Error('settings_error', $e->getMessage());
        }

        return true;
    }

    /**
     * Validate statement descriptor.
     *
     * Note: Same validation rules are also added to the client side (AppearanceSettings),
     * changes here should be reflected there as well.
     *
     * @throws InvalidSettingValueException
     */
    private function validateStatementDescriptor(string $value): void
    {
        //  Check if the string contains between 5 and 22 characters.
        if (strlen($value) < 5 || strlen($value) > 22) {
            throw new InvalidSettingValueException(esc_html__('Statement descriptor must be between 5 and 22 characters.', 'stellarpay'));
        }

        // Contains only Latin characters (\u0000-\u007f).
        if (!preg_match('/^[\x00-\x7F]*$/', $value)) {
            throw new InvalidSettingValueException(esc_html__('Statement descriptor must contain only Latin characters.', 'stellarpay'));
        }

        // Contains at least one letter.
        if (!preg_match('/[a-zA-Z]/', $value)) {
            throw new InvalidSettingValueException(esc_html__('Statement descriptor must contain at least one letter.', 'stellarpay'));
        }

        // Doesn’t contain any of the following special characters: < > \ ' " * ＊.
        if (preg_match('/[<>\\\\\'"\*＊]/', $value)) { // four backslashes are necessary to escape the backslash in the regex.
            throw new InvalidSettingValueException(esc_html__('Statement descriptor must not contain any of the following special characters: < > \ \' " *.', 'stellarpay'));
        }
    }

    /**
     * Validate number.
     *
     * @throws InvalidSettingValueException
     */
    private function validateNumber(string $value, string $field): void
    {
        if (empty($value)) {
            return;
        }

        $pattern = '/^[0-9]*[.]?[0-9]*$/';
        if (!preg_match($pattern, $value)) {
            throw new InvalidSettingValueException(
                sprintf(
                    // translators: %s - the field name
                    esc_html__('%s must be a value with one decimal point, using a period (.) as the decimal separator.', 'stellarpay'),
                    esc_html($field)
                )
            );
        }

        if (!is_numeric($value)) {
            throw new InvalidSettingValueException(
                sprintf(
                    // translators: %s - the field name
                    esc_html__('%s must be a valid number.', 'stellarpay'),
                    esc_html($field)
                )
            );
        }
    }

    /**
     * Determine if the setting should be processed.
     */
    private function shouldProcessSetting(string $key, array $settings): bool
    {
        if (OptionsRepository::OPTION_NAME_STRIPE_STATEMENT_DESCRIPTOR === $key) {
            return $settings['stripe-payment-statement-descriptor-enabled'] ?? false;
        }

        return true;
    }
}
