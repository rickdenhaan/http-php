Capirussa HTTP Library
======================

[![Build Status](https://travis-ci.org/rickdenhaan/http-php.png?branch=master)](https://travis-ci.org/rickdenhaan/http-php)
[![Coverage Status](https://coveralls.io/repos/rickdenhaan/http-php/badge.png?branch=master)](https://coveralls.io/r/rickdenhaan/http-php)

This simple PHP library simplifies communication with an external service over HTTP or HTTPS.


Usage
-----

```php
use Capirussa\Http;

try {
    $request = new Http\Request();
    $request->setRequestUrl('http://www.example.com');
    $request->setRequestMethod(Http\Request::METHOD_GET);
    $response = $request->send();
} catch (\Exception $exception) {
    // something went wrong, fix it and try again!
}
```

If you find any bugs in this library, please [raise an issue on Github](https://github.com/rickdenhaan/http-php/issues).

Happy coding!