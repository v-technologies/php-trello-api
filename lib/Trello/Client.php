<?php

namespace Trello;

/**
 * Simple PHP Trello client
 *
 * @author Kevin Saliou <kevin at saliou dot name>
 * Website: http://github.com/kbsali/php-redmine-api
 */
class Client {
    /**
     * @var array
     */
    private static $defaultPorts = array(
        'http'  => 80,
        'https' => 443,
    );

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $apikey;

    /**
     * @var boolean
     */
    private $checkSslCertificate = false;

    /**
     * @var array APIs
     */
    private $apis = array();

    /**
     * @param string $url
     * @param string $apikey
     */
    public function __construct($url, $apikey)
    {
        $this->url    = $url;
        $this->apikey = $apikey;
    }

    /**
     * @param  string                    $name
     * @return Api\AbstractApi
     * @throws \InvalidArgumentException
     */
    public function api($name)
    {
        if (!isset($this->apis[$name])) {
            switch ($name) {
                // case 'actions':
                //     $api = new Api\Actions($this);
                //     break;
                case 'boards':
                    $api = new Api\Boards($this);
                    break;
                case 'cards':
                    $api = new Api\Cards($this);
                    break;
                // case 'checklists':
                //     $api = new Api\Checklists($this);
                //     break;
                // case 'lists':
                //     $api = new Api\Lists($this);
                //     break;
                case 'members':
                    $api = new Api\Members($this);
                    break;
                // case 'notifications':
                //     $api = new Api\Notifications($this);
                //     break;
                // case 'organizations':
                //     $api = new Api\Organizations($this);
                //     break;
                // case 'search':
                //     $api = new Api\Search($this);
                //     break;
                // case 'tokens':
                //     $api = new Api\Tokens($this);
                //     break;
                // case 'types':
                //     $api = new Api\Types($this);
                //     break;
                default:
                    throw new \InvalidArgumentException();
            }

            $this->apis[$name] = $api;
        }

        return $this->apis[$name];
    }

    /**
     * HTTP GETs a json $path and tries to decode it
     * @param  string $path
     * @return array
     */
    public function get($path)
    {
        if (false === $json = $this->runRequest($path, 'GET')) {
            return false;
        }

        return json_decode($json, true);
    }

    /**
     * HTTP POSTs $params to $path
     * @param  string $path
     * @param  string $data
     * @return mixed
     */
    public function post($path, $data)
    {
        return $this->runRequest($path, 'POST', $data);
    }

    /**
     * HTTP PUTs $params to $path
     * @param  string $path
     * @param  string $data
     * @return array
     */
    public function put($path, $data)
    {
        return $this->runRequest($path, 'PUT', $data);
    }

    /**
     * HTTP PUTs $params to $path
     * @param  string $path
     * @return array
     */
    public function delete($path)
    {
        return $this->runRequest($path, 'DELETE');
    }

    /**
     * Turns on/off ssl certificate check
     * @param boolean $check
     */
    public function setCheckSslCertificate($check = false)
    {
        $this->checkSslCertificate = $check;
    }

    /**
     * Set the port of the connection
     * @param int $port
     */
    public function setPort($port = null)
    {
        if (null !== $port) {
            $this->port = (int) $port;
        }
    }

    /**
     * Returns the port of the current connection,
     * if not set, it will try to guess the port
     * from the given $urlPath
     * @param  string $urlPath the url called
     * @return int
     */
    public function getPort($urlPath = null)
    {
        if (null === $urlPath) {
            return $this->port;
        }
        if (null !== $this->port) {
            return $this->port;
        }
        $tmp = parse_url($urlPath);

        if (isset($tmp['port'])) {
            $this->setPort($tmp['port']);

            return $this->port;
        }
        $this->setPort(self::$defaultPorts[$tmp['scheme']]);

        return $this->port;
    }

    /**
     * @param  string                        $path
     * @param  string                        $method
     * @param  string                        $data
     * @return false|SimpleXMLElement|string
     * @throws \Exception                    If anything goes wrong on curl request
     */
    private function runRequest($path, $method = 'GET', $data = '')
    {
        $this->getPort($this->url.$path);

        $curl = curl_init();
        if (isset($this->apikey)) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->apikey.':'.rand(100000, 199999) );
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        curl_setopt($curl, CURLOPT_URL, $this->url.$path);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PORT , $this->port);
        if (80 !== $this->port) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->checkSslCertificate);
        }

        $tmp = parse_url($this->url.$path);
        if ('xml' === substr($tmp['path'], -3)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: text/xml',
            ));
        }
        if ('json' === substr($tmp['path'], -4)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
            ));
        }

        if ('/uploads.json' === $path || '/uploads.xml' === $path) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/octet-stream',
            ));
        }

        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                if (isset($data)) {curl_setopt($curl, CURLOPT_POSTFIELDS, $data);}
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (isset($data)) {curl_setopt($curl, CURLOPT_POSTFIELDS, $data);}
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default: // GET
                break;
        }
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $e = new \Exception(curl_error($curl), curl_errno($curl));
            curl_close($curl);
            throw $e;
        }
        curl_close($curl);

        if ($response) {
            if ('<' === substr($response, 0, 1)) {
                return new \SimpleXMLElement($response);
            }

            return $response;
        }

        return true;
    }
}
