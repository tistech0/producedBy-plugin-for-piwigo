<?php
/*
Version: 1.0
Plugin Name: ProducedBy
Author: tistech
Description: Add a field to picture page to add a producedBy mention.
*/

// Chech whether we are indeed included by Piwigo.
if (!defined('PHPWG_ROOT_PATH'))
    die('Hacking attempt!');

// Define the path to our plugin.
define('PRODUCEDBY_PATH', PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');

global $prefixeTable;
global $alreadyInit;
define('PHOTO_TABLE', $prefixeTable . 'title_photo');

add_event_handler('init', 'producedBy_init');


add_event_handler('loc_end_picture', 'TitlePhoto', EVENT_HANDLER_PRIORITY_NEUTRAL);
function TitlePhoto()
{
    global $template, $page;
    if (!empty($page['image_id'])) {
        $query = '
  select id,title
    FROM ' . TITLE_PHOTO_TABLE . '
    WHERE id = \'' . $page['image_id'] . '\'
    ;';
        $result = pwg_query($query);
        $row = pwg_db_fetch_assoc($result);
        if (isset($row['title'])) {
            $titleP = $row['title'];
            $titlePED = trigger_change('AP_render_content', $titleP);
            if (!empty($titlePED)) {
                $template->assign('PERSO_TITLE', $titlePED);
            }
        }
    }
}

add_event_handler('loc_begin_admin', 'titlePadminf', EVENT_HANDLER_PRIORITY_NEUTRAL);
add_event_handler('loc_begin_admin_page', 'titlePadminA', EVENT_HANDLER_PRIORITY_NEUTRAL);

function titlePadminf()
{
    global $template;
    $template->set_prefilter('picture_modify', 'titlePadminfT');
}


function titlePadminfT($content)
{
    $search = '#<input type="hidden" name="pwg_token"#';
    $replacement = '
    <p>
    <strong>Produite par</strong>
    <br>
    <textarea cols="100" maxlength="100" style="resize: none;" {if $useED==1}placeholder="{\'Use Extended Description tags...\'}"{/if} name="insertitleP" id="insertitleP" class="insertitleP">{$titleICONTENT}></textarea>
    </p>
	
    <input type="hidden" name="pwg_token"';

    return preg_replace($search, $replacement, $content);
}

function titlePadminA()
{
    if (isset($_GET['image_id'])) {
        global $template, $prefixeTable, $pwg_loaded_plugins;
        $query = 'select id,title FROM ' . TITLE_PHOTO_TABLE . ' WHERE id = ' . $_GET['image_id'] . ';';
        $result = pwg_query($query);
        $row = pwg_db_fetch_assoc($result);
        if (isset($row['title'])) {
            $titleP = $row['title'];
            $template->assign(
                array(
                    'titleICONTENT' => $titleP,
                )
            );
        } else {
            $template->assign(
                array(
                    'titleICONTENT' => "",
                )
            );
        }
        if (isset($pwg_loaded_plugins['ExtendedDescription'])) {
            $template->assign('useED', 1);
        } else {
            $template->assign('useED', 0);
        }

    }
    if (isset($_POST['insertitleP'])) {
        $query = 'DELETE FROM ' . TITLE_PHOTO_TABLE . ' WHERE id = ' . $_GET['image_id'] . ';';
        $result = pwg_query($query);
        $q = 'INSERT INTO ' . $prefixeTable . 'title_photo(id,title)VALUES (' . $_GET['image_id'] . ',"' . $_POST['insertitleP'] . '");';
        pwg_query($q);
        $template->assign(
            array(
                'titleICONTENT' => $_POST['insertitleP'],
            )
        );
    }
}

add_event_handler('loc_end_picture', 'add_producedBy_string', EVENT_HANDLER_PRIORITY_NEUTRAL);
add_event_handler('loc_end_picture', 'addDetailTemplate', EVENT_HANDLER_PRIORITY_NEUTRAL);
function add_producedBy_string()
{
    global $template, $picture, $prefixeTable;

    // retrieve the custom title from the database
    $query = 'SELECT title FROM ' . $prefixeTable . 'title_photo WHERE id = ' . $picture['current']['id'];
    $result = pwg_query($query);
    if (pwg_db_num_rows($result) > 0) {
        $row = pwg_db_fetch_assoc($result);
        $title = $row['title'];

        // display the custom title on the picture detail page
        if (!empty($title)) {
            $template->assign('INFO_PRODUCEDBY', '<span>' . $title . '</span>');
        }
    } else {
        var_dump('ICI');
        $template->assign('INFO_PRODUCEDBY', '<span> ' . '</span>');
    }
}

function titleDetail($content)
{
    global $alreadyInit;

    $template_file = file_get_contents('themes/default/template/picture.tpl');
    if (!$alreadyInit) {

        // define the search pattern and replacement string
        $search = '/<dd>\{\$INFO_POSTED_DATE\}<\/dd>/';
        $replace = '<dd>{$INFO_POSTED_DATE}</dd>
        <dt>{\'Produite par\'}</dt>
        <dd>{$INFO_PRODUCEDBY}</dd>';

        // apply the preg_replace function to modify the template file contents
        $modified_template_file = preg_replace($search, $replace, $template_file);

        // write the modified contents back to the picture.tpl template file
        file_put_contents('themes/default/template/picture.tpl', $modified_template_file);
        $alreadyInit = true;
        return ($modified_template_file);
    }
    var_dump("pass");
    return ($content);
}

function addDetailTemplate()
{
    global $template;

    $template->set_prefilter('picture', 'titleDetail');
}

function producedBy_init()
{
    global $conf;
    global $prefixeTable;
    global $alreadyInit;
    $alreadyInit = false;

    if (!defined('TITLE_PHOTO_TABLE'))
        define('TITLE_PHOTO_TABLE', $prefixeTable . 'title_photo');
    $query = "CREATE TABLE IF NOT EXISTS " . TITLE_PHOTO_TABLE . " (
        id SMALLINT( 5 ) UNSIGNED NOT NULL ,
title VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY (id))DEFAULT CHARSET=utf8;";
    $result = pwg_query($query);
}
?>