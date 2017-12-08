<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\upload;


use eiu\components\Component;


/**
 * 上传组件
 *
 * @package eiu\components\upload
 */
class UploadComponent extends Component
{
    /**
     * File is too big by the user-defined max size
     */
    const UPLOAD_ERR_USER_SIZE = 9;
    
    /**
     * File is not allowed, per user-definition
     */
    const UPLOAD_ERR_NOT_ALLOWED = 10;
    
    /**
     * Upload directory does not exist
     */
    const UPLOAD_ERR_DIR_NOT_EXIST = 11;
    
    /**
     * Upload directory not writable
     */
    const UPLOAD_ERR_DIR_NOT_WRITABLE = 12;
    
    /**
     * Unexpected error
     */
    const UPLOAD_ERR_UNEXPECTED = 13;
    
    /**
     * Error messageed
     *
     * @var array
     */
    protected static $errorMessages = [
        0  => 'The file uploaded successfully',
        1  => 'The uploaded file exceeds the upload_max_filesize directive',
        2  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
        3  => 'The uploaded file was only partially uploaded',
        4  => 'No file was uploaded',
        6  => 'Missing a temporary folder',
        7  => 'Failed to write file to disk',
        8  => 'A PHP extension stopped the file upload',
        9  => 'The uploaded file exceeds the user-defined max file size',
        10 => 'The uploaded file is not allowed',
        11 => 'The specified upload directory does not exist',
        12 => 'The specified upload directory is not writable',
        13 => 'Unexpected error',
    ];
    
    /**
     * The upload directory path
     *
     * @var string
     */
    protected $uploadDir = null;
    
    /**
     * The final filename of the uploaded file
     *
     * @var string
     */
    protected $uploadedFile = null;
    
    /**
     * Allowed maximum file size
     *
     * @var int
     */
    protected $maxSize = 0;
    
    /**
     * Allowed file types
     *
     * @var array
     */
    protected $allowedTypes = [];
    
    /**
     * Disallowed file types
     *
     * @var array
     */
    protected $disallowedTypes = [];
    
    /**
     * Overwrite flag
     *
     * @var boolean
     */
    protected $overwrite = false;
    
    /**
     * Error flag
     *
     * @var int
     */
    protected $error = 0;
    
    /**
     * 采用默认设置
     *
     * @return UploadComponent
     */
    public function setDefaults()
    {
        // Allow basic text, graphic, audio/video, data and archive file types
        $allowedTypes = [
            'ai', 'aif', 'aiff', 'avi', 'bmp', 'bz2', 'csv', 'doc', 'docx', 'eps', 'fla', 'flv', 'gif', 'gz',
            'jpe', 'jpg', 'jpeg', 'log', 'md', 'mov', 'mp2', 'mp3', 'mp4', 'mpg', 'mpeg', 'otf', 'pdf',
            'png', 'ppt', 'pptx', 'psd', 'rar', 'svg', 'swf', 'tar', 'tbz', 'tbz2', 'tgz', 'tif', 'tiff', 'tsv',
            'ttf', 'txt', 'wav', 'wma', 'wmv', 'xls', 'xlsx', 'xml', 'zip',
        ];
        
        // Disallow programming/development file types
        $disallowedTypes = [
            'css', 'htm', 'html', 'js', 'json', 'pgsql', 'php', 'php3', 'php4', 'php5', 'sql', 'sqlite', 'yaml', 'yml',
        ];
        
        // Set max file size to 10 MBs
        $this->setMaxSize(10000000);
        $this->setAllowedTypes($allowedTypes);
        $this->setDisallowedTypes($disallowedTypes);
        
        return $this;
    }
    
    /**
     * 增加允许的类型
     *
     * @param  string $type
     *
     * @return UploadComponent
     */
    public function addAllowedType($type)
    {
        if (!in_array(strtolower($type), $this->allowedTypes))
        {
            $this->allowedTypes[] = strtolower($type);
        }
        
        return $this;
    }
    
