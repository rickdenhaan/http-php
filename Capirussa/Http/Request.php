<?php
namespace Capirussa\Http;

/**
 * The Request object is used to submit an HTTP or HTTPS request
 *
 * @package Capirussa\Http
 */
class Request
{
    /**
     * Indicates that a GET request should be submitted
     */
    const METHOD_GET = 'GET';

    /**
     * Indicates that a POST request should be submitted
     */
    const METHOD_POST = 'POST';

    /**
     * This property contains the URL to request.
     *
     * @type string
     */
    protected $requestUrl;

    /**
     * This property contains the request method to use for this request, must be one of the methods defined in the
     * constants.
     *
     * @type string
     */
    protected $requestMethod = self::METHOD_GET;

    /**
     * This property contains an array of data that should be appended to the URL in its query string.
     *
     * @type mixed[]
     */
    protected $queryParameters = array();

    /**
     * This property contains an array of data that should be posted with the request, if it is a POST request.
     *
     * @type mixed[]
     */
    protected $postParameters = array();

    /**
     * This property contains the request timeout in seconds. Defaults to 30 seconds.
     *
     * @type int
     */
    protected $timeout = 30;

    /**
     * This property is a boolean indicating whether the SSL certificate for the remote server should be validated.
     * Defaults to `true`, I recommend you keep it that way.
     *
     * @type bool
     */
    protected $validateSsl = true;

    /**
     * This property will contain the response to this request after it has been submitted.
     *
     * @type Response
     */
    protected $response;

    /**
     * The constructor can be used to quickly instantiate a Request with a URL and request method. In some
     * circumstances it may be necessary to disable SSL verification on the response. Usually when your server is not
     * properly configured, but this can also happen if the remote server forgets to renew their SSL certificates and
     * is working with old ones. If you ever need to (**which is not recommended!**), you can use the third argument of
     * the constructor to disable SSL verification. The constructor accepts three optional arguments:
     *
     * * The request url, which must be a string. If not given, you must use `setRequestUrl()` to set it
     * * The request method, which must be one of the methods defined in this class. Defaults to `Request::METHOD_GET`
     * * A boolean flag which indicates whether or not to validate the remote SSL certificate, defaults to `true`
     *
     * <code>
     * $request = new Request();
     * $request = new Request('http://www.example.com', Request::METHOD_POST, false);
     * </code>
     *
     * @param string $requestUrl    (Optional) Defaults to null
     * @param string $requestMethod (Optional) Defaults to self::METHOD_GET
     * @param bool   $validateSsl   (Optional) Defaults to true, only set to false for debugging!
     */
    public function __construct($requestUrl = null, $requestMethod = self::METHOD_GET, $validateSsl = true)
    {
        // if a request URL was given, set it
        if ($requestUrl !== null) {
            $this->setRequestUrl($requestUrl);
        }

        // if a request method was given, set it
        if ($requestMethod !== null) {
            $this->setRequestMethod($requestMethod);
        }

        $this->validateSsl = $validateSsl;
    }

