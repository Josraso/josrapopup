<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$engine = _MYSQL_ENGINE_;

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup` (
    `id_popup` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_from` DATETIME NULL,
    `date_to` DATETIME NULL,
    `priority` INT NOT NULL DEFAULT 10,

    `content_type` VARCHAR(20) NOT NULL DEFAULT "html",
    `image` VARCHAR(255) NULL,
    `image_link` VARCHAR(255) NULL,
    `image_width` INT NOT NULL DEFAULT 0,
    `image_height` INT NOT NULL DEFAULT 0,
    `image_responsive` TINYINT(1) NOT NULL DEFAULT 1,

    `trigger_type` VARCHAR(20) NOT NULL DEFAULT "load",
    `trigger_delay` INT NOT NULL DEFAULT 0,
    `trigger_scroll_percent` INT NOT NULL DEFAULT 50,
    `trigger_click_selector` VARCHAR(255) NULL,

    `frequency_type` VARCHAR(20) NOT NULL DEFAULT "session",
    `frequency_days` INT NOT NULL DEFAULT 7,
    `frequency_max_times` INT NOT NULL DEFAULT 3,

    `overlay` TINYINT(1) NOT NULL DEFAULT 1,
    `closable_outside_click` TINYINT(1) NOT NULL DEFAULT 1,
    `popup_width` INT NOT NULL DEFAULT 600,
    `popup_position` VARCHAR(20) NOT NULL DEFAULT "center",

    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_popup`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup_lang` (
    `id_popup` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NULL,
    `content` TEXT NULL,
    `button_text` VARCHAR(255) NULL,
    `button_link` VARCHAR(255) NULL,
    PRIMARY KEY (`id_popup`, `id_lang`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup_shop` (
    `id_popup` INT UNSIGNED NOT NULL,
    `id_shop` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_popup`, `id_shop`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup_group` (
    `id_popup` INT UNSIGNED NOT NULL,
    `id_group` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_popup`, `id_group`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup_category` (
    `id_popup` INT UNSIGNED NOT NULL,
    `id_category` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_popup`, `id_category`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup_product` (
    `id_popup` INT UNSIGNED NOT NULL,
    `id_product` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_popup`, `id_product`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup_page` (
    `id_popup` INT UNSIGNED NOT NULL,
    `page` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id_popup`, `page`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup_hook` (
    `id_popup` INT UNSIGNED NOT NULL,
    `hook` VARCHAR(64) NOT NULL,
    PRIMARY KEY (`id_popup`, `hook`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'josra_popup_display_log` (
    `id_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT UNSIGNED NOT NULL,
    `id_customer` INT UNSIGNED NOT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_log`),
    KEY `id_popup` (`id_popup`),
    KEY `id_customer` (`id_customer`)
) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
