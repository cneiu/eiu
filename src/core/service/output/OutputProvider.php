<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\output;


use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\Provider;


/**
 * Class EventProvider
 *
 * @package eiu\core\service\event
 */
class OutputProvider extends Provider
{
    /**
     * @var LoggerProvider
     */
    private $logger;
    
    
    private $_outputs  = [];
    private $_headers  = [];
    private $_zlib_oc  = false;
    private $_compress = false;
    
    /**
     * 是否已渲染
     *
     * @var bool
     */
    private $_isRendered = false;
    
    private $_content_type = 'text/html';
    
    /**
     * 服务注册
     */
    public function register()
    {
        $this->app->instance($this->alias(), $this);
        $this->app->instance(__CLASS__, $this);
    }
    
    /**
     * 服务启动
     *
     * @param ConfigProvider        $config
     * @param Logger|LoggerProvider $logger
     */
    public function boot(ConfigProvider $config, LoggerProvider $logger)
    {
        // 输出压缩
        $this->_zlib_oc  = @ini_get('zlib.output_compression');
        $this->_compress = $config['app']['OUTPUT_COMPRESS'];
        
        $this->logger = $logger;
        $this->logger->info($this->className() . " is booted");
    }
    
    /**
     * 设置输出
     *
     * 追加一个文本到输出缓冲器
     *
     * @param string $text 文本
     */
    public function setOutput($text)
    {
        array_push($this->_outputs, $text);
    }
    
    /**
     * 获取输出
     *
     * 获取输出缓冲区内容
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->_outputs;
    }
    
    /**
     * 输出显示
     *
     * 立即输出显示文本
     *
     * @param string $text     文不能
     * @param bool   $compress 是否压缩
     */
    public function render(string $text = null, bool $compress = true)
    {
        ob_start();
        // compress output
        if ($this->_compress === true and $this->_zlib_oc == false)
        {
            if (extension_loaded('zlib'))
            {
                if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) and strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
                {
                    (function_exists('ob_gzhandler') and $compress === true) ? ob_start('ob_gzhandler') : ob_start();
                }
            }
        }
        
        // set header
        if ($this->_headers)
        {
            foreach ($this->_headers as $header)
            {
                header($header[0], $header[1]);
            }
        }
        
        $this->_isRendered = true;
        
