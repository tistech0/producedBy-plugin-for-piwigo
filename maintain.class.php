<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

/**
 * This class is used to expose maintenance methods to the plugins manager
 * It must extends PluginMaintain and be named "PLUGINID_maintain"
 * where PLUGINID is the directory name of your plugin.
 */

class producedBy_maintain extends PluginMaintain
{

    private $table;
    private $dir;
    function __construct($plugin_id)
    {
        parent::__construct($plugin_id); // always call parent constructor

        global $prefixeTable;

        // Class members can't be declared with computed values so initialization is done here
        $this->table = $prefixeTable . 'producedBy';
        $this->dir = PHPWG_ROOT_PATH . PWG_LOCAL_DIR . 'producedBy/';
    }

    /**
     * Plugin installation
     *
     * Perform here all needed step for the plugin installation such as create default config,
     * add database tables, add fields to existing tables, create local folders...
     */
    function install($plugin_version, &$errors = array())
    {
        global $conf;

        // add config parameter
        if (empty($conf['producedBy'])) {
            // conf_update_param well serialize and escape array before database insertion
            // the third parameter indicates to update $conf['skeleton'] global variable as well
            conf_update_param('producedBy', $this->default_conf, true);
        } else {
            $old_conf = safe_unserialize($conf['producedBy']);

            conf_update_param('producedBy', $old_conf, true);
        }

        // create a local directory
        if (!file_exists($this->dir)) {
            mkdir($this->dir, 0755);
        }
    }

    /**
     * Plugin activation
     *
     * This function is triggered after installation, by manual activation or after a plugin update
     * for this last case you must manage updates tasks of your plugin in this function
     */
    function activate($plugin_version, &$errors = array())
    {
    }

    /**
     * Plugin deactivation
     *
     * Triggered before uninstallation or by manual deactivation
     */
    function deactivate()
    {
    }

    /**
     * Plugin (auto)update
     *
     * This function is called when Piwigo detects that the registered version of
     * the plugin is older than the version exposed in main.inc.php
     * Thus it's called after a plugin update from admin panel or a manual update by FTP
     */
    function update($old_version, $new_version, &$errors = array())
    {
        // I (mistic100) chosed to handle install and update in the same method
        // you are free to do otherwize
        $this->install($new_version, $errors);
    }

    /**
     * Plugin uninstallation
     *
     * Perform here all cleaning tasks when the plugin is removed
     * you should revert all changes made in 'install'
     */
    function uninstall()
    {
        // delete configuration
        conf_delete_param('producedBy');

        // delete table
        pwg_query('DROP TABLE `' . $this->table . '`;');

        // delete field
        pwg_query('ALTER TABLE `' . IMAGES_TABLE . '` DROP `producedBy`;');

        // delete local folder
        // use a recursive function if you plan to have nested directories
        foreach (scandir($this->dir) as $file) {
            if ($file == '.' or $file == '..')
                continue;
            unlink($this->dir . $file);
        }
        rmdir($this->dir);
    }
}