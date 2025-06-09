# Request-Interop Implementation

The reference implementation is readonly; it cannot be modified after construction, and provides idempotent readonly access to the request body.

```php
use RequestInterop\Impl\Request;
use RequestInterop\Impl\RequestFactory;

$factory = new RequestFactory();
$request = $factory->newRequest();
assert($request instanceof Request::class);
```
