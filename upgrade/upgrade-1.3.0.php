<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.3.0: actualizaciones desde el servidor de Josra y fechas vacías bien guardadas.
 * - Registra los hooks del actualizador.
 * - Los popups guardados sin fecha tenían "0000-00-00 00:00:00" y no se podían volver a guardar:
 *   pasan a NULL (sin límite), que es lo que significaban.
 */
function upgrade_module_1_3_0($module)
{
    $table = '`' . _DB_PREFIX_ . 'josra_popup`';
    foreach (['date_from', 'date_to'] as $field) {
        // Comparando como texto para que no falle con MySQL en modo estricto
        $ok = Db::getInstance()->execute(
            'UPDATE ' . $table . ' SET `' . $field . '` = NULL WHERE CAST(`' . $field . '` AS CHAR) LIKE \'0000-00-00%\''
        );
        if (!$ok) {
            return false;
        }
    }

    return $module->getUpdater()->registerHooks();
}
