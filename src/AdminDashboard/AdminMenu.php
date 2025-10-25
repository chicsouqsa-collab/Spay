<?php

/**
 * Admin Menu
 *
 * This file is responsible for setting up the admin menu.
 *
 * @package StellarPay\AdminDashboard
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard;

use StellarPay\AdminDashboard\DataTransferObjects\DashboardDTO;
use StellarPay\Core\Constants;
use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\Request;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository as StripeSettingRepository;
use StellarPay\PluginSetup\Environment;
use WP_Admin_Bar;

/**
 * Class AdminMenu
 *
 * @package StellarPay\AdminDashboard
 * @since 1.0.0
 */
class AdminMenu
{
    use WooCommercePaymentGatewayUtilities;

    /**
     * @since 1.0.0
     */
    private string $baseSlug = Constants::PLUGIN_SLUG;

    /**
     * @since 1.0.0
     */
    private AccountRepository $accountRepository;

    /**
     * @since 1.0.0
     */
    private DashboardDTO $dashboardDTO;

    /**
     * @since 1.0.0
     * @var StripeSettingRepository
     */
    private StripeSettingRepository $stripeSettingRepository;

    /**
     * @since 1.3.0
     */
    private Request $request;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(
        AccountRepository $accountRepository,
        DashboardDTO $dashboardDTO,
        StripeSettingRepository $stripeSettingRepository,
        Request $request
    ) {
        $this->accountRepository = $accountRepository;
        $this->dashboardDTO = $dashboardDTO;
        $this->stripeSettingRepository = $stripeSettingRepository;
        $this->request = $request;
    }

