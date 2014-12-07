<?php
require_once(dirname(__FILE__) . '/../../../init.php');

use Capirussa\Http;

class MockHttpRequest extends Http\Request
{
    /**
     * Overrides the real request method to simulate a predefined response
     *
     * @return Http\Response
     */
    public function send()
    {
        // build the request URL
        $requestUrl = $this->buildRequestUrl();

        // read the mock file contents
        switch ($requestUrl) {
            case 'http://www.example.com/lowercase/content-type':
                $simulatedResponse = $this->loadMockResponse('mock_example_com_lcct.txt');
                break;

            case 'http://www.example.com/with/character-set':
                $simulatedResponse = $this->loadMockResponse('mock_example_com_wcs.txt');
                break;

            case 'http://www.example.com/with/empty-html':
                $simulatedResponse = $this->loadMockResponse('mock_example_com_weh.txt');
                break;

            case 'http://www.example.com/without/content-type':
                $simulatedResponse = $this->loadMockResponse('mock_example_com_nct.txt');
                break;

            default:
                $simulatedResponse = $this->loadMockResponse('mock_example_com.txt');
                break;
        }

        // the response should contain \r\n line endings, but Git sometimes screws that up
        if (!strpos($simulatedResponse, "\r\n")) {
            $simulatedResponse = str_replace(array("\r", "\n"), "\r\n", $simulatedResponse);
        }

        $this->response = new Http\Response($simulatedResponse);

        return $this->response;
    }

    private function loadMockResponse($filename)
    {
        return file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $filename);
    }
}