<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\security;


use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\Provider;
use Exception;


/**
 * Class EventProvider
 *
 * @package eiu\core\service\event
 */
class SecurityProvider extends Provider
{
    /**
     * @var LoggerProvider | Logger
     */
    private $logger;
    
    /**
     * @var ConfigProvider
     */
    private $config;
    /**
     * 过滤列表
     *
     * @var    array
     */
    private $filename_bad_chars = [//
                                   '../',                  //
                                   '<!--',                 //
                                   '-->',                  //
                                   '<',                    //
                                   '>',                    //
                                   "'",                    //
                                   '"',                    //
                                   '&',                    //
                                   '$',                    //
                                   '#',                    //
                                   '{',                    //
                                   '}',                    //
                                   
                                   
                                   '[',                    //
                                   ']',                    //
                                   '=',                    //
                                   ';',                    //
                                   '?',                    //
                                   '%20',                  //
                                   '%22', '%3c',           // <
                                   '%253c',                // <
                                   '%3e',                  // >
                                   '%0e',                  // >
                                   '%28',                  // (
                                   '%29',                  // )
                                   '%2528',                // (
                                   '%26',                  // &
                                   '%24',                  // $
                                   '%3f',                  // ?
                                   '%3b',                  // ;
                                   '%3d'                   // =
    ];
    
    /**
     * 字符集
     *
     * @var    string
     */
    private $charset = 'UTF-8';
    
    /**
     * XSS 哈希散列保护
     *
     * @var    string
     */
    private $_xss_hash;
    
    /**
     * 绝对禁止的字符串
     *
     * @var    array
     */
    private $_never_allowed_str = [//
                                   'document.cookie' => '[removed]',           //
                                   'document.write'  => '[removed]',           //
                                   '.parentNode'     => '[removed]',           //
                                   '.innerHTML'      => '[removed]',           //
                                   '-moz-binding'    => '[removed]',           //
                                   '<!--'            => '&lt;!--',             //
                                   '-->'             => '--&gt;',              //
                                   '<![CDATA['       => '&lt;![CDATA[',        //
                                   '<comment>'       => '&lt;comment&gt;',     //
                                   '<%'              => '&lt;&#37;',
    ];
    
    /**
     * List of never allowed regex replacements
     *
     * @var    array
     */
    private $_never_allowed_regex = [//
                                     'javascript\s*:',                                   //
                                     '(document|(document\.)?window)\.(location|on\w*)', //
                                     'expression\s*(\(|&\#40;)',                         // CSS and IE
                                     'vbscript\s*:',                                     // IE, surprise!
                                     'wscript\s*:',                                      // IE
                                     'jscript\s*:',                                      // IE
                                     'vbs\s*:',                                          // IE
                                     'Redirect\s+30\d', "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?",
    ];
    
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
        $this->config  = $config;
        $this->logger  = $logger;
        $this->charset = $this->config['app']['CHARSET'];
        