    /**
     * This function registers the admin menus.
     *
     * @since 1.1.0 Register "Status" menu item
     * @since 1.0.0
     */
    public function registerMenus(): void
    {

        $stellarPayIcon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQwIiBoZWlnaHQ9IjI0MCIgdmlld0JveD0iMCAwIDI0MCAyNDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0yMzQuNTcyIDg1LjU5OTdDMjM0LjU3MiA4NS41OTk3IDIzMy4zNjkgODYuNDAxNSAyMzMuMjgzIDg2LjQ2MzJDMTI3LjAxOSAxNjAuOTQgNjEuMzAzNCAyMjkuMzY0IDk3Ljc4MzQgMjM3LjA3N0M5OC40NjE4IDIzNy4yMjIgMTA0LjQ1NyAyMzguNjc0IDEwOS4zMjcgMjM5LjFDMTc1LjIyNCAyNDQuODczIDIzMy4zMjYgMTk2LjEzNCAyMzkuMTAyIDEzMC4yMzdDMjQwLjQ2MiAxMTQuNzI4IDIzOC43NjYgOTkuNjYyNSAyMzQuNTc1IDg1LjU5NjZMMjM0LjU3MiA4NS41OTk3WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTIyNS4wMTUgNjIuNjMwNEMyMjUuMDE1IDYyLjYzMDQgMjI1LjAxNSA2Mi42Mjc0IDIyNS4wMTUgNjIuNjI0M0MyMDYuNDY1IDI4LjU1NTkgMTcxLjc3NiA0LjEwMzMyIDEzMC4yMzYgMC40NjQyNjJDNjQuMzM4IC01LjMwODg4IDYuMjM2NDQgNDMuNDI5OCAwLjQ2MzI5IDEwOS4zMjhDLTAuMzM4NTM2IDExOC40NjggLTAuMDg1NjUyOCAxMjcuNDU1IDEuMTE0IDEzNi4xNzNDMS40NDA5IDEzOC41NDIgMS44MzU2NSAxNDAuODg2IDIuMzAxMzIgMTQzLjIxMVYxNDMuMjE3QzIuMzUwNjYgMTQzLjQ3OSAyLjQwMzA5IDE0My43MzggMi40NTg2IDE0NEMyLjYxODk3IDE0NC43NzEgMi43ODU1IDE0NS41NDIgMi45NjEyOSAxNDYuMzFDMy4xNDAxNSAxNDcuMTAzIDMuMzI4MjggMTQ3Ljg4OSAzLjUyMjU2IDE0OC42NzZDMy43MTY4NSAxNDkuNDU2IDMuOTIwMzkgMTUwLjIzMyA0LjEzMDEgMTUxLjAxQzQuMzQ1OTggMTUxLjgxMiA0LjU3MTExIDE1Mi42MTEgNC44MDU0OSAxNTMuNDA2QzUuMDM2NzggMTU0LjE5OSA1LjI3NzMzIDE1NC45ODggNS41MjcxMyAxNTUuNzcyQzUuNzgzMSAxNTYuNTgzIDYuMDQ1MjMgMTU3LjM5MSA2LjMxOTcgMTU4LjE5M0M2LjU5MTA5IDE1OC45OTQgNi44NzE3MyAxNTkuNzkgNy4xNjE2MiAxNjAuNTg2QzcuNDYwNzYgMTYxLjQwOSA3Ljc2OTE2IDE2Mi4yMjYgOC4wODY4IDE2My4wNDFDOC40MDEzNyAxNjMuODU1IDguNzI1MTggMTY0LjY2MyA5LjA2NDQxIDE2NS40NjhDOS40MDY3MyAxNjYuMzA3IDkuNzU4MyAxNjcuMTMzIDEwLjEyNTMgMTY3Ljk1NkMxMC40ODYxIDE2OC43ODMgMTAuODU5MyAxNjkuNjAzIDExLjI0NzggMTcwLjQyQzExLjYzOTUgMTcxLjI2OSAxMi4wNDY2IDE3Mi4xMTEgMTIuNDY2IDE3Mi45NDlDMTIuODc5MyAxNzMuNzg4IDEzLjMwNzkgMTc0LjYyMSAxMy43NDU4IDE3NS40NUMxNC4xOTkyIDE3Ni4zMTQgMTQuNjY0OSAxNzcuMTc0IDE1LjE0MjkgMTc4LjAyNkMxNS42MTc4IDE3OC44ODMgMTYuMTA1MSAxNzkuNzMxIDE2LjYwNzcgMTgwLjU3M0MxNy4xMjI4IDE4MS40NTIgMTcuNjUzMiAxODIuMzI1IDE4LjE5MjkgMTgzLjE5MUMxOS40Nzg5IDE4NS4yNDUgMjAuODIzNSAxODcuMjU2IDIyLjIyNjcgMTg5LjIyNkMyNC42MTA2IDE4OC4xNzIgMjcuMDE5MSAxODcuMDkyIDI5LjQ0OTMgMTg1Ljk4NUMzMC40OTQ4IDE4NS41MSAzMS41NDY0IDE4NS4wMjkgMzIuNjAxMSAxODQuNTQ1QzMzLjYyMTkgMTg0LjA3MyAzNC42NDU3IDE4My41OTggMzUuNjcyNyAxODMuMTJDMzYuNzAyNyAxODIuNjM5IDM3LjczODkgMTgyLjE1NSAzOC43NzgyIDE4MS42NjVDMzkuNzgzNiAxODEuMTkgNDAuNzg5IDE4MC43MTIgNDEuODAwNSAxODAuMjMxQzQyLjgxODIgMTc5Ljc0NiA0My44MzI4IDE3OS4yNTYgNDQuODU2NyAxNzguNzYzQzQ1Ljg0OTcgMTc4LjI4NSA0Ni44NDI4IDE3Ny44MDMgNDcuODQxOSAxNzcuMzE5QzQ4Ljg0NDIgMTc2LjgyOSA0OS44NDM0IDE3Ni4zMzkgNTAuODUxOSAxNzUuODQyQzUxLjgyOTUgMTc1LjM2MSA1Mi44MTAyIDE3NC44NzcgNTMuNzk0IDE3NC4zODZDNTQuNzgzOSAxNzMuODk2IDU1Ljc3MzkgMTczLjQgNTYuNzY2OSAxNzIuOUg1Ni43N0M1Ny43MzgzIDE3Mi40MTYgNTguNzA2NyAxNzEuOTI1IDU5LjY3ODEgMTcxLjQzNUM2MC42NTI3IDE3MC45NDIgNjEuNjMwMyAxNzAuNDQyIDYyLjYxMSAxNjkuOTM5QzYzLjU2MzkgMTY5LjQ1MiA2NC41MTY4IDE2OC45NjIgNjUuNDc1OSAxNjguNDY1SDY1LjQ3OUM2Ni40NDQzIDE2Ny45NjkgNjcuNDA5NiAxNjcuNDY5IDY4LjM4MSAxNjYuOTZINjguMzg0MUM2OS4zMjc4IDE2Ni40NyA3MC4yNzE1IDE2NS45NzcgNzEuMjE4MyAxNjUuNDhDNzIuMTcxMiAxNjQuOTggNzMuMTI3MiAxNjQuNDc1IDc0LjA4NjMgMTYzLjk2NkM3NS4wMTc3IDE2My40NzUgNzUuOTUyMSAxNjIuOTc5IDc2Ljg4OTYgMTYyLjQ3NkM3Ny44MzMzIDE2MS45NzQgNzguNzgwMSAxNjEuNDY4IDc5LjcyNjkgMTYwLjk1NkM4MC42NDkgMTYwLjQ1OSA4MS41NzQyIDE1OS45NiA4Mi40OTkzIDE1OS40NTdDODMuNDMwNyAxNTguOTUxIDg0LjM2NTEgMTU4LjQ0MiA4NS4zMDI2IDE1Ny45MzFDODYuMjE1NSAxNTcuNDMxIDg3LjEyNTMgMTU2LjkzMSA4OC4wNDEyIDE1Ni40MjJIODguMDQ0M0M4OC45NjY0IDE1NS45MTcgODkuODkxNSAxNTUuNDA1IDkwLjgxOTggMTU0Ljg5QzkxLjcyMzQgMTU0LjM4NyA5Mi42MjcgMTUzLjg4MSA5My41MzM3IDE1My4zNzZDOTQuNDQ2NSAxNTIuODY0IDk1LjM2MjUgMTUyLjM0OSA5Ni4yNzg0IDE1MS44MzRDOTcuMTcyNyAxNTEuMzI4IDk4LjA2NzEgMTUwLjgyMiA5OC45NjQ1IDE1MC4zMTNDOTkuODY4MSAxNDkuNzk4IDEwMC43NzUgMTQ5LjI4IDEwMS42ODEgMTQ4Ljc2MkMxMDIuNTYzIDE0OC4yNTYgMTAzLjQ0OSAxNDcuNzUgMTA0LjMzNCAxNDcuMjM1QzEwNS4yMjggMTQ2LjcyMyAxMDYuMTI1IDE0Ni4yMDIgMTA3LjAyNiAxNDUuNjc4QzEwNy45MDIgMTQ1LjE3MiAxMDguNzgxIDE0NC42NiAxMDkuNjYgMTQ0LjE0NUMxMTAuNTQ1IDE0My42MjcgMTExLjQzMyAxNDMuMTA2IDExMi4zMjEgMTQyLjU4NUMxMTMuMTkxIDE0Mi4wNzMgMTE0LjA1NCAxNDEuNTU4IDExNC45MjQgMTQxLjA0M0MxMTUuODAzIDE0MC41MjIgMTE2LjY4MiAxNDAgMTE3LjU2MSAxMzkuNDczQzExNy44NjMgMTM5LjI5NCAxMTguMTY4IDEzOS4xMTIgMTE4LjQ3IDEzOC45M0MxMTkuMDI5IDEzOC41OTcgMTE5LjU4NyAxMzguMjYxIDEyMC4xNDIgMTM3LjkyOEMxMjEuMDE1IDEzNy40MDQgMTIxLjg4NCAxMzYuODc2IDEyMi43NTQgMTM2LjM1MkMxMjMuNjA4IDEzNS44MzQgMTI0LjQ1OSAxMzUuMzE5IDEyNS4zMTEgMTM0LjgwMUMxMjYuMTc0IDEzNC4yNzQgMTI3LjAzNSAxMzMuNzQ2IDEyNy44OTUgMTMzLjIxNkMxMjguNzQzIDEzMi42OTggMTI5LjU4OCAxMzIuMTggMTMwLjQzIDEzMS42NjFDMTMxLjI4NCAxMzEuMTMxIDEzMi4xMzUgMTMwLjYwMSAxMzIuOTg3IDEzMC4wN0MxMzMuODI1IDEyOS41NTIgMTM0LjY2MSAxMjkuMDMxIDEzNS40OTcgMTI4LjUwN0MxMzYuMzQ1IDEyNy45NzYgMTM3LjE5MyAxMjcuNDQ2IDEzOC4wMzUgMTI2LjkxNUMxMzguODY4IDEyNi4zOTEgMTM5LjY5NyAxMjUuODY3IDE0MC41MjQgMTI1LjM0NkMxNDEuMzYzIDEyNC44MTIgMTQyLjIwMSAxMjQuMjc5IDE0My4wMzcgMTIzLjc0NUMxNDMuODU4IDEyMy4yMjEgMTQ0LjY3OCAxMjIuNjk2IDE0NS40OTUgMTIyLjE2OUMxNDYuMzMxIDEyMS42MzYgMTQ3LjE2NCAxMjEuMDk5IDE0Ny45OSAxMjAuNTYyQzE0OC44MDcgMTIwLjAzNSAxNDkuNjIxIDExOS41MDggMTUwLjQyOSAxMTguOThDMTUxLjI1NiAxMTguNDQ0IDE1Mi4wNzYgMTE3LjkwNyAxNTIuODk3IDExNy4zN0MxNTMuNzA1IDExNi44NDMgMTU0LjUwOSAxMTYuMzEzIDE1NS4zMTEgMTE1Ljc4MkMxNTYuMTI5IDExNS4yNDYgMTU2Ljk0NiAxMTQuNzA2IDE1Ny43NTcgMTE0LjE2NkMxNTguNTU2IDExMy42MzYgMTU5LjM1NCAxMTMuMTA1IDE2MC4xNDcgMTEyLjU3MkMxNjAuOTU4IDExMi4wMzIgMTYxLjc2MyAxMTEuNDg5IDE2Mi41NjggMTEwLjk1QzE2My4zNiAxMTAuNDE2IDE2NC4xNSAxMDkuODgzIDE2NC45MzYgMTA5LjM1MkMxNjUuNzQxIDEwOC44MDYgMTY2LjU0IDEwOC4yNjQgMTY3LjMzNiAxMDcuNzIxQzE2OC4xMTkgMTA3LjE4NyAxNjguODk5IDEwNi42NTEgMTY5LjY3NiAxMDYuMTE3QzE3MC40NzIgMTA1LjU3MSAxNzEuMjY1IDEwNS4wMjUgMTcyLjA1NCAxMDQuNDgzQzE3Mi44MzEgMTAzLjk0MyAxNzMuNjAyIDEwMy40MDYgMTc0LjM3MyAxMDIuODdDMTc1LjE2IDEwMi4zMjQgMTc1Ljk0IDEwMS43NzggMTc2LjcyIDEwMS4yMjlDMTc3LjQ5MSAxMDAuNjkyIDE3OC4yNTYgMTAwLjE1MyAxNzkuMDE4IDk5LjYxMzFDMTc5Ljc5NSA5OS4wNjQyIDE4MC41NjkgOTguNTE1MiAxODEuMzQgOTcuOTY2M0MxODIuMTAyIDk3LjQyNjYgMTgyLjg2IDk2Ljg4NjkgMTgzLjYxNiA5Ni4zNDQxQzE4NC4zODcgOTUuNzkyMSAxODUuMTU1IDk1LjI0MzIgMTg1LjkxNiA5NC42OTExQzE4Ni42NjkgOTQuMTQ4NCAxODcuNDE4IDkzLjYwNTYgMTg4LjE2NSA5My4wNjI4QzE4OC45MjYgOTIuNTEwOCAxODkuNjg1IDkxLjk1ODggMTkwLjQ0MSA5MS40MDM3QzE5MS4xODcgOTAuODYwOSAxOTEuOTI3IDkwLjMxNSAxOTIuNjY3IDg5Ljc2OTJDMTkzLjQyMyA4OS4yMTQgMTk0LjE3MiA4OC42NTg5IDE5NC45MTggODguMTAzOEMxOTUuNjU1IDg3LjU1NDkgMTk2LjM4NiA4Ny4wMDkgMTk3LjExNCA4Ni40NjMyQzE5Ny44NiA4NS45MDUgMTk4LjYwNCA4NS4zNDk5IDE5OS4zNDEgODQuNzkxN0MyMDAuMDcyIDg0LjI0MjcgMjAwLjc5NiA4My42OTA3IDIwMS41MTggODMuMTQ0OEMyMDIuMjU1IDgyLjU4MzYgMjAyLjk4NiA4Mi4wMjU0IDIwMy43MTQgODEuNDY3MkMyMDQuNDM1IDgwLjkxNTIgMjA1LjE1MSA4MC4zNjMxIDIwNS44NjMgNzkuODExMUMyMDYuNTkxIDc5LjI0OTggMjA3LjMxNiA3OC42ODg1IDIwOC4wMzQgNzguMTI3M0MyMDguNzQ3IDc3LjU3NTIgMjA5LjQ1MyA3Ny4wMjAxIDIxMC4xNTYgNzYuNDY4MUMyMTAuODc1IDc1LjkwMzcgMjExLjU5IDc1LjMzOTQgMjEyLjMgNzQuNzc4MUMyMTYuOTQ3IDcxLjA5NTkgMjIxLjQxIDY3LjQ1NjggMjI1LjY2OCA2My44NzY0QzIyNS40NDYgNjMuNDYgMjI1LjIyNCA2My4wNDM3IDIyNC45OTYgNjIuNjMwNEgyMjUuMDE1Wk0yMi45NzYxIDE4Ni41MzRDMjIuMzg3MSAxODUuNjggMjEuODA3MyAxODQuODE2IDIxLjI0MjkgMTgzLjk0N0wyNi4wNzI0IDE4NS4xNDZDMjUuMDM2MiAxODUuNjE1IDI0LjAwNjEgMTg2LjA3OCAyMi45NzYxIDE4Ni41MzRaTTI5LjI0MjcgMTgzLjcwOUwxOS41NjgzIDE4MS4zMUMxOS4wNDQxIDE4MC40NjIgMTguNTMyMSAxNzkuNjA0IDE4LjAzMjUgMTc4Ljc0MUwzMi4zMjk3IDE4Mi4yOTFDMzEuMjk2NiAxODIuNzY5IDMwLjI2OTYgMTgzLjI0IDI5LjI0MjcgMTgzLjcwOVpNMzUuNDUzNyAxODAuODM4TDE2LjU3NjkgMTc2LjE1NEMxNi4xMTc0IDE3NS4zMTggMTUuNjcwMiAxNzQuNDc5IDE1LjIzNTQgMTczLjYzN0wzOC40OTc2IDE3OS40MUMzNy40Nzk5IDE3OS44OTEgMzYuNDY4NCAxODAuMzY5IDM1LjQ1NjggMTgwLjgzOEgzNS40NTM3Wk00MS41NzIzIDE3Ny45NDhMMTMuOTYxNyAxNzEuMDkzQzEzLjU2MDggMTcwLjI3MiAxMy4xNzIyIDE2OS40NDYgMTIuNzkyOSAxNjguNjE2TDQ0LjU2OTkgMTc2LjUwNUM0My41Njc2IDE3Ni45ODkgNDIuNTcxNSAxNzcuNDcgNDEuNTc1NCAxNzcuOTQ4SDQxLjU3MjNaTTQ3LjU5ODMgMTc1LjAzNEwxMS42ODU4IDE2Ni4xMThDMTEuMzM0MiAxNjUuMzEgMTAuOTk1IDE2NC40OTYgMTAuNjY4MSAxNjMuNjc5TDUwLjU1ODkgMTczLjU4MkM0OS41NjkgMTc0LjA2OSA0OC41ODUyIDE3NC41NTMgNDcuNjAxNCAxNzUuMDM0SDQ3LjU5ODNaTTUzLjU0NDIgMTcyLjA5OEw5LjcwMjc5IDE2MS4yMThDOS4zOTc0OCAxNjAuNDE5IDkuMTA0NTEgMTU5LjYxNyA4LjgyMDc4IDE1OC44MTNMNTYuNDYxNiAxNzAuNjM2QzU1LjQ4NyAxNzEuMTMgNTQuNTE1NiAxNzEuNjE0IDUzLjU0NDIgMTcyLjA5OFpNNTkuNDA5OCAxNjkuMTQ0TDcuOTk3MzcgMTU2LjM4NUM3LjczNTIzIDE1NS41OTYgNy40ODU0MyAxNTQuODA2IDcuMjQ0ODkgMTU0LjAxMUw2Mi4yOTMzIDE2Ny42NzNDNjEuMzMxMSAxNjguMTY5IDYwLjM2ODkgMTY4LjY2IDU5LjQwOTggMTY5LjE0NFpNNjUuMjEwNyAxNjYuMTcxTDYuNTQxNzUgMTUxLjYxMkM2LjMxOTcgMTUwLjgzMSA2LjEwOTk5IDE1MC4wNTQgNS45MDY0NSAxNDkuMjY4TDY4LjA1NzIgMTY0LjY5NEM2Ny4xMDc0IDE2NS4xOSA2Ni4xNTc1IDE2NS42ODQgNjUuMjEwNyAxNjYuMTcxWk03MC45NDA3IDE2My4xODJMNS4zMjA1IDE0Ni44OTZDNS4xMzU0NyAxNDYuMTI1IDQuOTU5NjggMTQ1LjM1MSA0Ljc5MzE1IDE0NC41NzdMNzMuNzU5NCAxNjEuNjk2QzcyLjgxODggMTYyLjE5NiA3MS44ODEzIDE2Mi42OTIgNzAuOTQzOCAxNjMuMTgySDcwLjk0MDdaTTc2LjYwMjggMTYwLjE3OUw0LjMxNTE0IDE0Mi4yMzZDNC4xNjA5NCAxNDEuNDc1IDQuMDIyMTYgMTQwLjcxIDMuODg5NTUgMTM5Ljk0NUw3OS4zODc2IDE1OC42ODNDNzguNDU5NCAxNTkuMTg2IDc3LjUzMTEgMTU5LjY4NSA3Ni42MDI4IDE2MC4xNzlaTTgyLjIwMzMgMTU3LjE2TDUuMDQ2MDMgMTM4LjAwOEM3LjE1ODU0IDEzNy44MiA5LjI3MTA0IDEzNy42MTcgMTEuMzg2NiAxMzcuMzk1TDg0Ljk2MDMgMTU1LjY1NUM4NC4wNDEzIDE1Ni4xNiA4My4xMjU0IDE1Ni42NjMgODIuMjA2NCAxNTcuMTZIODIuMjAzM1pNODcuNzQ4MiAxNTQuMTI1TDE3LjU5NDYgMTM2LjcxM0MxOS41NTYgMTM2LjQ4NSAyMS41MTEyIDEzNi4yNDEgMjMuNDcyNiAxMzUuOTg1TDkwLjQ3MTMgMTUyLjYxNEM4OS41NjE2IDE1My4xMiA4OC42NTQ5IDE1My42MjIgODcuNzQ4MiAxNTQuMTI1Wk05My4yMjg0IDE1MS4wNzVMMjkuMjY0MyAxMzUuMTk5QzMxLjEwMjMgMTM0LjkzNyAzMi45MzcyIDEzNC42NjIgMzQuNzcyMiAxMzQuMzc4TDk1LjkyMzcgMTQ5LjU1NUM5NS4wMjYzIDE1MC4wNjMgOTQuMTI4OSAxNTAuNTcyIDkzLjIzMTUgMTUxLjA3NUg5My4yMjg0Wk05OC42NTMgMTQ4LjAwOUg5OC42NUw0MC4yMTg0IDEzMy41MDZDNDEuOTQ4NSAxMzMuMjE2IDQzLjY3NTUgMTMyLjkyIDQ1LjQwNTYgMTMyLjYwOEg0NS40MTE4TDEwMS4zMTQgMTQ2LjQ4M0MxMDAuNDI2IDE0Ni45OTggOTkuNTQxMiAxNDcuNTA0IDk4LjY1MyAxNDguMDA5Wk0xMDQuMDE5IDE0NC45MzJMNTAuNTcxMiAxMzEuNjY1QzUyLjIxODEgMTMxLjM1MyA1My44NjE4IDEzMS4wMzIgNTUuNTA1NiAxMzAuNzAyTDEwNi42NTkgMTQzLjM5OUMxMDUuNzggMTQzLjkxMSAxMDQuODk4IDE0NC40MjMgMTA0LjAxNiAxNDQuOTMySDEwNC4wMTlaTTEwOS4zMzMgMTQxLjgzOUw2MC40MDkgMTI5LjY5N0M2MS45ODE4IDEyOS4zNjcgNjMuNTQ4NSAxMjkuMDI4IDY1LjExODIgMTI4LjY3OUwxMTEuOTQ4IDE0MC4zMDNDMTExLjA3NSAxNDAuODE4IDExMC4yMDIgMTQxLjMzIDEwOS4zMyAxNDEuODM5SDEwOS4zMzNaTTExNC42IDEzOC43MzZMNjkuODA1OCAxMjcuNjE4QzcxLjMxMDggMTI3LjI3IDcyLjgwOTYgMTI2LjkxMiA3NC4zMTE1IDEyNi41NTFINzQuMzE0NUwxMTcuMTg0IDEzNy4xOTFDMTE2LjMyNCAxMzcuNzA5IDExNS40NjEgMTM4LjIyMSAxMTQuNiAxMzguNzM2Wk0xMTkuODA2IDEzNS42MThMNzguODA0OCAxMjUuNDQxQzgwLjI0OCAxMjUuMDc3IDgxLjY4ODIgMTI0LjcwNCA4My4xMjg0IDEyNC4zMjhMMTIyLjM2OSAxMzQuMDY3QzEyMS41MTcgMTM0LjU4OCAxMjAuNjYzIDEzNS4xMDMgMTE5LjgwNiAxMzUuNjE4Wk0xMjQuOTY4IDEzMi40ODhMODcuNDQ2IDEyMy4xNzRDODguODM2OCAxMjIuNzk1IDkwLjIyMTUgMTIyLjQxMyA5MS42MDYyIDEyMi4wMjFMMTI3LjUxMyAxMzAuOTM0QzEyNi42NjggMTMxLjQ1MiAxMjUuODIgMTMxLjk3IDEyNC45NjggMTMyLjQ4OFpNMTMwLjA4NSAxMjkuMzQ1TDk1Ljc2MzQgMTIwLjgyOEM5Ny4xMDQ5IDEyMC40MzkgOTguNDQwMiAxMjAuMDQxIDk5Ljc3NTYgMTE5LjYzN0wxMzIuNjAxIDEyNy43ODVDMTMxLjc2NSAxMjguMzA2IDEzMC45MjcgMTI4LjgyNyAxMzAuMDg1IDEyOS4zNDVaTTEzNS4xNDggMTI2LjE5NEwxMDMuNzg4IDExOC40MUMxMDUuMDgzIDExOC4wMDYgMTA2LjM3NSAxMTcuNTk5IDEwNy42NjQgMTE3LjE4NUwxMzcuNjQzIDEyNC42MjdDMTM2LjgxNCAxMjUuMTQ4IDEzNS45ODEgMTI1LjY3MiAxMzUuMTQ4IDEyNi4xOTRaTTE0MC4xNzIgMTIzLjAzTDExMS41NDEgMTE1LjkyMUMxMTIuNzkzIDExNS41MTEgMTE0LjAzOSAxMTUuMDkxIDExNS4yODUgMTE0LjY2NkwxNDIuNjMzIDEyMS40NTRDMTQxLjgxMyAxMjEuOTgxIDE0MC45OTMgMTIyLjUwNSAxNDAuMTY5IDEyMy4wM0gxNDAuMTcyWk0xNDUuMTQgMTE5Ljg1TDExOS4wNDEgMTEzLjM3NEMxMjAuMjUzIDExMi45NTEgMTIxLjQ2NSAxMTIuNTIzIDEyMi42NzEgMTEyLjA4OEwxNDcuNTkyIDExOC4yNzFDMTQ2Ljc3OCAxMTguNzk4IDE0NS45NjEgMTE5LjMyNiAxNDUuMTQ0IDExOS44NUgxNDUuMTRaTTE1MC4wNjkgMTE2LjY2NEwxMjYuMzA0IDExMC43NjVDMTI3LjQ3OSAxMTAuMzMzIDEyOC42NTEgMTA5Ljg5NSAxMjkuODE5IDEwOS40NTFMMTUyLjQ4OSAxMTUuMDc5QzE1MS42ODUgMTE1LjYwNiAxNTAuODggMTE2LjEzNyAxNTAuMDcyIDExNi42NjRIMTUwLjA2OVpNMTU0Ljk0MSAxMTMuNDYzTDEzMy4zNDEgMTA4LjFDMTM0LjQ3OSAxMDcuNjU5IDEzNS42MTcgMTA3LjIxMiAxMzYuNzQ5IDEwNi43NjJIMTM2Ljc1MkwxNTcuMzQxIDExMS44NzJDMTU2LjU0NSAxMTIuNDAyIDE1NS43NDMgMTEyLjkzMyAxNTQuOTQxIDExMy40NjNaTTE1OS43NjggMTEwLjI1TDE0MC4xODEgMTA1LjM4OUMxNDEuMzE5IDEwNC45MzMgMTQyLjQzOSAxMDQuNDggMTQzLjU0IDEwNC4wMzVMMTYyLjE0OCAxMDguNjU1QzE2MS4zNTYgMTA5LjE4OSAxNjAuNTYzIDEwOS43MjIgMTU5Ljc2OCAxMTAuMjVaTTE2NC41NTQgMTA3LjAyN0wxNDYuOTM4IDEwMi42NTRDMTQ4LjA2NCAxMDIuMTk0IDE0OS4xNjggMTAxLjc0MSAxNTAuMjU3IDEwMS4yOTRMMTY2LjkwNCAxMDUuNDIzQzE2Ni4xMjQgMTA1Ljk2IDE2NS4zNCAxMDYuNDkzIDE2NC41NTQgMTA3LjAyN1pNMTY5LjI4OCAxMDMuNzkyTDE1My42MTUgOTkuODk5OUMxNTQuNzI1IDk5LjQ0MDQgMTU1LjgxNCA5OC45ODA5IDE1Ni44OSA5OC41Mjc2TDE3MS42MTYgMTAyLjE4MkMxNzAuODQyIDEwMi43MjIgMTcwLjA2NSAxMDMuMjU1IDE2OS4yODggMTAzLjc5MlpNMTczLjk3NSAxMDAuNTQ0TDE2MC4xOTYgOTcuMTI0NEMxNjEuMjg1IDk2LjY1ODcgMTYyLjM1OCA5Ni4xOTYxIDE2My40MjIgOTUuNzM5N0wxNzYuMjc2IDk4LjkyODVDMTc1LjUxMSA5OS40NjgyIDE3NC43NDMgMTAwLjAwNSAxNzMuOTcyIDEwMC41NDRIMTczLjk3NVpNMTc4LjYxNCA5Ny4yODQ3TDE2Ni42NjkgOTQuMzIxMUMxNjcuNzM2IDkzLjg0OTIgMTY4Ljc4OCA5My4zODM1IDE2OS44MzEgOTIuOTE3OUwxODAuODkzIDk1LjY2MjZDMTgwLjEzNCA5Ni4yMDU0IDE3OS4zNzUgOTYuNzQ1IDE3OC42MTQgOTcuMjg0N1pNMTgzLjIwMiA5NC4wMTI3TDE3My4wMTMgOTEuNDgzOEMxNzQuMDUyIDkxLjAxMiAxNzUuMDg2IDkwLjU0MDEgMTc2LjEwOSA5MC4wNjUyTDE4NS40NiA5Mi4zODc0QzE4NC43MTEgOTIuOTMwMiAxODMuOTU4IDkzLjQ2OTkgMTgzLjIwMiA5NC4wMTI3Wk0xODcuNzM5IDkwLjczMTNMMTc5LjIyNyA4OC42MTU4QzE4MC4yNDIgODguMTQwOCAxODEuMjUgODcuNjYyOCAxODIuMjU5IDg3LjE4MTdMMTg5Ljk3MiA4OS4wOTY5QzE4OS4yMjkgODkuNjQyNyAxODguNDg1IDkwLjE4NTUgMTg3LjczNiA5MC43MzEzSDE4Ny43MzlaTTE5Mi4yMzIgODcuNDMxNUwxODUuMzE4IDg1LjcxNjlDMTg2LjMwNSA4NS4yMzg4IDE4Ny4yOTIgODQuNzU0NyAxODguMjg1IDg0LjI2NzRMMTk0LjQzNyA4NS43OTRDMTkzLjcwNiA4Ni4zNDI5IDE5Mi45NjkgODYuODg4OCAxOTIuMjMyIDg3LjQzMTVaTTE5Ni42NzMgODQuMTIyNUwxOTEuMjg2IDgyLjc4NzFDMTkyLjI1MSA4Mi4zMDYgMTkzLjIyMiA4MS44MTg3IDE5NC4yIDgxLjMyMjJMMTk4Ljg1NyA4Mi40Nzg3QzE5OC4xMzUgODMuMDI3NyAxOTcuNDA3IDgzLjU3NjYgMTk2LjY3NiA4NC4xMjI1SDE5Ni42NzNaTTIwMS4wNjIgODAuODA0MUwxOTcuMTQyIDc5LjgyOTZDMTk4LjA5MiA3OS4zNDg1IDE5OS4wNTEgNzguODU1MSAyMDAuMDE5IDc4LjM1ODZMMjAzLjIxNCA3OS4xNTExQzIwMi40OTkgNzkuNzAwMSAyMDEuNzggODAuMjUyMSAyMDEuMDU5IDgwLjgwNDFIMjAxLjA2MlpNMjA1LjM5NSA3Ny40NjczTDIwMi45MjEgNzYuODUzNkMyMDMuODU5IDc2LjM2OTQgMjA0LjgwMiA3NS44NzI5IDIwNS43NjIgNzUuMzczM0wyMDcuNTIyIDc1LjgxMTJDMjA2LjgxNiA3Ni4zNjMzIDIwNi4xMDcgNzYuOTE1MyAyMDUuMzk1IDc3LjQ2NzNaTTIwOS42NzUgNzQuMTIxMkwyMDguNjMgNzMuODYyMkMyMDkuOTgzIDczLjE0OTggMjExLjM1OSA3Mi40MTg5IDIxMi43NjUgNzEuNjY5NUMyMTEuNzQ0IDcyLjQ4NjcgMjEwLjcxNCA3My4zMDQgMjA5LjY3NSA3NC4xMjEyWiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cg==';

        // Main Menu.
        $dashboardPageSuffix = add_menu_page(
            _x('StellarPay', 'Page title', 'stellarpay'),
            _x('StellarPay', 'Menu title', 'stellarpay'),
            'manage_options',
            $this->baseSlug,
            [$this, 'dashboardRenderer'],
            $stellarPayIcon,
            58
        );

        // Register assets for the dashboard page.
        add_action('load-' . $dashboardPageSuffix, [$this, 'enqueueDashboardAdminScripts']);

        // Only display onboarding when Stripe account is not connected in LIVE mode.
        // We require a live account to be connected to use the plugin.
        // todo: update this logic so when we add PayPal or other payment gateways.
        if (! $this->accountRepository->isLiveModeConnected()) {
            return;
        }

        $isStellarPayPage = $this->baseSlug === $this->request->get('page');

        if ($isStellarPayPage) {
            return;
        }

        // Sub Menu - Dashboard.
        $this->registerSubMenu(
            _x('Dashboard', 'Page title', 'stellarpay'),
            'dashboard'
        );

        // Remove duplicate menu hack.
        // Note: It needs to go after dashboard submenu registration (add_submenu_page call).
        remove_submenu_page($this->baseSlug, $this->baseSlug);

        $this->registerSubMenu(
            _x('Payments', 'Page title', 'stellarpay'),
            'payments'
        );

        $this->registerSubMenu(
            _x('Subscriptions', 'Page title', 'stellarpay'),
            'subscriptions'
        );

        $this->registerSubMenu(
            _x('Events', 'Page title', 'stellarpay'),
            'events'
        );

        $this->registerSubMenu(
            _x('Settings', 'Page title', 'stellarpay'),
            'settings'
        );
    }