    /**
     * This method is used to set the request URL for this request. It accepts one argument, which must be a string.
     * The method returns the current Request instance for easy chaining.
     *
     * <code>
     * $request = new Request();
     * $request->setRequestUrl('http://www.example.com');
     * $request->setRequestUrl('http://www.example.com/example')->setRequestMethod(Request::METHOD_POST);
     * </code>
     *
     * @param string $requestUrl
     * @throws \InvalidArgumentException
     * @return static
     */
    public function setRequestUrl($requestUrl)
    {
        // validate the URL
        if (!self::isValidRequestUrl($requestUrl)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%1$s: Invalid request URL \'%2$s\' given',
                    __METHOD__,
                    $requestUrl
                )
            );
        }

        $this->requestUrl = $requestUrl;

        return $this;
    }

    /**
     * Returns the current request URL as a string, or null if one is not yet set.
     *
     * <code>
     * $requestUrl = $request->getRequestUrl();
     * </code>
     *
     * @return string|null
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * This method is used to set the request method for this request. It accepts one argument, which must be one of
     * the request methods defined in this class. The method returns the current Request instance for easy chaining.
     *
     * <code>
     * $request = new Request();
     * $request->setRequestMethod(Request::METHOD_GET);
     * $request->setRequestMethod(Request::METHOD_GET)->setRequestUrl('http://www.example.com');
     * </code>
     *
     * @param string $requestMethod
     * @throws \InvalidArgumentException
     * @return static
     */
    public function setRequestMethod($requestMethod)
    {
        // validate the request method by checking whether it is defined as a constant in this class
        if (!self::isValidRequestMethod($requestMethod)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%1$s: Invalid request method \'%2$s\' given',
                    __METHOD__,
                    $requestMethod
                )
            );
        }

        $this->requestMethod = $requestMethod;

        return $this;
    }

    /**
     * Returns the current request method as a string.
     *
     * <code>
     * $requestMethod = $request->getRequestMethod();
     * </code>
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * This method is used to set the request timeout for this request. It accepts one argument, which must be an
     * integer. The method returns the current Request instance for easy chaining.
     *
     * <code>
     * $request = new Request();
     * $request->setTimeout(10);
     * $request->setTimeout(10)->setRequestUrl('http://www.example.com');
     * </code>
     *
     * @param int $timeout
     * @throws \InvalidArgumentException
     * @return static
     */
    public function setTimeout($timeout)
    {
        // validate the timeout by checking whether it is a valid integer value
        if (!self::isValidTimeout($timeout)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%1$s: Invalid timeout \'%2$s\' given',
                    __METHOD__,
                    $timeout
                )
            );
        }

        $this->timeout = intval($timeout);

        return $this;
    }

    /**
     * Returns the current request timeout as an integer.
     *
     * <code>
     * $timeout = $request->getTimeout();
     * </code>
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * This method is used to set data that should be appended to the URL's query string. The method accepts two
     * arguments:
     *
     * * The parameter that is being set, which must be valid for use as an array index (string or integer)
     * * The value that is being set, which can be anything
     *
     * The method returns the current Request instance for easy chaining.
     *
     * <code>
     * $request->addQueryParameter('key1', 'value');
     * $request->addQueryParameter('key2', array('value 1', 'value 2'));
     * $request->addQueryParameter('key3', 'value')->addQueryParameter('key4', 'value');
     * </code>
     *
     * @param string $parameter
     * @param mixed  $value
     * @return static
     */
    public function addQueryParameter($parameter, $value)
    {
        $this->queryParameters[$parameter] = $value;

        return $this;
    }

    /**
     * This method returns an array of all query parameters that have been added to this request.
     *
     * <code>
     * $queryParameters = $request->getQueryParameters();
     * </code>
     *
     * @return mixed[]
     */
    public function getQueryParameters()
    {
        return $this->queryParameters;
    }

    /**
     * This method is used to set data that should be submitted in a POST request. Note that this method does not
     * change the Request's request method, and any configured POST data is ignored if a GET method is used. The method
     * accepts two arguments:
     *
     * * The parameter that is being set, which must be valid for use as an array index (string or integer)
     * * The value that is being set, which can be anything
     *
     * The method returns the current Request instance for easy chaining.
     *
     * <code>
     * $request->addPostParameter('key1', 'value');
     * $request->addPostParameter('key2', array('value 1', 'value 2'));
     * $request->addPostParameter('key3', 'value')->setRequestMethod(Request::METHOD_POST);
     * </code>
     *
     * @param string $parameter
     * @param mixed  $value
     * @return static
     */
    public function addPostParameter($parameter, $value)
    {
        $this->postParameters[$parameter] = $value;

        return $this;
    }

    /**
     * This method returns an array of all post parameters that have been added to this request.
     *
     * <code>
     * $postParameters = $request->getPostParameters();
     * </code>
     *
     * @return mixed[]
     */
    public function getPostParameters()
    {
        return $this->postParameters;
    }

    /**
     * This method submits this request and returns a Response object containing the resulting response.
     *
     * <code>
     * $response = $request->send();
     * </code>
     *
     * @throws \Exception
     * @return Response
     *
     * Unittests should never talk to real remote servers, they should use a mock request, so:
     * @codeCoverageIgnore
     */
    public function send()
    {
        // build the request URL
        $requestUrl = $this->buildRequestUrl();

        // set up the CURL request options
        $curlOptions = array(
            CURLOPT_SSL_VERIFYPEER => $this->validateSsl,
            CURLOPT_SSL_VERIFYHOST => $this->validateSsl ? 2 : 0,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_USERAGENT      => 'Capirussa/0.1.2 (+http://github.com/rickdenhaan/http-php)'
        );

        // if this is a post request, tell CURL that
        if ($this->getRequestMethod() == self::METHOD_POST) {
            $curlOptions[CURLOPT_POST] = true;

            // check whether any post data was set
            if (count($this->getPostParameters()) > 0) {
                $curlOptions[CURLOPT_POSTFIELDS] = $this->getPostParameters();
            }
        }

        // initialize and configure the CURL request
        $curl = curl_init($requestUrl);
        curl_setopt_array(
            $curl,
            $curlOptions
        );

        // execute the CURL request
        $result = curl_exec($curl);

        // check whether the server threw a fit (would have nothing to do with the remote server, because we configured
        // the CURL request not to throw an error if the HTTP request fails)
        $error = curl_error($curl);
        if ($error != '') {
            throw new \Exception($error);
        }

        // close the CURL request
        curl_close($curl);

        // parse the response body and return the Response object
        $this->response = new Response($result);

        return $this->response;
    }

    /**
     * This method is used internally to build the full request URL by combining the base URL with any query parameters.
     *
     * <code>
     * $this->setRequestUrl('http://www.example.com')
     *      ->addQueryParameter('key1', 'value');
     *
     * $fullUrl = $this->buildRequestUrl();
     * </code>
     *
     * @return string
     */
    protected function buildRequestUrl()
    {
        $retValue = $this->getRequestUrl();

        $queryParameters = $this->getQueryParameters();

        if (count($queryParameters) > 0) {
            $retValue .= (strpos($retValue, '?') > 0 ? '&' : '?') . http_build_query($queryParameters);
        }

        return $retValue;
    }

    /**
     * This method returns the last response, in case this request is submitted multiple times or this instance is
     * reused for several requests. It returns either a Response object or null, if the request has not been submitted
     * yet.
     *
     * <code>
     * $response = $request->getLastResponse();
     * </code>
     *
     * @return Response
     */
    public function getLastResponse()
    {
        return $this->response;
    }

    /**
     * This static method validates whether a given value is one of the defined request methods.
     *
     * <code>
     * $isValid = Request::isValidRequestMethod(Request::METHOD_GET);
     * </code>
     *
     * @param string $requestMethod
     * @return bool
     */
    public static function isValidRequestMethod($requestMethod)
    {
        // validate the request method by checking whether it is defined as a constant in this class
        $reflectionClass  = new \ReflectionClass(get_class());
        $definedConstants = $reflectionClass->getConstants();

        $requestMethodIsValid = false;
        foreach ($definedConstants as $constantName => $constantValue) {
            if ($constantValue == $requestMethod && strlen($constantName) > 7 && strtoupper(substr($constantName, 0, 7)) == 'METHOD_') {
                $requestMethodIsValid = true;
                break;
            }
        }

        return $requestMethodIsValid;
    }

    /**
     * This static method validates whether a given value is a valid URL
     *
     * <code>
     * $isValid = Request::isValidRequestUrl('http://www.example.com');
     * </code>
     *
     * @param string $requestUrl
     * @return bool
     */
    public static function isValidRequestUrl($requestUrl)
    {
        // use PHP's built-in URL validation
        return (filter_var($requestUrl, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED) !== false);
    }

    /**
     * This static method validates whether a given value is a valid integer value for a request timeout
     *
     * <code>
     * $isValid = Request::isValidTimeout(20);
     * </code>
     *
     * @param int $timeout
     * @return bool
     */
    public static function isValidTimeout($timeout)
    {
        // get the integer value for the given timeout
        $timeout = intval($timeout);

        // the timeout must be greater than 0
        return ($timeout > 0);
    }
}