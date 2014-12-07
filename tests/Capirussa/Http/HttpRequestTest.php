<?php
require_once(dirname(__FILE__) . '/../../init.php');

use Capirussa\Http\Request;

/**
 * Tests Capirussa\Http\Request
 *
 */
class HttpRequestTest extends PHPUnit_Framework_TestCase
{
    public function testConstructWithoutParameters()
    {
        $request = new Request();

        $this->assertNull($request->getRequestUrl());
        $this->assertEquals(Request::METHOD_GET, $request->getRequestMethod());
        $this->assertInternalType('array', $request->getQueryParameters());
        $this->assertCount(0, $request->getQueryParameters());
        $this->assertInternalType('array', $request->getPostParameters());
        $this->assertCount(0, $request->getPostParameters());
        $this->assertTrue($this->getObjectAttribute($request, 'validateSsl'));
        $this->assertNull($request->getLastResponse());
    }

    public function testConstructWithRequestUrl()
    {
        $request = new Request('http://www.example.com');

        $this->assertEquals('http://www.example.com', $request->getRequestUrl());
    }

    public function testConstructWithRequestMethod()
    {
        $request = new Request(null, Request::METHOD_POST);

        $this->assertEquals(Request::METHOD_POST, $request->getRequestMethod());
    }

    public function testConstructWithDisableSslVerification()
    {
        $request = new Request(null, null, false);

        $this->assertFalse($this->getObjectAttribute($request, 'validateSsl'));
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testIsValidRequestUrlWithoutRequestUrl()
    {
        /** @noinspection PhpParamsInspection (this is intentional) */
        Request::isValidRequestUrl();
    }

    public function testIsValidRequestUrlWithRequestUrl()
    {
        $validRequestUrls = array(
            'http://www.example.com',
            'https://www.example.com',
            'HTTP://WWW.EXAMPLE.COM',
            'HTTPS://WWW.EXAMPLE.COM',
            'http://www.example.com/example',
            'http://example.codes',
            'http://example.com/',
            'http://www.example.subdomain.example.com',
            'https://www.example.com/example?foo=bar',
            'http://example.com/example#foo/bar',
            'ftp://example.com',
            'ftps://example.com',
        );

        foreach ($validRequestUrls as $requestUrl) {
            $this->assertTrue(Request::isValidRequestUrl($requestUrl));
        }

        $invalidRequestUrls = array(
            'example.com',
            'www.example.com',
            'example.com/foo',
            '$!$',
            '',
            null,
        );

        foreach ($invalidRequestUrls as $requestUrl) {
            $this->assertFalse(Request::isValidRequestUrl($requestUrl));
        }
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testSetRequestUrlWithoutParameters()
    {
        $request = new Request();

        /** @noinspection PhpParamsInspection (this is intentional) */
        $request->setRequestUrl();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid request URL
     */
    public function testSetRequestUrlWithInvalidRequestUrl()
    {
        $request = new Request();

        $request->setRequestUrl('invalidRequestUrl');
    }

    public function testSetRequestUrlWithValidRequestUrl()
    {
        $request = new Request();

        $this->assertNull($request->getRequestUrl());

        $request->setRequestUrl('http://www.example.com');

        $this->assertEquals('http://www.example.com', $request->getRequestUrl());
    }

    public function testSetRequestUrlWithChainingRequestUrl()
    {
        $request = new Request();

        $this->assertNull($request->getRequestUrl());

        $request
            ->setRequestUrl('http://www.example.com')
            ->setRequestUrl('http://www.example.com/foo/bar');

        $this->assertEquals('http://www.example.com/foo/bar', $request->getRequestUrl());
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testIsValidRequestMethodWithoutRequestMethod()
    {
        /** @noinspection PhpParamsInspection (this is intentional) */
        Request::isValidRequestMethod();
    }

    public function testIsValidRequestMethodWithRequestMethod()
    {
        $validRequestMethodsByConstant = array(
            Request::METHOD_GET,
            Request::METHOD_POST,
        );

        foreach ($validRequestMethodsByConstant as $requestMethod) {
            $this->assertTrue(Request::isValidRequestMethod($requestMethod));
        }

        $validRequestMethodsByValue = array(
            'GET',
            'POST',
        );

        foreach ($validRequestMethodsByValue as $requestMethod) {
            $this->assertTrue(Request::isValidRequestMethod($requestMethod));
        }

        for ($idx = 0; $idx < 1000; $idx++) {
            $requestMethod = '';

            for ($chr = 0; $chr < mt_rand(0, 10); $chr++) {
                $requestMethod .= ord(mt_rand(0, 255));
            }

            if (!in_array($requestMethod, $validRequestMethodsByConstant) && !in_array($requestMethod, $validRequestMethodsByValue)) {
                $this->assertFalse(Request::isValidRequestMethod($requestMethod));
            }
        }
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testSetRequestMethodWithoutParameters()
    {
        $request = new Request();

        /** @noinspection PhpParamsInspection (this is intentional) */
        $request->setRequestMethod();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid request method
     */
    public function testSetRequestMethodWithInvalidRequestMethod()
    {
        $request = new Request();

        $request->setRequestMethod('invalidRequestMethod');
    }

    public function testSetRequestMethodWithValidRequestMethod()
    {
        $request = new Request();

        $this->assertEquals(Request::METHOD_GET, $request->getRequestMethod());

        $request->setRequestMethod(Request::METHOD_POST);

        $this->assertEquals(Request::METHOD_POST, $request->getRequestMethod());
    }

    public function testSetRequestMethodWithChainingRequestMethod()
    {
        $request = new Request();

        $this->assertEquals(Request::METHOD_GET, $request->getRequestMethod());

        $request->setRequestMethod(Request::METHOD_POST);

        $this->assertEquals(Request::METHOD_POST, $request->getRequestMethod());

        $request->setRequestMethod(Request::METHOD_GET)->setRequestMethod(Request::METHOD_POST);

        $this->assertEquals(Request::METHOD_POST, $request->getRequestMethod());
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testIsValidTimeoutWithoutTimeout()
    {
        /** @noinspection PhpParamsInspection (this is intentional) */
        Request::isValidTimeout();
    }

    public function testIsValidTimeoutWithTimeout()
    {
        $validTimeouts = array(
            10,
            30,
            1,
            0xAE3,
            '15'
        );

        foreach ($validTimeouts as $timeout) {
            $this->assertTrue(Request::isValidTimeout($timeout));
        }

        $invalidTimeouts = array(
            0,
            -10,
            'foo',
            0.6,
        );

        foreach ($invalidTimeouts as $timeout) {
            $this->assertFalse(Request::isValidTimeout($timeout));
        }
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testSetTimeoutWithoutParameters()
    {
        $request = new Request();

        /** @noinspection PhpParamsInspection (this is intentional) */
        $request->setTimeout();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid timeout
     */
    public function testSetTimeoutWithInvalidTimeout()
    {
        $request = new Request();

        $request->setTimeout('invalidTimeout');
    }

    public function testSetTimeoutWithValidTimeout()
    {
        $request = new Request();

        $this->assertEquals(30, $request->getTimeout());

        $request->setTimeout(10);

        $this->assertEquals(10, $request->getTimeout());
    }

    public function testSetTimeoutWithChainingTimeout()
    {
        $request = new Request();

        $this->assertEquals(30, $request->getTimeout());

        $request->setTimeout(10);

        $this->assertEquals(10, $request->getTimeout());

        $request->setTimeout(15)->setTimeout(20);

        $this->assertEquals(20, $request->getTimeout());
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testAddQueryParameterWithoutParameters()
    {
        $request = new Request();

        /** @noinspection PhpParamsInspection (this is intentional) */
        $request->addQueryParameter();
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testAddQueryParameterWithInvalidParameter()
    {
        $request = new Request();

        $this->assertCount(0, $request->getQueryParameters());

        $request->addQueryParameter(array('foo'), 'bar');
    }

    public function testAddQueryParameterWithValidParameter()
    {
        $request = new Request();

        $this->assertCount(0, $request->getQueryParameters());

        $request->addQueryParameter('testParameter', 'testValue');

        $this->assertCount(1, $request->getQueryParameters());
        $this->assertArrayHasKey('testParameter', $request->getQueryParameters());
        $this->assertEquals('testValue', current($request->getQueryParameters()));
    }

    public function testAddQueryParameterWithArrayParameter()
    {
        $request = new Request();

        $this->assertCount(0, $request->getQueryParameters());

        $request->addQueryParameter('testParameter', array('testValue1', 'testValue2'));

        $this->assertCount(1, $request->getQueryParameters());
        $queryParameters = $request->getQueryParameters();

        $this->assertArrayHasKey('testParameter', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter']);
        $this->assertCount(2, $queryParameters['testParameter']);

        $this->assertEquals('testValue1', $queryParameters['testParameter'][0]);
        $this->assertEquals('testValue2', $queryParameters['testParameter'][1]);
    }

    public function testAddQueryParameterWithChaining()
    {
        $request = new Request();

        $this->assertCount(0, $request->getQueryParameters());

        $request
            ->addQueryParameter('testParameter1', array('testValue1', 'testValue2'))
            ->addQueryParameter('testParameter2', array('testValue3', 'testValue4'))
            ->addQueryParameter('testParameter3', array('testValue5', 'testValue6'))
            ->addQueryParameter('testParameter4', array('testValue7', 'testValue8'));

        $this->assertCount(4, $request->getQueryParameters());
        $queryParameters = $request->getQueryParameters();

        $this->assertArrayHasKey('testParameter1', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter1']);
        $this->assertCount(2, $queryParameters['testParameter1']);

        $this->assertEquals('testValue1', $queryParameters['testParameter1'][0]);
        $this->assertEquals('testValue2', $queryParameters['testParameter1'][1]);

        $this->assertArrayHasKey('testParameter2', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter2']);
        $this->assertCount(2, $queryParameters['testParameter2']);

        $this->assertEquals('testValue3', $queryParameters['testParameter2'][0]);
        $this->assertEquals('testValue4', $queryParameters['testParameter2'][1]);

        $this->assertArrayHasKey('testParameter3', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter3']);
        $this->assertCount(2, $queryParameters['testParameter3']);

        $this->assertEquals('testValue5', $queryParameters['testParameter3'][0]);
        $this->assertEquals('testValue6', $queryParameters['testParameter3'][1]);

        $this->assertArrayHasKey('testParameter4', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter4']);
        $this->assertCount(2, $queryParameters['testParameter4']);

        $this->assertEquals('testValue7', $queryParameters['testParameter4'][0]);
        $this->assertEquals('testValue8', $queryParameters['testParameter4'][1]);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testAddPostParameterWithoutParameters()
    {
        $request = new Request();

        /** @noinspection PhpParamsInspection (this is intentional) */
        $request->addPostParameter();
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testAddPostParameterWithInvalidParameter()
    {
        $request = new Request();

        $this->assertCount(0, $request->getPostParameters());

        $request->addPostParameter(array('foo'), 'bar');
    }

    public function testAddPostParameterWithValidParameter()
    {
        $request = new Request();

        $this->assertCount(0, $request->getPostParameters());

        $request->addPostParameter('testParameter', 'testValue');

        $this->assertCount(1, $request->getPostParameters());
        $this->assertArrayHasKey('testParameter', $request->getPostParameters());
        $this->assertEquals('testValue', current($request->getPostParameters()));
    }

    public function testAddPostParameterWithArrayParameter()
    {
        $request = new Request();

        $this->assertCount(0, $request->getPostParameters());

        $request->addPostParameter('testParameter', array('testValue1', 'testValue2'));

        $this->assertCount(1, $request->getPostParameters());
        $queryParameters = $request->getPostParameters();

        $this->assertArrayHasKey('testParameter', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter']);
        $this->assertCount(2, $queryParameters['testParameter']);

        $this->assertEquals('testValue1', $queryParameters['testParameter'][0]);
        $this->assertEquals('testValue2', $queryParameters['testParameter'][1]);
    }

    public function testAddPostParameterWithChaining()
    {
        $request = new Request();

        $this->assertCount(0, $request->getPostParameters());

        $request
            ->addPostParameter('testParameter1', array('testValue1', 'testValue2'))
            ->addPostParameter('testParameter2', array('testValue3', 'testValue4'))
            ->addPostParameter('testParameter3', array('testValue5', 'testValue6'))
            ->addPostParameter('testParameter4', array('testValue7', 'testValue8'));

        $this->assertCount(4, $request->getPostParameters());
        $queryParameters = $request->getPostParameters();

        $this->assertArrayHasKey('testParameter1', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter1']);
        $this->assertCount(2, $queryParameters['testParameter1']);

        $this->assertEquals('testValue1', $queryParameters['testParameter1'][0]);
        $this->assertEquals('testValue2', $queryParameters['testParameter1'][1]);

        $this->assertArrayHasKey('testParameter2', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter2']);
        $this->assertCount(2, $queryParameters['testParameter2']);

        $this->assertEquals('testValue3', $queryParameters['testParameter2'][0]);
        $this->assertEquals('testValue4', $queryParameters['testParameter2'][1]);

        $this->assertArrayHasKey('testParameter3', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter3']);
        $this->assertCount(2, $queryParameters['testParameter3']);

        $this->assertEquals('testValue5', $queryParameters['testParameter3'][0]);
        $this->assertEquals('testValue6', $queryParameters['testParameter3'][1]);

        $this->assertArrayHasKey('testParameter4', $queryParameters);
        $this->assertInternalType('array', $queryParameters['testParameter4']);
        $this->assertCount(2, $queryParameters['testParameter4']);

        $this->assertEquals('testValue7', $queryParameters['testParameter4'][0]);
        $this->assertEquals('testValue8', $queryParameters['testParameter4'][1]);
    }

    public function testBuildRequestUrl()
    {
        $request = new Request();

        // buildRequestUrl is a protected method, to test it we need to call it via reflection
        $reflectionRequest = new ReflectionObject($request);
        $reflectionMethod  = $reflectionRequest->getMethod('buildRequestUrl');
        $reflectionMethod->setAccessible(true);

        // since we haven't set any URL information yet, the full URL should be null
        $this->assertNull($reflectionMethod->invoke($request));

        // set some URL components
        $request
            ->setRequestUrl('http://www.example.com')
            ->addQueryParameter('foo', 'bar');

        $this->assertEquals('http://www.example.com?foo=bar', $reflectionMethod->invoke($request));

        $request->addQueryParameter('testQueryParameter', array('testValue1', 'testValue2'));

        $this->assertEquals('http://www.example.com?foo=bar&testQueryParameter%5B0%5D=testValue1&testQueryParameter%5B1%5D=testValue2', $reflectionMethod->invoke($request));
    }

    public function testSend()
    {
        $request = new MockHttpRequest('http://www.example.com');

        $response = $request->send();

        $this->assertInstanceOf('Capirussa\\Http\\Response', $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringStartsWith('<!doctype html>', $response->getRawBody());
        $this->assertContains('This domain is established to be used for illustrative examples in documents.', $response->getRawBody());
        $this->assertStringEndsWith('</html>', $response->getRawBody());
    }

    public function testGetLastResponse()
    {
        $request = new MockHttpRequest('http://www.example.com');

        $this->assertNull($request->getLastResponse());

        $response = $request->send();

        $this->assertInstanceOf('Capirussa\\Http\\Response', $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringStartsWith('<!doctype html>', $response->getRawBody());
        $this->assertContains('This domain is established to be used for illustrative examples in documents.', $response->getRawBody());
        $this->assertStringEndsWith('</html>', $response->getRawBody());

        $response = $request->getLastResponse();

        $this->assertInstanceOf('Capirussa\\Http\\Response', $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringStartsWith('<!doctype html>', $response->getRawBody());
        $this->assertContains('This domain is established to be used for illustrative examples in documents.', $response->getRawBody());
        $this->assertStringEndsWith('</html>', $response->getRawBody());
    }
}