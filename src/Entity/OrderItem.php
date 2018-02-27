<?php

declare(strict_types=1);

namespace Brille24\CustomerOptionsPlugin\Entity;

use Brille24\CustomerOptionsPlugin\Entity\CustomerOptions\CustomerOptionValueInterface;
use Brille24\CustomerOptionsPlugin\Entity\CustomerOptions\CustomerOptionValuePrice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\OrderItem as BaseOrderItem;
use Sylius\Component\Order\Model\OrderItemInterface as BaseOrderItemInterface;
use Sylius\Component\Core\Model\OrderItemInterface as BaseCoreOrderItemInterface;

class OrderItem extends BaseOrderItem implements OrderItemInterface
{
    /** @var Collection */
    protected $configuration;

    public function __construct()
    {
        parent::__construct();
        $this->configuration = new ArrayCollection();
    }

    /** {@inheritdoc} */
    public function setCustomerOptionConfiguration(array $configuration): void
    {
        $this->configuration = new ArrayCollection($configuration);
    }

    /** {@inheritdoc} */
    public function getCustomerOptionConfiguration(): array
    {
        return $this->configuration->toArray();
    }

    /** {@inheritdoc} */
    public function getSubtotal(): int
    {
        $basePrice = parent::getSubtotal();

        return (int)$this->applyConfigurationPrices($basePrice, $this->getQuantity());
    }

    /** {@inheritdoc} */
    public function equals(BaseOrderItemInterface $item): bool
    {
        $parentEquals = parent::equals($item);

        if (!$parentEquals && !$item instanceof BaseCoreOrderItemInterface) {
            return $parentEquals;
        }

        $product = $item->getProduct();
        return ($product instanceof Product) ? !$product->hasCustomerOptions() : true;
    }

    /** {@inheritdoc} */
    public function recalculateUnitsTotal(): void
    {
        $this->unitsTotal = 0;

        foreach ($this->units as $unit) {
            $this->unitsTotal += $this->applyConfigurationPrices($unit->getTotal());
        }

        $this->recalculateTotal();
    }

    /**
     * Applies the configuration pricing and returns the new price.
     *
     * @param int $basePrice
     * @param int $quantity
     *
     * @return int
     */
    protected function applyConfigurationPrices(int $basePrice, int $quantity = 1): int
    {
        $result = $basePrice;

        /** @var OrderItemOptionInterface $value */
        foreach ($this->configuration as $value) {
            if ($value->getPricingType() === CustomerOptionValuePrice::TYPE_PERCENT) {
                $result += $basePrice * $value->getPercent() / 100;
            } else {
                $result += $value->getFixedPrice() * $quantity;
            }
        }

        return (int) round($result, 0);
    }
}


