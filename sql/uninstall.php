<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$tables = [
    'josra_popup',
    'josra_popup_lang',
    'josra_popup_shop',
    'josra_popup_group',
    'josra_popup_category',
    'josra_popup_product',
    'josra_popup_page',
    'josra_popup_hook',
    'josra_popup_display_log',
];

foreach ($tables as $table) {
    Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`');
}
