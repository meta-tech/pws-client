<?php
/*
 * This file is part of the pws-client package.
 *
 * (c) meta-tech.academy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MetaTech\Ws;

use MetaTech\Util\Tool;
use MetaTech\PwsAuth\Authenticator;
use MetaTech\Output\Formatter;

/*!
 * PwsAuth token
 * 
 * @package         MetaTech\Ws
 * @class           PwsClient
 * @author          a-Sansara
 * @date            2016-05-02 13:19:01 CET
 */
class Client
{
    /*! @constant QUIET */
    const QUIET         = 0;
    /*! @constant VERBOOSE */
    const VERBOOSE      = 1;
    /*! @constant MOST_VERBOOSE */
    const MOST_VERBOOSE = 2;
    /*! @constant LF */
    const LF            = "
";

    /*! @protected @var [assoc] $config */
    protected $config;

    /*! @protected @var MetaTech\Output\Formatter $formatter */
    protected $formatter;

    /*! @protected @var Mtc\Core\Auth\Authenticator $authenticator */
    protected $authenticator;

    /*!
     * desc
     * 
     * @constructor
     * @param       [assoc]                         $config
     * @param       MetaTech\PwsAuth\Authenticator  $authenticator
     * @public
     */
    public function __construct($config, Authenticator $authenticator)
    {
        if (!is_array($config)) {
            throw new \Exception('bad rest config');
        }
        $typeFormatter       = $this->config['html_output'] ? Formatter::TYPE_HTML : Formatter::TYPE_CLI;
        $this->formatter     = new Formatter($typeFormatter);
        $this->config        = $config;
        $this->authenticator = $authenticator;

        if ($this->config['debug'] && $this->config['debug'] == self::MOST_VERBOOSE) {
            $config['password'] = '--hidden--';
            $config['key']      = substr($config['key'], 0, 3) . '...--hidden--';
            if (!empty($config['http']['password'])) {
                $config['http']['password'] = '--hidden--';
            }
            $this->formatter->writeTags([
                [date('H:i:s')                        , '3'],
                [' [' . __class__ . ']'               , '1'],
                [' debug mode verboose, view config :', '2'],
                Formatter::LF,
                [var_export($config, true), null]
            ]);
        }
        if (!file_exists($config['store'])) {
            $this->_persist('#');
        }
        $resp = $this->check();
        if (is_null($resp) || !$resp->done) {
            $this->auth();
        }
    }

    /*!
     * call the authenticate web service
     *
     * @method      auth
     * @public
     * @return      object
     */
    public function auth()
    {
        $header   = $this->_buildHeader();
        $data     = Tool::compact($this->config, ['login', 'password']);
        $data     = $this->_send($this->config['uri']['auth'], $data, "POST", $header);
        $resp     = $data['response'];
        if ($resp!=null && $resp->done) {
            $this->_persist($resp->data->sid);
        }
        unset($resp->data->sid);
        return $resp;
    }

    /*!
     * call the logout web service
     * 
     * @method      logout
     * @public
     * @return
     */
    public function logout()
    {
        return $this->get($this->config['uri']['logout']);
    }

    /*!
     * call the check auth web service
     * 
     * @method      check
     * @public
     * @return
     */
    public function check()
    {
        return $this->get($this->config['uri']['check']);
    }

    /*!
     * call a web service denotes by $uri
     * 
     * @method      get
     * @public
     * @param       str     $uri   the web service uri
     * @param       []      $data  the post data
     * @return      object
     */
    public function get($uri)
    {
        $header = $this->_buildHeader($this->_getToken());
        $resp   = $this->_send($uri, null, 'GET', $header);
        return $resp['response'];
    }

    /*!
     * call a web service denotes by $uri with $data post
     * 
     * @method      post
     * @public
     * @param       str     $uri   the web service uri
     * @param       []      $data  the post data
     * @return      object
     */
    public function post($uri, $data=array(), $trace=false)
    {
        $header = $this->_buildHeader($this->_getToken());
        $resp   = $this->_send($uri, $data, 'POST', $header);
        return $resp['response'];
    }

    /*!
     * build header for authentication. if token is missing or null, the header is
     * specific to a login action, overwise the header is specific to a loading session
     * 
     * @method      _buildHeader
     * @private
     * @param       str     $login
     * @param       str     $key
     * @param       str     $sessid
     * @retval [str]
     */
    private function _buildHeader($sessid=null)
    {
        $header = $this->authenticator->generateHeader($this->config['login'], $this->config['key'], $sessid);
        return $header;
    }

