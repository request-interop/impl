# Request Interop Implementations

This package includes two RequestInterop implementations: one readonly, and one mutable. It also includes an abstract _RequestFactory_ class with the elements needed for creating your own implementations.

## Notes

- No `user` or `password` in URL properties (deprecated in RFC 3986; sent by clients as `Authorization: Basic` header so not in URL anyway)

## Readonly

The _Readonly_ implementations cannot be modified after construction, and provide idempotent readonly access to the request body (but no access to the upload bodies).

```php
use RequestInterop\Impl\Readonly\ReadonlyRequest;
use RequestInterop\Impl\Readonly\ReadonlyRequestFactory;

$factory = new ReadonlyRequestFactory();
$request = $factory->newRequest();
assert($request instanceof ReadonlyRequest::class);
```

## Mutable

The _Mutable_ implementations can be modified after construction, and provide non-idempotent readable access to the request body and each upload body.

```php
use RequestInterop\Impl\Mutable\MutableRequest;
use RequestInterop\Impl\Mutable\MutableRequestFactory;

$factory = new MutableRequestFactory();
$request = $factory->newRequest();
assert($request instanceof MutableRequest::class);
```