    /**
     * Register a submenu.
     *
     * @param string $menuTitle The text to be displayed in the title tags of the page when the menu is selected.
     * @param string $slug      The slug name to refer to this menu by (should be unique for this menu).
     *
     * @since 1.0.0
     */
    private function registerSubMenu(string $menuTitle, string $slug): void
    {
        $this->baseSlug = Constants::PLUGIN_SLUG;
        add_submenu_page(
            $this->baseSlug,
            _x('StellarPay Dashboard', 'Page title', 'stellarpay'),
            $menuTitle,
            'manage_options',
            "$this->baseSlug#/$slug",
            '__return_null'
        );
    }

    /**
     * Register the menu bar for test/live mode indicator.
     *
     * @since 1.0.0
     */
    public function registerMenuBar(WP_Admin_Bar $wpAdminBar): bool
    {
        if (! $this->stripeSettingRepository->isTestModeActive()) {
            return false;
        }

        $wpAdminBar->add_menu(
            [
                'id'     => 'stellarpay-test-notice',
                'href'   => admin_url("admin.php?page={$this->baseSlug}#/settings/development"),
                'parent' => 'top-secondary',
                'title'  => esc_html__('Test Mode Active', 'stellarpay'),
                'meta'   => [
                    'class' => 'stellarpay-test-mode-active',
                ],
            ]
        );

        return true;
    }

