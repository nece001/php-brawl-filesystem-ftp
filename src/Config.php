<?php

namespace Nece\Brawl\FileSystem\Ftp;

use Nece\Brawl\ConfigAbstract;

/**
 * 本地存储配置
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
        $this->addTemplate(true, 'base_path', '硬盘目录', '例：D:/a/b');
        $this->addTemplate(true, 'sub_path', '子目录', '例：a/b/c');
        $this->addTemplate(true, 'base_url', '基础URL', '例：http(s)://aaa.com');
    }
}
