<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ipdebug extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ipdebug';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'keng';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Debug By ipaddress');
        $this->description = $this->l('Debug By ipaddress');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->files_copy = array('list_ip.php','defines_custom.inc.php');
        $this->path_copy = _PS_MODULE_DIR_.'ipdebug/public/';
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('IPD_ENABLE_IP_LIST', false);
        foreach ($this->files_copy as $file) {
            if (file_exists(_PS_CONFIG_DIR_.$file)) {
                $datetime = date("Y-m-d");
                copy(_PS_CONFIG_DIR_.$file, _PS_CONFIG_DIR_.'backup-'.$datetime.$file);
                unlink(_PS_CONFIG_DIR_.$file);
                copy($this->path_copy.$file, _PS_CONFIG_DIR_.$file);
            } else {
                copy($this->path_copy.$file, _PS_CONFIG_DIR_.$file);
            }
        }
        Tools::clearCache();
        return parent::install()
            && $this->registerHook('backOfficeFooter');
    }

    public function uninstall()
    {
        Configuration::deleteByName('IPD_ENABLE_IP_LIST');
        foreach ($this->files_copy as $file) {
            if (file_exists(_PS_CONFIG_DIR_.$file)) {
                unlink(_PS_CONFIG_DIR_.$file);
            }
        }
        Tools::clearCache();
        return parent::uninstall();
    }

    public function enable($force_all = false)
    {
        Tools::clearCache();
        return parent::enable($force_all);
    }

    public function disable($force_all = false)
    {
        Configuration::deleteByName('IPD_ENABLE_IP_LIST');
        foreach ($this->files_copy as $file) {
            if (file_exists(_PS_CONFIG_DIR_.$file)) {
                unlink(_PS_CONFIG_DIR_.$file);
            }
        }
        Tools::clearCache();
        return parent::disable($force_all);
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        Tools::clearCache();
        $error = '';
        if (((bool)Tools::isSubmit('submitIpdebugModule')) == true) {
            $status = $this->postProcess();
            $url_admin = $this->context->link->getAdminLink('AdminModules', true);
            $data = '&conf=4&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
            if ($status) {
                Tools::redirectAdmin($url_admin.$data);
            } else {
                $error = $this->displayError('Error !! can\'t save.');
            }
        }
       
        return $error.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table.'_allow_list_ip';
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitIpdebugModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enabled IP LIST'),
                        'name' => 'IPD_ENABLE_IP_LIST',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Example : 192.168.1.1,192.168.1.2,...,192.168.1.x'),
                        'name' => 'IPD_IP',
                        'label' => $this->l('IP Address'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enabled Debug'),
                        'name' => 'IPD_LIVE_MODE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),

                    
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'IPD_LIVE_MODE' => $this->isDebugModeEnabled(),
            'IPD_ENABLE_IP_LIST' =>  Configuration::get('IPD_ENABLE_IP_LIST'),
            'IPD_IP' => Configuration::get('IPD_IP', '127.0.0.1,::1'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        $debug_statue = Tools::getValue('IPD_LIVE_MODE');
        //$debug_statue = !$debug_statue  ? '0' : '1';
        $res = true;
        if (Tools::getValue('IPD_ENABLE_IP_LIST')) {
            $ips = Tools::getValue('IPD_IP');
            if ($ips != '') {
                $ips = explode(',', $ips);
                foreach ($ips as $key => &$ip) {
                    $ip = $key.' => "'.$ip.'"';
                }
            } else {
                $ips = array();
            }
            foreach ($this->files_copy as $file) {
                if (file_exists(_PS_CONFIG_DIR_.$file)) {
                    if ($file == 'list_ip.php') {
                        $res &= $this->saveIpFile($ips);
                    } else {
                         $res &= $this->updateDebugModeValueInCustomFile($debug_statue);
                    }
                } else {
                    if (copy($this->path_copy.$file, _PS_CONFIG_DIR_.$file)) {
                        if ($file == 'list_ip.php') {
                            $res &= $this->saveIpFile($ips);
                        } else {
                            if (file_exists(_PS_CONFIG_DIR_.'list_ip.php')) {
                                Tools::clearCache();
                                $res &= $this->updateDebugModeValueInCustomFile($debug_statue);
                            }
                        }
                    } else {
                        return false;
                    }
                }
            }
        } else {
            $res &= $this->removeFiles();
            $res &= $this->updateDebugModeValueInMainFile($debug_statue);
        }
        return $res;
    }

    public function replaceModeDev($content)
    {
        $content = str_replace("'_PS_MODE_DEV_', 1", "'_PS_MODE_DEV_', true", $content);
        $content = str_replace("'_PS_MODE_DEV_', 0", "'_PS_MODE_DEV_', false", $content);
        return $content;
    }
    public function updateDebugModeValueInCustomFile($value = 0)
    {
        $customFileName = _PS_ROOT_DIR_ . '/config/defines_custom.inc.php';
        $cleanedFileContent = php_strip_whitespace($customFileName);
        $cleanedFileContent = $this->replaceModeDev($cleanedFileContent);
        $fileContent = Tools::file_get_contents($customFileName);
        
        if (!empty($cleanedFileContent)
            && preg_match('/define\(\'_PS_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', $cleanedFileContent)) {
            if ($value == 0) {
                $fileContent = preg_replace(
                    '/define\(\'_PS_MODE_DEV_\', ([a-zA-Z]+)\);/Ui',
                    'define(\'_PS_MODE_DEV_\', false);',
                    $fileContent
                );
            } else {
                $fileContent = preg_replace(
                    '/define\(\'_PS_MODE_DEV_\', ([a-zA-Z]+)\);/Ui',
                    'define(\'_PS_MODE_DEV_\', true);',
                    $fileContent
                );
            }
            $fileContent = $this->replaceModeDev($fileContent);
            if (!@file_put_contents($customFileName, $fileContent)) {
                return false;
            }
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($customFileName);
            }
            return true;
        }
        return false;
    }

    public function pre($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
    public function updateDebugModeValueInMainFile($value = 0)
    {
        $filename = _PS_ROOT_DIR_ . '/config/defines.inc.php';
        $cleanedFileContent = php_strip_whitespace($filename);
        $cleanedFileContent = $this->replaceModeDev($cleanedFileContent);
        $fileContent = Tools::file_get_contents($filename);

        if (!preg_match('/define\(\'_PS_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', $cleanedFileContent)) {
            return false;
        }
        if ($value == 0) {
            $fileContent = preg_replace(
                '/define\(\'_PS_MODE_DEV_\', ([a-zA-Z]+)\);/Ui',
                'define(\'_PS_MODE_DEV_\', false);',
                $fileContent
            );
        } else {
            $fileContent = preg_replace(
                '/define\(\'_PS_MODE_DEV_\', ([a-zA-Z]+)\);/Ui',
                'define(\'_PS_MODE_DEV_\', true);',
                $fileContent
            );
        }
        $fileContent = $this->replaceModeDev($fileContent);
        if (!@file_put_contents($filename, $fileContent)) {
            return false;
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($filename);
        }
        return true;
    }

    public function saveIpFile($ips)
    {
        $return = '<?php return array ('.implode(',', $ips).');';
        $file = _PS_CONFIG_DIR_.'list_ip.php';
        $myfile = fopen($file, "w") or die("Unable to open file!");
        fwrite($myfile, $return);
        fclose($myfile);
        return true;
    }
    public function removeFiles()
    {
        foreach ($this->files_copy as $file) {
            if (file_exists(_PS_CONFIG_DIR_.$file)) {
                if (!unlink(_PS_CONFIG_DIR_.$file)) {
                    return false;
                }
            }
        }
        Tools::clearCache();
        return true;
    }

    public function isDebugModeEnabled()
    {
        $definesClean = '';
        $customDefinesPath = _PS_ROOT_DIR_ . '/config/defines_custom.inc.php';
        $definesPath = _PS_ROOT_DIR_ . '/config/defines.inc.php';
        if (is_readable($customDefinesPath)) {
            $definesClean = php_strip_whitespace($customDefinesPath);
        }
        if (!preg_match('/define\(\'_PS_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', $definesClean, $debugModeValue)) {
            $definesClean = php_strip_whitespace($definesPath);
            if (!preg_match('/define\(\'_PS_MODE_DEV_\', ([a-zA-Z]+)\);/Ui', $definesClean, $debugModeValue)) {
                return false;
            }
        }
        return 'true' === Tools::strtolower($debugModeValue[1]);
    }

    public function hookBackOfficeFooter()
    {
        $url_admin = $this->context->link->getAdminLink('AdminModules', true);
        $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        $data = '&configure='.$this->name;
        $ipdebug = Tools::getValue('configure') == $this->name ? true : false;
        $this->context->smarty->assign(
            array(
                'url_debug_setting' => $url_admin.$data,
                'path_js' => $this->_path.'views/js/back.js',
                'ipdebug' => $ipdebug,
                'my_ip_address' => $this->getClientIp(),
                'ps_version' => _PS_VERSION_
            )
        );
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');
        return $output;
    }

    public function getClientIp()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }
}
