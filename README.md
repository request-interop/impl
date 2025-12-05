# Request-Interop Implementation

There are two reference implementations: one is readonly, and cannot be modified after construction; the other is mutable.

## Readonly

```php
use RequestInterop\Impl\Readonly\ReadonlyRequest;
use RequestInterop\Impl\Readonly\ReadonlyRequestFactory;

$factory = new ReadonlyRequestFactory();
$request = $factory->newRequest();
assert($request instanceof ReadonlyRequest::class);
```


## Mutable

```php
use RequestInterop\Impl\Mutable\MutableRequest;
use RequestInterop\Impl\Mutable\MutableRequestFactory;

$factory = new MutableRequestFactory();
$request = $factory->newRequest();
assert($request instanceof MutableRequest::class);
```