    /**
     * 增加不允许的类型
     *
     * @param  string $type
     *
     * @return UploadComponent
     */
    public function addDisallowedType($type)
    {
        if (!in_array(strtolower($type), $this->disallowedTypes))
        {
            $this->disallowedTypes[] = strtolower($type);
        }
        
        return $this;
    }
    
    /**
     * 移除所有允许的类型
     *
     * @param  string $type
     *
     * @return UploadComponent
     */
    public function removeAllowedType($type)
    {
        if (in_array(strtolower($type), $this->allowedTypes))
        {
            unset($this->allowedTypes[array_search(strtolower($type), $this->allowedTypes)]);
        }
        
        return $this;
    }
    
    /**
     * 移除所有不允许的类型
     *
     * @param  string $type
     *
     * @return UploadComponent
     */
    public function removeDisallowedType($type)
    {
        if (in_array(strtolower($type), $this->disallowedTypes))
        {
            unset($this->disallowedTypes[array_search(strtolower($type), $this->disallowedTypes)]);
        }
        
        return $this;
    }
    
    /**
     * 设置复写标记
     *
     * @param  boolean $overwrite
     *
     * @return UploadComponent
     */
    public function overwrite($overwrite)
    {
        $this->overwrite = (bool)$overwrite;
        
        return $this;
    }
    
    /**
     * 获取上传目录
     *
     * @return string
     */
    public function getUploadDir()
    {
        return $this->uploadDir;
    }
    
    /**
     * 设置上传目录
     *
     * @param  string $dir
     *
     * @return UploadComponent
     */
    public function setUploadDir($dir)
    {
        // Check to see if the upload directory exists.
        if (!file_exists($dir) || !is_dir($dir))
        {
            $this->error = self::UPLOAD_ERR_DIR_NOT_EXIST;
            // Check to see if the permissions are set correctly.
        }
        else if (!is_writable($dir))
        {
            $this->error = self::UPLOAD_ERR_DIR_NOT_WRITABLE;
        }
        
        $this->uploadDir = $dir;
        
        return $this;
    }
    
    /**
     * 获取上传文件
     *
     * @return string
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }
    
    /**
     * 获取上传文件绝对路径
     *
     * @return string
     */
    public function getUploadedFullPath()
    {
        return $this->uploadDir . DIRECTORY_SEPARATOR . $this->uploadedFile;
    }
    
    /**
     * 获取文件大小上限
     *
     * @return int
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }
    
    /**
     * 设置文件大小上限
     *
     * @param  int $maxSize
     *
     * @return UploadComponent
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = (int)$maxSize;
        
        return $this;
    }
    
    /**
     * 获取不允许的文件类型
     *
     * @return array
     */
    public function getDisallowedTypes()
    {
        return $this->disallowedTypes;
    }
    
    /**
     * 设置不允许的类型
     *
     * @param  array $disallowedTypes
     *
     * @return UploadComponent
     */
    public function setDisallowedTypes(array $disallowedTypes)
    {
        foreach ($disallowedTypes as $type)
        {
            $this->addDisallowedType($type);
        }
        
        return $this;
    }
    
    /**
     * 获取允许的文件类型
     *
     * @return array
     */
    public function getAllowedTypes()
    {
        return $this->allowedTypes;
    }
    
    /**
     * 设置允许类型
     *
     * @param  array $allowedTypes
     *
     * @return UploadComponent
     */
    public function setAllowedTypes(array $allowedTypes)
    {
        foreach ($allowedTypes as $type)
        {
            $this->addAllowedType($type);
        }
        
        return $this;
    }
    
    /**
     * 判断指定文件类型是否不允许上传
     *
     * @param  string $ext
     *
     * @return boolean
     */
    public function isNotAllowed($ext)
    {
        $disallowed = ((count($this->disallowedTypes) > 0) && (in_array(strtolower($ext), $this->disallowedTypes)));
        $allowed    = ((count($this->allowedTypes) == 0) ||
                       ((count($this->allowedTypes) > 0) && (in_array(strtolower($ext), $this->allowedTypes))));
        
        return (($disallowed) && (!$allowed));
    }
    
