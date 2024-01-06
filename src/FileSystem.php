<?php

namespace Nece\Brawl\FileSystem\Ftp;

use Nece\Brawl\ConfigAbstract;
use Nece\Brawl\FileSystem\FileSystemAbstract;
use Nece\Brawl\FileSystem\FileSystemException;

class FileSystem extends FileSystemAbstract
{
    private $host;
    private $port;
    private $timeout;
    private $mode;
    private $pasv;
    private $username;
    private $password;
    private $tmp_dir;

    private $ftp;

    public function __construct()
    {
        if (!function_exists('ftp_connect')) {
            throw new FileSystemException('请先安装ftp扩展');
        }
    }

    public function __destruct()
    {
        if ($this->ftp) {
            ftp_close($this->ftp);
        }
    }

    /**
     * 获取连接
     *
     * @Author nece001@163.com
     * @Created 2023-12-23
     *
     * @return \Ftp\Connection
     */
    private function getConnection()
    {
        if (!$this->ftp) {
            $this->ftp = ftp_connect($this->host, $this->port, $this->timeout);
            if (!$this->ftp) {
                throw new FileSystemException('连接失败');
            }

            if (!ftp_login($this->ftp, $this->username, $this->password)) {
                throw new FileSystemException('登录失败');
            }
            if ($this->pasv) {
                if (!ftp_pasv($this->ftp, true)) {
                    throw new FileSystemException('设置被动模式失败');
                }
            }
        }
        return $this->ftp;
    }

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

        $this->host = $this->getConfigValue('host');
        $this->port = $this->getConfigValue('port');
        $this->timeout = $this->getConfigValue('timeout', 90);
        $this->username = $this->getConfigValue('username');
        $this->password = $this->getConfigValue('password');
        $this->base_url = $this->getConfigValue('base_url');
        $this->tmp_dir = $this->getConfigValue('tmp_dir');

