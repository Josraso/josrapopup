<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Actualiza las columnas date_from/date_to de DATE a DATETIME para poder
 * programar los popups con hora, no solo con fecha.
 * Se ejecuta automáticamente al detectar que el módulo pasa a la versión 1.1.0.
 */
function upgrade_module_1_1_0($module)
{
    $sql = [];

    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'josra_popup`
                MODIFY `date_from` DATETIME NULL,
                MODIFY `date_to` DATETIME NULL';

    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    }

    return true;
}
