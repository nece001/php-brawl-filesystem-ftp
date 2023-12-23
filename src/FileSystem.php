<?php

namespace Nece\Brawl\FileSystem\Ftp;

use Nece\Brawl\ConfigAbstract;
use Nece\Brawl\FileSystem\FileSystemAbstract;
use Nece\Brawl\FileSystem\FileSystemException;
use Throwable;

class FileSystem extends FileSystemAbstract
{
    protected $base_path;

    /**
     * 设置配置
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param ConfigAbstract $config
     *
     * @return void
     */
    public function setConfig(ConfigAbstract $config)
    {
        parent::setConfig($config);

        $this->base_path = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $this->getConfigValue('base_path', '')), DIRECTORY_SEPARATOR);
        $this->sub_path = trim(str_replace('/', DIRECTORY_SEPARATOR, $this->getConfigValue('sub_path', '')), DIRECTORY_SEPARATOR);
    }

    /**
     * 构建绝对路径
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return string
     */
    protected function buildPath($path)
    {
        $path = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        return $this->base_path . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * 构建带子路径的绝对路径
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return string
     */
    protected function buildPathWithSubPath($path)
    {
        $path = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $this->real_path = $this->sub_path . DIRECTORY_SEPARATOR . $path;
        return $this->base_path . DIRECTORY_SEPARATOR . $this->real_path;
    }

    /**
     * 获取签名url
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     * 
     * @param string $path 相对路径
     * @param int $expires 过期时间（秒）
     *
     * @return string
     */
    public function buildPreSignedUrl(string $path, $expires = null): string
    {
        return $this->buildUrl($path, $expires);
    }

    /**
     * 写文件内容
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     * @param string $content
     *
     * @return void
     */
    public function write(string $path, string $content): void
    {
        $filename = $this->buildPathWithSubPath($path);

        if (file_exists($filename)) {
            if (!is_writable($filename)) {
                throw new FileSystemException('文件不可写');
            }
        } else {
            $this->mkDir(dirname($this->real_path));
        }

        file_put_contents($filename, $content);

        $this->setUri($filename);
    }

    /**
     * 追加文件内容
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径（已存在的文件）
     * @param string $content
     *
     * @return void
     */
    public function append(string $path, string $content): void
    {
        $filename = $this->buildPath($path);

        if (file_exists($filename)) {
            if (!is_writable($filename)) {
                throw new FileSystemException('文件不可写');
            }
        } else {
            $this->mkDir(dirname($this->real_path));
        }

        file_put_contents($filename, $content, FILE_APPEND);

        $this->setUri($filename);
    }

    /**
     * 复制文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $source 相对路径
     * @param string $destination 相对路径
     *
     * @return void
     */
    public function copy(string $source, string $destination): void
    {
        try {
            $source = $this->buildPath($source);
            $destination = $this->buildPathWithSubPath($destination);

            $this->mkDir(dirname($this->real_path));
            copy($source, $destination);

            $this->setUri($this->real_path);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('文件复制失败');
        }
    }

    /**
     * 移动文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $source 相对路径
     * @param string $destination 相对路径
     *
     * @return void
     */
    public function move(string $source, string $destination): void
    {
        try {
            $source = $this->buildPath($source);
            $destination = $this->buildPathWithSubPath($destination);

            $this->mkDir(dirname($this->real_path));
            rename($source, $destination);

            $this->setUri($this->real_path);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('文件移动失败');
        }
    }

    /**
     * 上传文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $local 绝对路径
     * @param string $destination 相对路径
     *
     * @return void
     */
    public function upload(string $local, string $destination): void
    {
        $destination = $this->buildPathWithSubPath($destination);

        $this->mkDir(dirname($this->real_path));

        if (is_uploaded_file($local)) {
            if (!move_uploaded_file($local, $destination)) {
                throw new FileSystemException('文件上传失败');
            }
        } else {
            throw new FileSystemException('文件上传失败：非正常上传的文件');
        }

        $this->setUri($this->real_path);
    }

    /**
     * 文件是否存在
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return boolean
     */
    public function exists(string $path): bool
    {
        $filename = $this->buildPath($path);
        return file_exists($filename);
    }

    /**
     * 读取文件内容
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return string
     */
    public function read(string $path): string
    {
        $filename = $this->buildPath($path);

        if (file_exists($filename)) {
            if (!is_readable($filename)) {
                throw new FileSystemException('文件不可读');
            }
        }

        return file_get_contents($filename);
    }

    /**
     * 删除文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return void
     */
    public function delete(string $path): void
    {
        $filename = $this->buildPath($path);
        $this->rm($filename);
    }

    /**
     * 删除文件或目录
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $dirname
     *
     * @return boolean
     */
    private function rm($dirname)
    {
        if (!file_exists($dirname)) {
            return false;
        }

        if (is_file($dirname) || is_link($dirname)) {
            return unlink($dirname);
        }

        $dir = dir($dirname);
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            $this->rm($dirname . DIRECTORY_SEPARATOR . $entry);
        }
        $dir->close();

        return rmdir($dirname);
    }

    /**
     * 创建目录
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return void
     */
    public function mkDir(string $path): void
    {
        $filename = $this->buildPath($path);
        if (!file_exists($filename)) {
            mkdir($filename, 0777, true);
        }
    }

    /**
     * 获取最后更新时间
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return intger
     */
    public function lastModified(string $path): int
    {
        $filename = $this->buildPath($path);
        return filemtime($filename);
    }

    /**
     * 获取文件大小(字节数)
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return integer
     */
    public function fileSize(string $path): int
    {
        $filename = $this->buildPath($path);
        return filesize($filename);
    }

    /**
     * 列表
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path
     *
     * @return array
     */
    public function readDir(string $path): array
    {
        $filename = $this->buildPath($path);
        return scandir($filename);
    }
}