        $this->mode = intval($this->getConfigValue('mode', 1)) == 1 ? FTP_ASCII : FTP_BINARY;
        $this->pasv = intval($this->getConfigValue('pasv', 1)) == 1 ? true : false;
    }

    /**
     * 创建临时文件名
     *
     * @Author nece001@163.com
     * @Created 2023-12-23
     *
     * @return string
     */
    private function buildTmpName()
    {
        if ($this->tmp_dir) {
            $path = $this->tmp_dir;
        } else {
            $path = sys_get_temp_dir();
        }

        $path = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return  $path . DIRECTORY_SEPARATOR . uniqid();
    }

    /**
     * 写临时文件
     *
     * @Author nece001@163.com
     * @Created 2023-12-23
     *
     * @param string $content 内容
     * @return string 临时文件路径
     */
    private function writeTmpFile($content)
    {
        $tmp_name = $this->buildTmpName();
        if (!file_put_contents($tmp_name, $content)) {
            throw new FileSystemException('写临时文件失败');
        }
        return $tmp_name;
    }

    /**
     * 删除临时文件
     *
     * @Author nece001@163.com
     * @Created 2023-12-23
     *
     * @param string $tmp_name
     *
     * @return void
     */
    private function deleteTmpFile($tmp_name)
    {
        if (file_exists($tmp_name)) {
            if (!unlink($tmp_name)) {
                throw new FileSystemException('删除临时文件失败');
            }
        }
    }

    /**
     * 写文件内容
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     * @param string $content
     *
     * @return void
     */
    public function write(string $path, string $content): void
    {
        $tmp = $this->writeTmpFile($content);

        if (!ftp_put($this->getConnection(), $path, $tmp, $this->mode)) {
            throw new FileSystemException('上传文件失败');
        }

        $this->deleteTmpFile($tmp);

        $this->setUri($path);
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
        $tmp = $this->buildTmpName();

        if (!ftp_get($this->getConnection(), $tmp, $path, $this->mode)) {
            throw new FileSystemException('下载文件失败');
        }
        if (!file_put_contents($tmp, $content, FILE_APPEND)) {
            throw new FileSystemException('写临时文件失败');
        }
        if (!ftp_put($this->getConnection(), $path, $tmp, $this->mode)) {
            throw new FileSystemException('上传文件失败');
        }
        $this->deleteTmpFile($tmp);

        $this->setUri($path);
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
        $tmp = $this->buildTmpName();
        if (!ftp_get($this->getConnection(), $tmp, $source, $this->mode)) {
            throw new FileSystemException('下载文件失败');
        }
        if (!ftp_put($this->getConnection(), $destination, $tmp, $this->mode)) {
            throw new FileSystemException('上传文件失败');
        }
        $this->deleteTmpFile($tmp);

        $this->setUri($destination);
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
        if (!@ftp_rename($this->getConnection(), $source, $destination)) {
            throw new FileSystemException('移动文件失败');
        }

        $this->setUri($destination);
    }

    /**
     * 上传文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $local 绝对路径
     * @param string $to 相对路径
     *
     * @return void
     */
    public function upload(string $local, string $to): void
    {
        if (!ftp_put($this->getConnection(), $to, $local, $this->mode)) {
            throw new FileSystemException('上传文件失败');
        }
        $this->setUri($to);
    }

    /**
     * 文件是否存在
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return boolean
     */
    public function exists(string $path): bool
    {
        if (ftp_rawlist($this->getConnection(), $path) !== false) {
            return true;
        }

        return ftp_size($this->getConnection(), $path) !== -1;
    }

    /**
     * 读取文件内容
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return string
     */
    public function read(string $path): string
    {
        $tmp = $this->buildTmpName();
        if (!ftp_get($this->getConnection(), $tmp, $path, $this->mode)) {
            throw new FileSystemException('下载文件失败');
        }

        $content = file_get_contents($tmp);
        $this->deleteTmpFile($tmp);
        return $content;
    }

    /**
     * 删除文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return void
     */
    public function delete(string $path): void
    {
        if (!@ftp_rmdir($this->getConnection(), $path)) {
            if (!@ftp_delete($this->getConnection(), $path)) {
                throw new FileSystemException('删除失败');
            }
        }
    }

    /**
     * 创建目录
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return void
     */
    public function mkDir(string $path): void
    {
        $path = str_replace('\\', '/', $path);
        if (!$this->exists($path)) {
            $path = trim($path, '/');
            $parts = explode('/', $path);
            $dirs = array();
            foreach ($parts as $part) {
                $dirs[] = $part;
                $dir = implode('/', $dirs);
                @ftp_mkdir($this->getConnection(), $dir);
            }
        }
    }

    /**
     * 获取最后更新时间
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return integer
     */
    public function lastModified(string $path): int
    {
        return ftp_mdtm($this->getConnection(), $path);
    }

    /**
     * 获取文件大小(字节数)
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return integer
     */
    public function fileSize(string $path): int
    {
        return ftp_size($this->getConnection(), $path);
    }

    /**
     * 列表
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return array
     */
    public function readDir(string $path): array
    {
        $result = ftp_nlist($this->getConnection(), $path);
        if (!$result) {
            throw new FileSystemException('获取目录列表失败');
        }
        return $result;
    }

    /**
     * 生成预签名 URL
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     * 
     * @param string $path 相对路径
     * @param int $expires 过期时间
     *
     * @return string
     */
    public function buildPreSignedUrl(string $path, $expires = null): string
    {
        return $this->buildUrl($path, $expires);
    }

    /**
     * 是否目录
     *
     * @Author nece001@163.com
     * @Created 2024-01-06
     *
     * @param string $path
     *
     * @return boolean
     */
    public function isDir($path)
    {
        return @ftp_size($this->getConnection(), $path) < 0;
    }

    /**
     * 是否文件
     *
     * @Author nece001@163.com
     * @Created 2024-01-06
     *
     * @param string $path
     *
     * @return boolean
     */
    public function isFile($path)
    {
        return @ftp_size($this->getConnection(), $path) > 0;
    }
}
