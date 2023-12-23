<?php

namespace Nece\Brawl\FileSystem\Ftp;

use Nece\Brawl\ConfigAbstract;

/**
 * FTP存储配置
 *
 * @Author nece001@163.com
 * @DateTime 2023-06-17
 */
class Config extends ConfigAbstract
{
    /**
     * 构建配置模板
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @return void
     */
    public function buildTemplate()
    {
        $this->addTemplate(true, 'host', '服务器地址', '例：192.168.1.15');
        $this->addTemplate(true, 'port', '端口号', '例：21');
        $this->addTemplate(true, 'timeout', '连接超时（秒）', '例：90');
        $this->addTemplate(true, 'username', '账号', '');
        $this->addTemplate(true, 'password', '密码', '');

        $this->addTemplate(true, 'mode', '传送模式', '1=文本模式,2=二进制模式(默认)。', '2', array(1=>'FTP_ASCII', 2=>'FTP_BINARY'));
        $this->addTemplate(true, 'pasv', '被动模式', '1=使用被动模式，0=不使用', '1');
        
        $this->addTemplate(true, 'sub_path', '子目录', '例：a/b/c');
        $this->addTemplate(true, 'base_url', '基础URL', '例：http(s)://aaa.com');
        $this->addTemplate(true, 'tmp_dir', '临时目录', '保存临时文件的本地目录，例：d:/tmp，/tmp');
    }
}
