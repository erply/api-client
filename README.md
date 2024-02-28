# API Client

A basic PHP implementation of an Erply API adapter.

## Dependencies

- CURL
- JSON

## Usage

```php
require __DIR__ . '/library/ApiClient.php';

$client = new \Erply\ApiClient\ApiClient();

$client
    ->setUrl('https://123.erply.com/api')
    ->setClientCode('123')
    ->setUsername('myuser')
    ->setPassword('mypassword')
    ->setConnectionTimeout(60)
    ->setExecutionTimeout(60);

print_r($client->sendRequest('getCustomerGroups'));

/**
 * Prints:
 * Array
 * (
 *   [status] => Array
 *   (
 *     [request] => getReasonCodes
 *     [requestUnixTime] => 1709111082
 *     [responseStatus] => ok
 *     [errorCode] => 0
 *     [generationTime] => 0.08368992805481
 *     [recordsTotal] => 5
 *     [recordsInResponse] => 5
 *   )
 *   [records] => Array
 *   (
 *     [0] => Array
 *     (
 *       [reasonID] => 1
 *       [name] => samples
 *       [added] => 0
 *       [lastModified] =>
 *       [purpose] =>
 *       [code] =>
 *       [manualDiscountDisablesPromotionTiers] => Array
 *       (
 *       )
 *     )
 *     ...
 */
```
