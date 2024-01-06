<?php

namespace Nece\Brawl\FileSystem\Ftp\Test;

use PHPUnit\Framework\TestCase;
use Nece\Brawl\FileSystem\Factory;

class FtpTest extends TestCase
{
    private $ftp;
    private $upload_file = '';

    protected function setUp(): void
    {
        // 测试上传的文件
        $this->upload_file = 'D:\资料文件\其它\1.png';

        // FTP配置
        $config = array(
            'host' => '192.168.1.106',
            'port' => '21',
            'timeout' => 90,
            'username' => 'user',
            'password' => '123456',
            'mode' => 2,
            'pasv' => 1,
            'sub_path' => 'public',
            'base_url' => 'http://aa.com',
            'tmp_dir' => 'D:/tmp',
        );

        $conf = Factory::createConfig('Ftp');
        $conf->setConfig($config);
        $this->ftp = Factory::createClient($conf);
    }

    protected function tearDown(): void
    {
        echo PHP_EOL, 'FTP测试完成。', PHP_EOL;
    }

    public function testFtp()
    {
        $dir = '/a/b/';
        $file = $dir.'/b.txt';
        $copy = $dir.'/b1.txt';
        $move = $dir.'/b2.txt';
        $upload = $dir.'/1.png';

        $this->ftp->mkDir($dir);
        $this->assertTrue($this->ftp->exists($dir), '目录不存在');

        $this->ftp->write($file, '123');
        $this->assertTrue($this->ftp->exists($file), '文件不存在');
        $this->assertEquals('123', $this->ftp->read($file), '文件内容不正确');
        $this->assertTrue(is_int($this->ftp->lastModified($file)), '文件更新时间不正确');
        $this->assertEquals(3, $this->ftp->fileSize($file), '文件大小不正确');

        $this->ftp->append($file, '456中文');
        $this->assertEquals('123456中文', $this->ftp->read($file), '追加的文件内容不正确');

        $this->ftp->copy($file, $copy);
        $this->assertTrue($this->ftp->exists($copy), '复制文件不存在');

        $this->ftp->move($copy, $move);
        $this->assertTrue($this->ftp->exists($move), '移动文件不存在');

        $list = $this->ftp->readDir($dir);
        $this->assertTrue(is_array($list), '读取目录失败');

        $this->ftp->upload($this->upload_file, $upload);
        $this->assertTrue($this->ftp->exists($upload), '上传文件不存在');

        $this->ftp->delete($file);
        $this->ftp->delete($move);
        $this->ftp->delete($upload);
        $this->ftp->delete($dir);
    }
}