        $this->logger->info($this->className() . " is booted");
    }
    
    /**
     * XSS 过滤
     *
     * 过滤保护跨站提交脚本的输入数据
     *
     * @param    string|array $str      输入数据
     * @param    bool         $is_image 是否是图像
     *
     * @return    string
     */
    public function xss_clean($str, $is_image = false)
    {
        // Is the string an array?
        if (is_array($str))
        {
            while (list($key) = each($str))
            {
                $str[$key] = $this->xss_clean($str[$key]);
            }
            
            return $str;
        }
        
        // Remove Invisible Characters
        $str = is_null($str) ? '' : $str;
        $str = $str ? $this->remove_invisible_characters($str) : $str;
        
        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         */
        if (stripos($str, '%') !== false)
        {
            do
            {
                $oldstr = $str;
                $str    = rawurldecode($str);
                $str    = preg_replace_callback('#%(?:\s*[0-9a-f]){2,}#i', [$this, '_urldecodespaces'], $str);
            }
            while ($oldstr !== $str);
            
            unset($oldstr);
        }
        
        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         */
        $str = preg_replace_callback("/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si", [$this, '_convert_attribute'], $str);
        $str = preg_replace_callback('/<\w+.*/si', [$this, '_decode_entity'], $str);
        
        // Remove Invisible Characters Again!
        $str = $this->remove_invisible_characters($str);
        
        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja	vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on
         * large blocks of data, so we use str_replace.
         */
        $str = str_replace("\t", ' ', $str);
        
        // Capture converted string for later comparison
        $converted_string = $str;
        
        // Remove Strings that are never allowed
        $str = $this->_do_never_allowed($str);
        
        /*
         * Makes PHP tags safe
         *
         * Note: XML tags are inadvertently replaced too:
         *
         * <?xml
         *
         * But it doesn't seem to pose a problem.
         */
        if ($is_image === true)
        {
            // Images have a tendency to have the PHP short opening and
            // closing tags every so often so we skip those and only
            // do the long opening tags.
            $str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
        }
        else
        {
            $str = str_replace(['<?', '?' . '>'], ['&lt;?', '?&gt;'], $str);
        }
        
        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         */
        $words = [//
                  'javascript',           //
                  'expression',           //
                  'vbscript',             //
                  'jscript',              //
                  'wscript',              //
                  'vbs',                  //
                  'script',               //
                  'base64',               //
                  'applet',               //
                  'alert',                //
                  'document',             //
                  'write',                //
                  'cookie',               //
                  'window',               //å
                  'confirm',              //
                  'prompt',               //
                  'eval',
        ];
        
        foreach ($words as $word)
        {
            $word = implode('\s*', str_split($word)) . '\s*';
            
            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#(' . substr($word, 0, -3) . ')(\W)#is', [
                $this, '_compact_exploded_words',
            ], $str);
        }
        
        /*
         * Remove disallowed Javascript in links or img tags
         * We used to do some version comparisons and use of stripos(),
         * but it is dog slow compared to these simplified non-capturing
         * preg_match(), especially if the pattern exists in the string
         *
         * Note: It was reported that not only space characters, but all in
         * the following pattern can be parsed as separators between a tag name
         * and its attributes: [\d\s"\'`;,\/\=\(\x00\x0B\x09\x0C]
         * ... however,$this->remove_invisible_characters() above already strips the
         * hex-encoded ones, so we'll skip them below.
         */
        do
        {
            $original = $str;
            
            if (preg_match('/<a/i', $str))
            {
                $str = preg_replace_callback('#<a(?:rea)?[^a-z0-9>]+([^>]*?)(?:>|$)#si', [
                    $this, '_js_link_removal',
                ], $str);
            }
            
            if (preg_match('/<img/i', $str))
            {
                $str = preg_replace_callback('#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', [
                    $this, '_js_img_removal',
                ], $str);
            }
            
            if (preg_match('/script|xss/i', $str))
            {
                $str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
            }
        }
        while ($original !== $str);
        
        unset($original);
        
        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         */
        $pattern = '#' . '<((?<slash>/*\s*)((?<tagName>[a-z0-9]+)(?=[^a-z0-9]|$)|.+)' // tag start and name, followed by a non-tag character
                   . '[^\s\042\047a-z0-9>/=]*' // a valid attribute character immediately after the tag would count as a separator
                   // optional attributes
                   . '(?<attributes>(?:[\s\042\047/=]*' // non-attribute characters, excluding > (tag close) for obvious reasons
                   . '[^\s\042\047>/=]+' // attribute characters
                   // optional attribute-value
                   . '(?:\s*=' // attribute-value separator
                   . '(?:[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*))' // single, double or non-quoted value
                   . ')?' // end optional attribute-value group
                   . ')*)' // end optional attributes group
                   . '[^>]*)(?<closeTag>\>)?#isS';
        
        // Note: It would be nice to optimize this for speed, BUT
        //       only matching the naughty elements here results in
        //       false positives and in turn - vulnerabilities!
        
        do
        {
            $old_str = $str;
            $str     = preg_replace_callback($pattern, [$this, '_sanitize_naughty_html'], $str);
        }
        while ($old_str !== $str);
        
        unset($old_str);
        
        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed. Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example:	eval('some code')
         * Becomes:	eval&#40;'some code'&#41;
         */
        $str = preg_replace('#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', '\\1\\2&#40;\\3&#41;', $str);
        
        // Final clean up
        // This adds a bit of extra precaution in case
        // something got through the above filters
        $str = $this->_do_never_allowed($str);
        
        /*
         * Images are Handled in a Special Way
         * - Essentially, we want to know that after all of the character
         * conversion is done whether any unwanted, likely XSS, code was found.
         * If not, we return TRUE, as the image is clean.
         * However, if the string post-conversion does not matched the
         * string post-removal of XSS, then it fails, as there was unwanted XSS
         * code found and removed/changed during processing.
         */
        if ($is_image === true)
        {
            return ($str === $converted_string);
        }
        
        return $str;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * 删除不可见字符
     *
     * @param    string $str         输入字符串
     * @param    bool   $url_encoded 是否URL编码
     *
     * @return    string
     */
    public function remove_invisible_characters(string $str, bool $url_encoded = true)
    {
        $non_displayables = [];
        
        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded)
        {
            $non_displayables[] = '/%0[0-8bcef]/i';    // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/i';    // url encoded 16-31
            $non_displayables[] = '/%7f/i';    // url encoded 127
        }
        
        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';    // 00-08, 11, 12, 14-31, 127
        
        do
        {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        }
        while ($count);
        
        return $str;
    }
    
    /**
     * 删除绝对禁止字符
     *
     * @param    string
     *
     * @return    string
     */
    private function _do_never_allowed($str)
    {
        $str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);
        
        foreach ($this->_never_allowed_regex as $regex)
        {
            $str = preg_replace('#' . $regex . '#is', '[removed]', $str);
        }
        
        return $str;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * 安全文件名
     *
     * @param    string $str           文件名
     * @param    bool   $relative_path 是否保存路径
     *
     * @return    string
     */
    public function sanitize_filename(string $str, bool $relative_path = false)
    {
        $bad = $this->filename_bad_chars;
        
        if (!$relative_path)
        {
            $bad[] = './';
            $bad[] = '/';
        }
        
        $str = $this->remove_invisible_characters($str, false);
        
        do
        {
            $old = $str;
            $str = str_replace($bad, '', $str);
        }
        while ($old !== $str);
        
        return stripslashes($str);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * 剔除图像标签
     *
     * @param    string $str 输入数据
     *
     * @return    string
     */
    public function strip_image_tags(string $str)
    {
        return preg_replace([
            '#<img[\s/]+.*?src\s*=\s*(["\'])([^\\1]+?)\\1.*?\>#i', '#<img[\s/]+.*?src\s*=\s*?(([^\s"\'=<>`]+)).*?\>#i',
        ], '\\2', $str);
    }
    
    /**
     * 字符串混淆
     *
     * @param string $str 需要加密的字符串
     *
     * @return string
     */
    function encode(string $str)
    {
        $rand_char = [//
                      'a',         //
                      'b',         //
                      'c',         //
                      'd',         //
                      'e',         //
                      'f',         //
                      'g',         //
                      'h',         //
                      'i',         //
                      'j',         //
                      'k',         //
                      'l',         //
                      'm',         //
                      'n',         //
                      'o',         //
                      'p',         //
                      'q',         //
                      'r',         //
                      's',         //
                      't',         //
                      'u',         //
                      'v',         //
                      'w',         //
                      'x',         //
                      'y',         //
                      'z',         //
                      0,           //
                      1,           //
                      2,           //
                      3,           //
                      4,           //
                      5,           //
                      6,           //
                      7,           //
                      8,           //
                      9,
        ];
        
        $_temp = null;
        
        for ($i = 0; $i < strlen($str); $i++)
        {
            $_temp .= $str[$i] . $rand_char[array_rand($rand_char)];
        }
        
        return base64_encode($_temp);
    }
    
    /**
     * 字符串反混淆
     *
     * @param string $str 密文
     *
     * @return string
     */
    function decode(string $str)
    {
        $str = base64_decode($str);
        
        $_temp = null;
        
        for ($i = 0; $i < strlen($str); $i++)
        {
            $_temp .= $i % 2 ? '' : $str[$i];
        }
        
        return str_replace('_KEY_', '', $_temp);
    }
    
    //	/**
    //	 * 生成 CSRF 令牌
    //	 *
    //	 * @param string $key 密钥
    //	 *
    //	 * @return string
    //	 */
    //	public function create_csrf( string $key = 'default' ) {
    //		$csrf_token = "csrf_token_" . md5( $key );
    //
    //		if ( ! $this->get_session( $csrf_token ) ) {
    //			$this->set_session( $csrf_token, uniqid( rand(), true ) );
    //		}
    //
    //		return $this->get_session( $csrf_token );
    //	}
    //
    //	/**
    //	 * 验证 CSRF 令牌
    //	 *
    //	 * @param    string $value $value 令牌
    //	 * @param string    $key   密钥
    //	 *
    //	 * @return bool
    //	 */
    //	public function check_csrf( string $value, string $key = 'default' ) {
    //		$csrf_token = "csrf_token_" . md5( $key );
    //
    //		if ( ! $value or empty( $this->get_session( $csrf_token ) ) ) {
    //			return false;
    //		}
    //
    //		if ( $value != $this->get_session( $csrf_token ) ) {
    //			return false;
    //		}
    //
    //		$this->delete_session( $csrf_token );
    //
    //		return true;
    //	}
    
    /**
     * URL 移除解码空格
     *
     * @param    array $matches
     *
     * @return    string
     */
    private function _urldecodespaces($matches)
    {
        $input    = $matches[0];
        $nospaces = preg_replace('#\s+#', '', $input);
        
        return ($nospaces === $input) ? $input : rawurldecode($nospaces);
    }
    
    /**
     * 移除空白字符
     *
     * @param    array $matches
     *
     * @return    string
     */
    private function _compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
    }
    
    //	/**
    //	 * 创建令牌
    //	 *
    //	 * 创建一个访问令牌码
    //	 *
    //	 * @param integer $seconds 有效期(秒)
    //	 *
    //	 * @return string
    //	 */
    //	function create_token( int $seconds = 300 ) {
    //		// 产生服务器代码
    //		$date = date( 'H:i:s:m:d:Y' );
    //		$md5  = md5( rand() );
    //		$this->set_session( $md5, $date );
    //
    //		// 产生代码
    //		return $this->encode( $md5 . $date . $seconds );
    //	}
    //
    //	/**
    //	 * 检验令牌
    //	 *
    //	 * 检验令牌是否有效
    //	 *
    //	 * @param string $code 密文
    //	 *
    //	 * @return bool
    //	 */
    //	function check_token( string $code ) {
    //		if ( ! preg_match( '/^[a-zA-Z0-9\=]{144}$/', $code ) ) {
    //			return false;
    //		}
    //
    //		$code = $this->decode( $code );
    //		$md5  = substr( $code, 0, 32 );
    //		$date = substr( $code, 32, 19 );
    //
    //		if ( $this->get_session( $md5 ) != $date ) {
    //			return $this->delete_session( $md5 );
    //		}
    //
    //		$date = explode( ':', $date );
    //
    //		$startdate = mktime( $date[0], $date[1], $date[2], $date[3], $date[4], $date[5] );
    //		$enddate   = mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) );
    //
    //		return (bool) round( ( $enddate - $startdate ) ) < (int) substr( $code, 51 );
    //	}
    
    /**
     * 剔除控件字符串
     *
     * @param    array $matches
     *
     * @return    string
     */
    private function _sanitize_naughty_html($matches)
    {
        static $naughty_tags = [        //
                                        'alert',                    //
                                        'area',                     //
                                        'prompt',                   //
                                        'confirm',                  //
                                        'applet',                   //
                                        'audio',                    //
                                        'basefont',                 //
                                        'base',                     //
                                        'behavior',                 //
                                        'bgsound',                  //
                                        'blink',                    //
                                        'body',                     //
                                        'embed',                    //
                                        'expression',               //
                                        'form',                     //
                                        'frameset',                 //
                                        'frame',                    //
                                        'head',                     //
                                        'html',                     //
                                        'ilayer',                   //
                                        'iframe',                   //
                                        'input',                    //
                                        'button',                   //
                                        'select',                   //
                                        'isindex',                  //
                                        'layer',                    //
                                        'link',                     //
                                        'meta',                     //
                                        'keygen',                   //
                                        'object',                   //
                                        'plaintext',                //
                                        'style',                    //
                                        'script',                   //
                                        'textarea',                 //
                                        'title',                    //
                                        'math',                     //
                                        'video',                    //
                                        'svg',                      //
                                        'xml',                      //
                                        'xss',
        ];
        
        static $evil_attributes = [
            'on\w+', 'style', 'xmlns', 'formaction', 'form', 'xlink:href', 'FSCommand', 'seekSegmentTime',
        ];
        
        // First, escape unclosed tags
        if (empty($matches['closeTag']))
        {
            return '&lt;' . $matches[1];
        } // Is the element that we caught naughty? If so, escape it
        else if (in_array(strtolower($matches['tagName']), $naughty_tags, true))
        {
            return '&lt;' . $matches[1] . '&gt;';
        } // For other tags, see if their attributes are "evil" and strip those
        else if (isset($matches['attributes']))
        {
            // We'll store the already fitlered attributes here
            $attributes = [];
            
            // Attribute-catching pattern
            $attributes_pattern = '#' . '(?<name>[^\s\042\047>/=]+)' // attribute characters
                                  // optional attribute-value
                                  . '(?:\s*=(?<value>[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*)))' // attribute-value separator
                                  . '#i';
            
            // Blacklist pattern for evil attribute names
            $is_evil_pattern = '#^(' . implode('|', $evil_attributes) . ')$#i';
            
            // Each iteration filters a single attribute
            do
            {
                // Strip any non-alpha characters that may precede an attribute.
                // Browsers often parse these incorrectly and that has been a
                // of numerous XSS issues we've had.
                $matches['attributes'] = preg_replace('#^[^a-z]+#i', '', $matches['attributes']);
                
                // No (valid) attribute found? Discard everything else inside the tag
                if (!preg_match($attributes_pattern, $matches['attributes'], $attribute, PREG_OFFSET_CAPTURE))
                {
                    break;
                }
                
                // Is it indeed an "evil" attribute?
                // Or does it have an equals sign, but no value and not quoted? Strip that too! OR (trim($attribute['value'][0]) === '')
                $attributes[]          = preg_match($is_evil_pattern, $attribute['name'][0]) ? 'xss=removed' : $attribute[0][0];
                $matches['attributes'] = substr($matches['attributes'], $attribute[0][1] + strlen($attribute[0][0]));
            }
            while ($matches['attributes'] !== '');
            
            $attributes = empty($attributes) ? '' : ' ' . implode(' ', $attributes);
            
            return '<' . $matches['slash'] . $matches['tagName'] . $attributes . '>';
        }
        
        return $matches[0];
    }
    
    /**
     * 删除 JS 链接
     *
     * @used-by    CI_Security::xss_clean()
     *
     * @param    array $match
     *
     * @return    string
     */
    private function _js_link_removal($match)
    {
        return str_replace($match[1], preg_replace('#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|d\s*a\s*t\s*a\s*:)#si', '', $this->_filter_attributes($match[1])), $match[0]);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * 过滤属性
     *
     * @param    string $str
     *
     * @return    string
     */
    private function _filter_attributes($str)
    {
        $out = '';
        
        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
        {
            foreach ($matches[0] as $match)
            {
                $out .= preg_replace('#/\*.*?\*/#s', '', $match);
            }
        }
        
        return $out;
    }
    
    /**
     * 删除 JS 图像
     *
     * @param    array $match
     *
     * @return    string
     */
    private function _js_img_removal($match)
    {
        return str_replace($match[1], preg_replace('#src=.*?(?:(?:alert|prompt|confirm|eval)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si', '', $this->_filter_attributes($match[1])), $match[0]);
    }
    
    /**
     * 属性转换
     *
     * @param    array $match
     *
     * @return    string
     */
    private function _convert_attribute($match)
    {
        return str_replace(['>', '<', '\\'], ['&gt;', '&lt;', '\\\\'], $match[0]);
    }
    
    /**
     * HTML 实体解码
     *
     * @param    array $match
     *
     * @return    string
     */
    private function _decode_entity($match)
    {
        // Protect GET variables in URLs
        // 901119URL5918AMP18930PROTECT8198
        $match = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i', $this->xssHash() . '\\1=\\2', $match[0]);
        
        // Decode, then un-protect URL GET vars
        return str_replace($this->xssHash(), '&', $this->entityDecode($match, $this->charset));
    }
    
    /**
     * XSS 生成随机哈希散列保护
     *
     * @return    string
     */
    public function xssHash()
    {
        if ($this->_xss_hash === null)
        {
            $rand            = $this->getRandomBytes(16);
            $this->_xss_hash = ($rand === false) ? md5(uniqid(mt_rand(), true)) : bin2hex($rand);
        }
        
        return $this->_xss_hash;
    }
    
    /**
     * 获取随机字节
     *
     * @param    int $length 输出长度
     *
     * @return    string
     */
    public function getRandomBytes(int $length)
    {
        if (empty($length) OR !ctype_digit((string)$length))
        {
            return false;
        }
        
        if (function_exists('random_bytes'))
        {
            try
            {
                // The cast is required to avoid TypeError
                return random_bytes((int)$length);
            }
            catch (Exception $e)
            {
                // If random_bytes() can't do the job, we can't either ...
                // There's no point in using fallbacks.
                $this->logger->error($e->getMessage());
                
                return false;
            }
        }
        
        // Unfortunately, none of the following PRNGs is guaranteed to exist ...
        if (defined('MCRYPT_DEV_URANDOM') and ($output = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM)) !== false)
        {
            return $output;
        }
        
        
        if (is_readable('/dev/urandom') and ($fp = fopen('/dev/urandom', 'rb')) !== false)
        {
            // Try not to waste entropy ...
            stream_set_chunk_size($fp, $length);
            $output = fread($fp, $length);
            fclose($fp);
            
            if ($output !== false)
            {
                return $output;
            }
        }
        
        if (function_exists('openssl_random_pseudo_bytes'))
        {
            return openssl_random_pseudo_bytes($length);
        }
        
        return false;
    }
    
    /**
     * HTML 实体编码
     *
     * @param    string $str     输入字符串
     * @param    string $charset 字符集
     *
     * @return    string
     */
    public function entityDecode(string $str, string $charset = null)
    {
        if (strpos($str, '&') === false)
        {
            return $str;
        }
        
        static $_entities;
        
        isset($charset) OR $charset = $this->charset;
        isset($_entities) OR $_entities = array_map('strtolower', get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML5));
        
        do
        {
            $str_compare = $str;
            
            // Decode standard entities, avoiding false positives
            if (preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches))
            {
                $replace = [];
                $matches = array_unique(array_map('strtolower', $matches[0]));
                
                foreach ($matches as &$match)
                {
                    if (($char = array_search($match . ';', $_entities, true)) !== false)
                    {
                        $replace[$match] = $char;
                    }
                }
                
                $str = str_replace(array_keys($replace), array_values($replace), $str);
            }
            
            // Decode numeric & UTF16 two byte entities
            $str = html_entity_decode(preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str), ENT_COMPAT | ENT_HTML5, $charset);
        }
        while ($str_compare !== $str);
        
        return $str;
    }
}