# php-brawl-filesystem-ftp
php 文件存储基础服务适配项目（FTP）

# 使用示例

```
use Nece\Brawl\FileSystem\Factory;

        $config = array(
            'host' => '192.168.1.15',
            'port' => 21,
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
        $ftp = Factory::createClient($conf);
        $data = $ftp->listDir("/");

        print_r($data);
```

# 单元测试

包根目录下运行：
```
> D:/Storage/Soft/Windows/php/phpunit/phpunit7.bat --bootstrap=tests/bootstrap.php ./tests/
```
或composer方式运行：
```
> composer run-script test
```

或进入test目录运行：
```
> cd tests
> D:/Storage/Soft/Windows/php/phpunit/phpunit7.bat
```