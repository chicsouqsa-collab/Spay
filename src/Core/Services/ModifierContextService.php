<?php

/**
 * Manages ModifierContext
 *
 * For a few asynchronous actions we need modifier context to add it in the order note.
 *
 * For example, we confirm subscription cancellation with Stripe webhook when admin cancels a subscription.
 *              An order note is added to order upon webhook processing. We use this service to access accurate modifier context for action.
 *
 * @package StellarPay\Core\ValueObjects\ModifierContextType
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Services;

use StellarPay\Core\ValueObjects\ModifierContextType;

use function StellarPay\Core\prefixedKey;

/**
 * @since 1.4.0
 */
class ModifierContextService
{
    /**
     * @since 1.4.0
     */
    private string $eventType;

    /**
     * @since 1.4.0
     */
    private int $objectId;

    /**
     * @since 1.4.0
     */
    private int $expirationTime;

    /**
     * @since 1.4.0
     */
    public function __construct(string $eventType, int $objectId)
    {
        $this->eventType = $eventType;
        $this->objectId = $objectId;
        $this->expirationTime = MINUTE_IN_SECONDS * 10;
    }

    /**
     * @since 1.4.0
     */
    public static function fromArray(array $array): self
    {
        if (! isset($array['eventType'], $array['objectId'])) {
            throw new \InvalidArgumentException('Invalid array passed to ModifierContextService::fromArray');
        }

        return new self($array['eventType'], $array['objectId']);
    }

    /**
     * Stores a ModifierContextType in a transient.
     *
     * @since 1.4.0
     */
    public function storeContext(ModifierContextType $modifierContext): bool
    {
        return set_transient(
            $this->getTransientKey(),
            ['modifier_context' => $modifierContext->getValue()],
            $this->expirationTime
        );
    }

    /**
     * @since 1.4.0
     */
    private function getContext(): ?array
    {
        return get_transient($this->getTransientKey()) ?: null;
    }

    /**
     * @since 1.4.0
     */
    public function removeContext(): void
    {
        $transientKey = $this->getTransientKey();
        delete_transient($transientKey);
    }

    /**
     * @since 1.4.0
     */
    public function getModifierContextType(): ?ModifierContextType
    {
        $modifierContextState = $this->getContext();

        if (! $modifierContextState || ! ModifierContextType::isValid($modifierContextState['modifier_context'])) {
            return null;
        }

        return ModifierContextType::from($modifierContextState['modifier_context']);
    }

    /**
     * @since 1.4.0
     */
    private function getTransientKey(): string
    {
        return prefixedKey('modifier_context_' . $this->objectId . '_' . $this->eventType);
    }
}
