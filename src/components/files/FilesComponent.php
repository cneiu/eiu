<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\files;


use eiu\components\Component;
use ErrorException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


/**
 * 文件系统组件
 *
 * @package eiu\components\files
 */
class FilesComponent extends Component
{
    /**
     * 获取文件的md5哈希值
     *
     * @param  string $path
     *
     * @return string
     */
    public function hash($path)
    {
        return md5_file($path);
    }
    
    /**
     * 判断是否是文件
     *
     * @param  string $path
     *
     * @return bool
     */
    public function isFile($path)
    {
        return is_file($path);
    }
    
    /**
     * 判断路径是否存在
     *
     * @param  string $path
     *
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }
    
    /**
     * 写入文件内容
     *
     * @param  string $path
     * @param  string $contents
     * @param  bool   $lock
     *
     * @return int
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }
    
    /**
     * 获取文件内容
     *
     * @param  string $path
     * @param  bool   $lock
     *
     * @return string
     * @throws FilesException
     */
    public function get($path, $lock = false)
    {
        if (is_file($path))
        {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }
        
        throw new FilesException("File does not exist at path \"{$path}\"");
    }
    
    /**
     * 获取具有共享访问权限的文件的内容
     *
     * @param  string $path
     *
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';
        
        $handle = fopen($path, 'rb');
        
        if ($handle)
        {
            try
            {
                if (flock($handle, LOCK_SH))
                {
                    clearstatcache(true, $path);
                    
                    $contents = fread($handle, $this->size($path) ?: 1);
                    
                    flock($handle, LOCK_UN);
                }
            }
            finally
            {
                fclose($handle);
            }
        }
        
        return $contents;
    }
    
    /**
     * 获取文件大小
     *
     * @param  string $path
     *
     * @return int
     */
    public function size($path)
    {
        return filesize($path);
    }
    
