# clerk-magento2
Magento 2 extension for Clerk.io

## Multi-Store Order Import

For multi-store setups where you need to import orders from all stores (not just the current store scope), you can enable the "Import orders from all stores" option in the Clerk.io configuration.

### Configuration

1. Go to **Stores > Configuration > Clerk > Synchronization**
2. Set **Import orders from all stores** to **Yes**
3. Save the configuration

### When to Use This Feature

This feature is particularly useful for:
- Multi-store setups with different order prefixes (e.g., B2C_, B2B_, etc.)
- Stores using marketplace integrations like M2E Pro that create orders in different stores
- Situations where you need comprehensive order history across all stores in Clerk.io

### Default Behavior

By default, the extension imports orders only from the current store scope (existing behavior). When the multi-store option is enabled, orders from all stores will be imported regardless of their store_id or order prefix.

### Logging

The extension logs which import strategy is being used to help with troubleshooting:
- "Order Store Filter: ENABLED" - importing from current store only
- "Order Store Filter: DISABLED" - importing from all stores