    /**
     * Add styles for the menu bar.
     *
     * @since 1.0.0
     */
    public function addMenuBarStyle(): void
    {

        if (! $this->stripeSettingRepository->isTestModeActive()) {
            return;
        }

        $scriptId = 'stellarpay-menu-bar-style';
        wp_register_style($scriptId, false, [], Constants::VERSION);
        wp_enqueue_style($scriptId);

        $style = '
        #wpadminbar .stellarpay-test-mode-active > .ab-item {
            color: #fff;
            display: flex;
            align-items: center;
        }

        .toplevel_page_stellarpay > .wp-menu-name::after,
         #wpadminbar .stellarpay-test-mode-active > .ab-item::before{
            content: "";
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgb(234, 88, 12 );
            box-sizing: border-box;
            box-shadow: inset 0 -1px 1px hsl(24,89%,57%),
            inset 0 -1px 3px hsl(25,84%,50%);
        }
        .toplevel_page_stellarpay > .wp-menu-name::after {
            margin-left: 8px;
        }';


        wp_add_inline_style($scriptId, $style);
    }

    /**
     * Enqueue plugin admin scripts and styles.
     *
     * @since 1.6.0 Remove WooCommerce dependencies if WooCommerce is not active.
     * @since 1.0.0
     *
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    public function enqueueDashboardAdminScripts(): void
    {
        $dashBoardScript = (new EnqueueScript(
            'dashboard',
            '/build/dashboard.js'
        ));

        $dashBoardScript
            ->loadInFooter()
            ->loadStyle(['wp-admin', 'wp-components'])
            ->registerLocalizeData('stellarPayDashboardData', $this->getDashboardData())
            ->registerTranslations()
            ->dependenciesFilter(function (array $dependencies) {
                if (! Environment::isWoocommerceActive()) {
                    $dependencies = array_filter($dependencies, function ($dependency) {
                        return strpos($dependency, 'wc-') !== 0;
                    });
                }
                return $dependencies;
            })
            ->enqueue();
    }

    /**
     * Get the initial state data for hydrating the React UI.
     *
     * @since 1.0.0
     *
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    protected function getDashboardData(): array
    {
        $invokable = $this->dashboardDTO;
        return $invokable();
    }

    /**
     * Plugin Dashboard page.
     *
     * @since 1.0.0
     */
    public function dashboardRenderer(): void
    {
        printf('<div id="stellarpay-dashboard-root"></div>');
    }
}