    /**
     * 追加一个文件内容
     *
     * @param  string $path
     * @param  string $data
     *
     * @return int
     */
    public function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }
    
    /**
     * 获取或设置文件或目录的UNIX模式
     *
     * @param  string $path
     * @param  int    $mode
     *
     * @return mixed
     */
    public function chmod($path, $mode = null)
    {
        if ($mode)
        {
            return chmod($path, $mode);
        }
        
        return substr(sprintf('%o', fileperms($path)), -4);
    }
    
    /**
     * 移动文件到新目录
     *
     * @param  string $path
     * @param  string $target
     *
     * @return bool
     */
    public function move($path, $target)
    {
        return rename($path, $target);
    }
    
    /**
     * 创建一个指向目标文件或目录的硬链接
     *
     * @param  string $target
     * @param  string $link
     *
     * @return void
     */
    public function link($target, $link)
    {
        if (!windows_os())
        {
            return symlink($target, $link);
        }
        
        $mode = $this->isDirectory($target) ? 'J' : 'H';
        
        exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
    }
    
    /**
     * 判断是否是目录
     *
     * @param  string $directory
     *
     * @return bool
     */
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }
    
    /**
     * 获取文件名
     *
     * @param  string $path
     *
     * @return string
     */
    public function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }
    
    /**
     * 获取路径中文件名部分
     *
     * @param  string $path
     *
     * @return string
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }
    
    /**
     * 获取目录名
     *
     * @param  string $path
     *
     * @return string
     */
    public function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }
    
    /**
     * 获取文件扩展名
     *
     * @param  string $path
     *
     * @return string
     */
    public function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
    
    /**
     * 获取文件类型
     *
     * @param  string $path
     *
     * @return string
     */
    public function type($path)
    {
        return filetype($path);
    }
    
    /**
     * 获取文件MIME类型
     *
     * @param  string $path
     *
     * @return string|false
     */
    public function mimeType($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }
    
    /**
     * 获取最后修改时间
     *
     * @param  string $path
     *
     * @return int
     */
    public function lastModified($path)
    {
        return filemtime($path);
    }
    
    /**
     * 判断文件是否可读
     *
     * @param  string $path
     *
     * @return bool
     */
    public function isReadable($path)
    {
        return is_readable($path);
    }
    
    /**
     * 判断文件是否可写
     *
     * @param  string $path
     *
     * @return bool
     */
    public function isWritable($path)
    {
        return is_writable($path);
    }
    
    /**
     * 找到匹配给定模式的路径名
     *
     * @param  string $pattern
     * @param  int    $flags
     *
     * @return array
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }
    
    /**
     * 获取指定目录下的所有文件列表
     *
     * @param string $path
     * @param bool   $isRecursive
     *
     * @return array
     */
    public function files(string $path, bool $isRecursive = false): array
    {
        if (!$this->isDirectory($path))
        {
            return [];
        }
        
        $files = [];
        $iter  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        
        if (!$isRecursive)
        {
            $iter->setMaxDepth(0);
        }
        
        foreach ($iter as $path => $item)
        {
            if ($item->isFile())
            {
                $files[] = $path;
            }
        }
        
        return $files;
    }
    
    /**
     * 获取指定目录下的所有子目录
     *
     * @param string $path
     * @param bool   $isRecursive
     *
     * @return array
     *
     */
    public function directories(string $path, bool $isRecursive = false): array
    {
        if (!$this->isDirectory($path))
        {
            return [];
        }
        
        $directories = [];
        $iter        = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        
        if (!$isRecursive)
        {
            $iter->setMaxDepth(0);
        }
        
        foreach ($iter as $path => $item)
        {
            if ($item->isDir())
            {
                $directories[] = $path;
            }
        }
        
        return $directories;
    }
    
    /**
     * 移动一个目录
     *
     * @param  string $from
     * @param  string $to
     * @param  bool   $overwrite
     *
     * @return bool
     */
    public function moveDirectory($from, $to, $overwrite = false)
    {
        if ($overwrite && $this->isDirectory($to))
        {
            if (!$this->deleteDirectory($to))
            {
                return false;
            }
        }
        
        return @rename($from, $to) === true;
    }
    
    /**
     * 删除目录
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string $directory
     * @param  bool   $preserve
     *
     * @return bool
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        if (!$this->isDirectory($directory))
        {
            return false;
        }
        
        $items = new FilesystemIterator($directory);
        
        foreach ($items as $item)
        {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && !$item->isLink())
            {
                $this->deleteDirectory($item->getPathname());
            }
            
            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else
            {
                $this->delete($item->getPathname());
            }
        }
        
        if (!$preserve)
        {
            @rmdir($directory);
        }
        
        return true;
    }
    
    /**
     * 删除文件
     *
     * @param  string|array $paths
     *
     * @return bool
     */
    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();
        
        $success = true;
        
        foreach ($paths as $path)
        {
            try
            {
                if (!@unlink($path))
                {
                    $success = false;
                }
            }
            catch (ErrorException $e)
            {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * 拷贝目录
     *
     * @param  string $directory
     * @param  string $destination
     * @param  int    $options
     *
     * @return bool
     */
    public function copyDirectory($directory, $destination, $options = null)
    {
        if (!$this->isDirectory($directory))
        {
            return false;
        }
        
        $options = $options ?: FilesystemIterator::SKIP_DOTS;
        
        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        if (!$this->isDirectory($destination))
        {
            $this->makeDirectory($destination, 0777, true);
        }
        
        $items = new FilesystemIterator($directory, $options);
        
        foreach ($items as $item)
        {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination . '/' . $item->getBasename();
            
            if ($item->isDir())
            {
                $path = $item->getPathname();
                
                if (!$this->copyDirectory($path, $target, $options))
                {
                    return false;
                }
            }
            
            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
            else
            {
                if (!$this->copy($item->getPathname(), $target))
                {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * 创建目录
     *
     * @param  string $path
     * @param  int    $mode
     * @param  bool   $recursive
     * @param  bool   $force
     *
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force)
        {
            return @mkdir($path, $mode, $recursive);
        }
        
        return mkdir($path, $mode, $recursive);
    }
    
    /**
     * 拷贝文件
     *
     * @param  string $path
     * @param  string $target
     *
     * @return bool
     */
    public function copy($path, $target)
    {
        return copy($path, $target);
    }
}