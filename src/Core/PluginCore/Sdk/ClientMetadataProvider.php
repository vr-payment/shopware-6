<?php

declare(strict_types=1);

namespace VRPaymentPayment\Core\PluginCore\Sdk;

use VRPayment\PluginCore\Sdk\ClientMetadata;
use VRPayment\PluginCore\Sdk\ClientMetadataProviderInterface;

/**
 * Provides the shop system and plugin versions that plugin-core sends as headers with
 * every API call.
 */
class ClientMetadataProvider implements ClientMetadataProviderInterface
{
    private ?ClientMetadata $metadata = null;

    /**
     * @param string $shopwareVersion
     */
    public function __construct(
        private readonly string $shopwareVersion,
    ) {
    }

    /**
     * Returns the metadata identifying this Shopware installation and plugin version.
     *
     * @return ClientMetadata
     */
    public function getClientMetadata(): ClientMetadata
    {
        if (!isset($this->metadata)) {
            $this->metadata = new ClientMetadata(
                shopSystem: 'shopware',
                shopSystemVersion: $this->shopwareVersion,
                pluginVersion: '7.5.0',
            );
        }
        return $this->metadata;
    }
}