    /**
     * 获取复写标记
     *
     * @return boolean
     */
    public function isOverwrite()
    {
        return $this->overwrite;
    }
    
    /**
     * 是否成功
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return ($this->error == UPLOAD_ERR_OK);
    }
    
    /**
     * 是否出错
     *
     * @return boolean
     */
    public function isError()
    {
        return ($this->error != UPLOAD_ERR_OK);
    }
    
    /**
     * 获取错误代码
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->error;
    }
    
    /**
     * 获取错误消息
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return self::$errorMessages[$this->error];
    }
    
    /**
     * 上传文件到上传目录并返回新上传的文件
     *
     * @param  array  $file
     * @param  string $to
     *
     * @return mixed
     */
    public function upload($file, $to = null)
    {
        if ($this->test($file))
        {
            if (null === $to)
            {
                $to = $file['name'];
            }
            if (!$this->overwrite)
            {
                $to = $this->checkFilename($to);
            }
            
            $this->uploadedFile = $to;
            $to                 = $this->uploadDir . DIRECTORY_SEPARATOR . $to;
            
            // Move the uploaded file, creating a file object with it.
            if (move_uploaded_file($file['tmp_name'], $to))
            {
                return $this->uploadedFile;
            }
            else
            {
                $this->error = self::UPLOAD_ERR_UNEXPECTED;
                
                return false;
            }
        }
        else
        {
            return false;
        }
        
    }
    
    /**
     * 在移动之前测试文件上传
     *
     * @param  array $file
     *
     * @return boolean
     */
    public function test($file)
    {
        if ($this->error != 0)
        {
            return false;
        }
        else
        {
            if (!isset($file['error']) || !isset($file['size']) || !isset($file['tmp_name']) || !isset($file['name']))
            {
                return false;
            }
            else
            {
                $this->error = $file['error'];
                if ($this->error != 0)
                {
                    return false;
                }
                else
                {
                    $fileSize  = $file['size'];
                    $fileParts = pathinfo($file['name']);
                    $ext       = (isset($fileParts['extension'])) ? $fileParts['extension'] : null;
                    
                    if (($this->maxSize > 0) && ($fileSize > $this->maxSize))
                    {
                        $this->error = self::UPLOAD_ERR_USER_SIZE;
                        
                        return false;
                    }
                    else if ((null !== $ext) && (!$this->isAllowed($ext)))
                    {
                        $this->error = self::UPLOAD_ERR_NOT_ALLOWED;
                        
                        return false;
                    }
                    else if ($this->error == 0)
                    {
                        return true;
                    }
                    else
                    {
                        $this->error = self::UPLOAD_ERR_UNEXPECTED;
                        
                        return false;
                    }
                }
            }
        }
    }
    
    /**
     * 判断指定文件类型是否允许上传
     *
     * @param  string $ext
     *
     * @return boolean
     */
    public function isAllowed($ext)
    {
        $disallowed = ((count($this->disallowedTypes) > 0) && (in_array(strtolower($ext), $this->disallowedTypes)));
        $allowed    = ((count($this->allowedTypes) == 0) ||
                       ((count($this->allowedTypes) > 0) && (in_array(strtolower($ext), $this->allowedTypes))));
        
        return ((!$disallowed) && ($allowed));
    }
    
    /**
     * 检查文件名是否重复并返回一个附加的新文件名
     *
     * @param  string $file
     *
     * @return string
     */
    public function checkFilename($file)
    {
        $newFilename  = $file;
        $parts        = pathinfo($file);
        $origFilename = $parts['filename'];
        $ext          = (isset($parts['extension']) && ($parts['extension'] != '')) ? '.' . $parts['extension'] : null;
        
        $i = 1;
        
        while (file_exists($this->uploadDir . DIRECTORY_SEPARATOR . $newFilename))
        {
            $newFilename = $origFilename . '_' . $i . $ext;
            $i++;
        }
        
        return $newFilename;
    }
}