        echo $text ?: implode('', $this->_outputs);
    }
    
    /**
     * 是否已渲染
     *
     * @return bool
     */
    public function isRendered()
    {
        return $this->_isRendered;
    }
    
    /**
     * 设置输出头状态
     *
     * 设置输出头返回状态代码
     *
     * @param integer $code 状态代码, 默认: 200
     * @param string  $text 状态文本
     */
    public function setHeaderStatus(int $code = 200, string $text = '')
    {
        $state = [//
                  200 => 'OK',                                   //
                  201 => 'Created',                                   //
                  202 => 'Accepted',                                   //
                  203 => 'Non-Authoritative Information',                                   //
                  204 => 'No Content',                                   //
                  205 => 'Reset Content',                                   //
                  206 => 'Partial Content',                                   //
                  300 => 'Multiple Choices',                                   //
                  301 => 'Moved Permanently',                                   //
                  302 => 'Found',                                   //
                  304 => 'Not Modified',                                   //
                  305 => 'Use Proxy',                                   //
                  307 => 'Temporary Redirect',                                   //
                  400 => 'Bad Request',                                   //
                  401 => 'Unauthorized',                                   //
                  403 => 'Forbidden',                                   //
                  404 => 'Not Found',                                   //
                  405 => 'Method Not Allowed',                                   //
                  406 => 'Not Acceptable',                                   //
                  407 => 'Proxy Authentication Required',                                   //
                  408 => 'Request Timeout',                                   //
                  409 => 'Conflict',                                   //
                  410 => 'Gone',                                   //
                  411 => 'Length Required',                                   //
                  412 => 'Precondition Failed',                                   //
                  413 => 'Request Entity Too Large',                                   //
                  414 => 'Request-URI Too Long',                                   //
                  415 => 'Unsupported Media Type',                                   //
                  416 => 'Requested Range Not Satisfiable',                                   //
                  417 => 'Expectation Failed',                                   //
                  500 => 'Internal Server Error',                                   //
                  501 => 'Not Implemented',                                   //
                  502 => 'Bad Gateway',                                   //
                  503 => 'Service Unavailable',                                   //
                  504 => 'Gateway Timeout',                                   //
                  505 => 'HTTP Version Not Supported',
        
        ];
        
        $code     = (!$code or !array_key_exists($code, $state)) ? 200 : $code;
        $text     = $text ?: $state[$code];
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? false;
        
        if (substr(php_sapi_name(), 0, 3) == 'cgi')
        {
            $this->setHeader("Status: {$code} {$text}", true);
        }
        else if ($protocol == 'HTTP/1.1' or $protocol == 'HTTP/1.0')
        {
            $this->setHeader($protocol . " {$code} {$text}", true);
        }
        else
        {
            $this->setHeader("HTTP/1.1 {$code} {$text}", true);
        }
    }
    
    /**
     * 设置输出头信息
     *
     * @param string $header  头信息
     * @param bool   $replace 是否替换现有同类头信息
     */
    public function setHeader(string $header, bool $replace = true)
    {
        $this->_headers[] = [$header, $replace];
    }
    
    /**
     * 设置输出头字符集
     *
     * set response header charset
     *
     * @param $charset string
     */
    public function setHeaderCharset($charset)
    {
        $content_type = $this->_content_type;
        
        $this->setHeader("Content-type:$content_type; charset={$charset}", true);
    }
    
    /**
     * 设置输出头类型
     *
     * @param string $mime_type 类型
     */
    public function setHeaderType(string $mime_type)
    {
        $mimes = [//
                  'hqx'   => 'application/mac-binhex40', //
                  'cpt'   => 'application/mac-compactpro', //
                  'csv'   => [
                      'text/x-comma-separated-values', //
                      'text/comma-separated-values', //
                      'application/octet-stream', //
                      'application/vnd.ms-excel', //
                      'application/x-csv', //
                      'text/x-csv', //
                      'text/csv', //
                      'application/csv', //
                      'application/excel', //
                      'application/vnd.msexcel',
                  ], //
                  'bin'   => 'application/macbinary', //
                  'dms'   => 'application/octet-stream', //
                  'lha'   => 'application/octet-stream', //
                  'lzh'   => 'application/octet-stream', //
                  'exe'   => ['application/octet-stream', 'application/x-msdownload'], //
                  'class' => 'application/octet-stream', //
                  'psd'   => 'application/x-photoshop', //
                  'so'    => 'application/octet-stream', //
                  'sea'   => 'application/octet-stream', //
                  'dll'   => 'application/octet-stream', //
                  'oda'   => 'application/oda', //
                  'pdf'   => ['application/pdf', 'application/x-download'], //
                  'ai'    => 'application/postscript', //
                  'eps'   => 'application/postscript', //
                  'ps'    => 'application/postscript', //
                  'smi'   => 'application/smil', //
                  'smil'  => 'application/smil', //
                  'mif'   => 'application/vnd.mif', //
                  'xls'   => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'], //
                  'ppt'   => ['application/powerpoint', 'application/vnd.ms-powerpoint'], //
                  'wbxml' => 'application/wbxml', //
                  'wmlc'  => 'application/wmlc', //
                  'dcr'   => 'application/x-director', //
                  'dir'   => 'application/x-director', //
                  'dxr'   => 'application/x-director', //
                  'dvi'   => 'application/x-dvi', //
                  'gtar'  => 'application/x-gtar', //
                  'gz'    => 'application/x-gzip', //
                  'php'   => 'application/x-httpd-php', //
                  'php4'  => 'application/x-httpd-php', //
                  'php3'  => 'application/x-httpd-php', //
                  'phtml' => 'application/x-httpd-php', //
                  'phps'  => 'application/x-httpd-php-source', //
                  'js'    => 'application/x-javascript', //
                  'swf'   => 'application/x-shockwave-flash', //
                  'sit'   => 'application/x-stuffit', //
                  'tar'   => 'application/x-tar', //
                  'tgz'   => ['application/x-tar', 'application/x-gzip-compressed'], //
                  'xhtml' => 'application/xhtml+xml', //
                  'xht'   => 'application/xhtml+xml', //
                  'zip'   => ['application/x-zip', 'application/zip', 'application/x-zip-compressed'], //
                  'mid'   => 'audio/midi', //
                  'midi'  => 'audio/midi', //
                  'mpga'  => 'audio/mpeg', //
                  'mp2'   => 'audio/mpeg', //
                  'mp3'   => ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'], //
                  'aif'   => 'audio/x-aiff', //
                  'aiff'  => 'audio/x-aiff', //
                  'aifc'  => 'audio/x-aiff', //
                  'ram'   => 'audio/x-pn-realaudio', //
                  'rm'    => 'audio/x-pn-realaudio', //
                  'rpm'   => 'audio/x-pn-realaudio-plugin', //
                  'ra'    => 'audio/x-realaudio', //
                  'rv'    => 'video/vnd.rn-realvideo', //
                  'wav'   => ['audio/x-wav', 'audio/wave', 'audio/wav'], //
                  'bmp'   => ['image/bmp', 'image/x-windows-bmp'], //
                  'gif'   => 'image/gif', //
                  'jpeg'  => ['image/jpeg', 'image/pjpeg'], //
                  'jpg'   => ['image/jpeg', 'image/pjpeg'], //
                  'jpe'   => ['image/jpeg', 'image/pjpeg'], //
                  'png'   => ['image/png', 'image/x-png'], //
                  'tiff'  => 'image/tiff', //
                  'tif'   => 'image/tiff', //
                  'css'   => 'text/css', //
                  'html'  => 'text/html', //
                  'htm'   => 'text/html', //
                  'shtml' => 'text/html', //
                  'txt'   => 'text/plain', //
                  'text'  => 'text/plain', //
                  'log'   => ['text/plain', 'text/x-log'], //
                  'rtx'   => 'text/richtext', //
                  'rtf'   => 'text/rtf', //
                  'xml'   => 'text/xml', //
                  'xsl'   => 'text/xml', //
                  'mpeg'  => 'video/mpeg', //
                  'mpg'   => 'video/mpeg', //
                  'mpe'   => 'video/mpeg', //
                  'qt'    => 'video/quicktime', //
                  'mov'   => 'video/quicktime', //
                  'avi'   => 'video/x-msvideo', //
                  'movie' => 'video/x-sgi-movie', //
                  'doc'   => 'application/msword', //
                  'docx'  => [
                      'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip',
                  ], //
                  'xlsx'  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],//
                  'word'  => ['application/msword', 'application/octet-stream'], //
                  'xl'    => 'application/excel', //
                  'eml'   => 'message/rfc822', //
                  'json'  => ['application/json', 'text/json'],
        ];
        
        if (strpos($mime_type, '/') === false)
        {
            $extension = ltrim($mime_type, '.');
            
            // Is this extension supported?
            if (isset($mimes[$extension]))
            {
                $mime_type =& $mimes[$extension];
                
                if (is_array($mime_type))
                {
                    $mime_type = current($mime_type);
                }
            }
        }
        
        $header              = 'Content-Type: ' . $mime_type;
        $this->_content_type = $mime_type;
        $this->_headers[]    = [$header, true];
    }
    
    /**
     * 设置输出头(文件)
     *
     * @param string $filename 文件
     */
    public function setHeaderFilename(string $filename)
    {
        $this->setHeader("Content-Disposition:attachment;filename=$filename");
    }
}