    /*!
     * desc
     * 
     * @method      _persist
     * @private
     * @param       str     $token  the session token
     */
    private function _persist($token)
    {
        file_put_contents($this->config['store'], $token);
    }

    /*!
     * desc
     * 
     * @method      _getToken
     * @private
     * @return      str
     */
    private function _getToken()
    {
        return file_get_contents($this->config['store']);
    }

    /*!
     * desc
     * 
     * @method      _initCall
     * @private
     * @param       str     $uri    the web service uri
     * @return      $curl instance 
     */
    private function _initCall($uri, $method)
    {
        $curl = curl_init();
        $url  = $this->config['protocol'] . $this->config['hostname'] . $uri;
        if ($this->config['debug'] == self::MOST_VERBOOSE) {
            $this->formatter->writeTags([
                [date('H:i:s')         , '3'],
                [' [' . __class__ . ']', '1'],
                [" => $method $url"    , '2'],
                ''
            ]);
        }
        curl_setopt($curl, CURLOPT_URL           , $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER        , true);
        curl_setopt($curl, CURLOPT_COOKIESESSION , false);
        curl_setopt($curl, CURLOPT_USERAGENT     , $this->config['key']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->config['verifypeer']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->config['verifyhost']);

        if (isset($this->config['http']) && isset($this->config['http']['user']) && isset($this->config['http']['password'])) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($curl, CURLOPT_USERPWD , $this->config['http']['user'] . ':'. $this->config['http']['password']);
        }
        return $curl;
    }

    /*!
     * @method      _send
     * @private
     * @param       str         $uri       the web service uri to call
     * @param       []|null     $data      the post params
     * @param       str         $method    the webservice method 
     * @param       bool        $header    the header to send
     * @retval [assoc]
     */
    private function _send($uri, $data=null, $method="GET", $header=array())
    {
        try {
            $stime = microtime(true);
            $date  = Tool::dateFromTime($stime);
            $curl  = $this->_initCall($uri, $method);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST , $method);
            if ($method == "POST") {
                curl_setopt($curl, CURLOPT_POST, true);
                if ($data!=null && !empty($data)) {
                    $fields = http_build_query($data);
                    $header[] = 'Content-Length: ' . strlen($fields);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
                }
            }
            if (count($header) > 0) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            // curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, "HandleHeaderLine"));
            $rs         = curl_exec($curl);
            $exectime   = number_format(((microtime(true)-$stime)),5);
            $status     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $size       = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $respheader = substr($rs, 0, $size);
            $body       = substr($rs, $size);
            $response   = json_decode($body);
            $url        = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
            if ($status == 0) {
                throw new \Exception(curl_error($curl));
            }
            curl_close($curl);
        }
        catch(\Exception $e) {
            if (isset($curl)) {
                $status   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $url      = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
                $error    = curl_error($curl);
                $response = [ 'done' => false, 'msg' => ' CURL ERROR : '.$error.' - status : '.$status.' - url : '.$url ];
                curl_close($curl);
            }
        }
        if ($this->config['debug']) {
            $respcontent = null;
            if (is_object($response)) {
                $respcontent = clone $response;
                if (isset($respcontent->data) && $this->config['html_output']) {
                    if (is_string($respcontent->data)) {
                        $respcontent->data = htmlentities($respcontent->data);
                    }
                    elseif (isset($respcontent->data->html) && is_string($respcontent->data->html)) {
                        $respcontent->data = clone $response->data;
                        $respcontent->data->html = htmlentities($respcontent->data->html);
                    }
                }
            }
            $tags = [
                [date('H:i:s')                    , 3],
                [' ['. __class__ .']'             , 1],
                [($this->config['debug'] == self::MOST_VERBOOSE ? " <=" : "")." $method $url"               , 2],
                [" $exectime s "                  , 4],
                ['', null],
            ];
        }
        switch ($this->config['debug']) {
            
            case self::MOST_VERBOOSE :
                if (isset($data['password'])) {
                    $data['password'] = '--hidden--';
                }
                $traces = var_export([
                    'HEADER'   => $this->authenticator->readHeader($header), 
                    'PARAMS'   => $data, 
                    'METHOD'   => $method, 
                    'RESPONSE' => compact('date', 'uri', 'status') + ['curl' => $rs, 'response' => $respcontent]
                ], true) . Formatter::LF;
                array_unshift($tags, $traces);
                $this->formatter->writeTags($tags);
                break;

            case self::VERBOOSE      :
                array_unshift($tags, Formatter::LF);
                $tags[] = var_export(compact('status')+['response' => $respcontent], true);
                $this->formatter->writeTags($tags);
                break;
        }
        return compact('date', 'uri', 'response', 'status', 'exectime');
    }

}
