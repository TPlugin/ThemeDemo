<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 模板预览插件 <a href="https://github.com/shingchi">ShingChi</a> 更新于 2014-11-22
 *
 * @package ThemeDemo
 * @author doudou
 * @version 1.2.0
 * @link http://doudou.me
 */
/**
 * Example:
 *
 * URL后添加 ?theme=主题 | 为空则删除cookie，恢复默认
 *
 */
class ThemeDemo_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->handleInit = array('ThemeDemo_Plugin', 'setTheme');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){}

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function setTheme($widget)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $cookie = array(
            'key'   => '__typecho_theme',
            'expire' => 86400, //默认cookie存活时间
        );

        /** 请求模版预览时设置cookie */
        if (isset($widget->request->theme) && $widget->request->isGet()) {
            $themeName = $widget->request->theme;

            if (!empty($themeName) && static::check($themeName)) {
                $configFile = $options->themeFile($themeName, 'functions.php');
                if (file_exists($configFile)) {
                    require_once $configFile;
                    if (function_exists('themeConfig')) {
                        $form = new Typecho_Widget_Helper_Form();
                        themeConfig($form);
                        $config = serialize($form->getValues());
                    }
                }

                $value = array('theme' => $themeName, 'config' => isset($config) ? $config : '');
                Typecho_Cookie::set($cookie['key'], serialize($value), $options->gmtTime + $cookie['expire']);
            } else {
                Typecho_Cookie::delete($cookie['key']);
                return;
            }
        }

        /** 配置初始化模版 */
        if ($themeCookie = Typecho_Cookie::get($cookie['key'])) {
            $themeInfo = unserialize($themeCookie);

            if (!static::check($themeInfo['theme'])) {
                Typecho_Cookie::delete($cookie['key']);
                return;
            }

            $themeName = $themeInfo['theme'];
            $themeDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR;

            /** 配置模版信息 */
            if (!empty($themeInfo['config'])) {
                $options->{'theme:' . $themeName} = $themeInfo['config'];
                foreach (unserialize($themeInfo['config']) as $row => $value) {
                    $options->{$row} = $value;
                }
            }

            /** 配置模版 */
            $options->theme = $themeName;

            /** 配置模版路径 */
            $widget->setThemeDir($themeDir);
        }

        return;
    }

    /**
     * 检测主题是否存在
     *
     * @access public
     * @param string $theme 主题名
     * @return boolean
     */
    public static function check($theme)
    {
        $themeDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . DIRECTORY_SEPARATOR . $theme;
        if (is_dir($themeDir)) {
            return true;
        }
        return false;
    }
}
