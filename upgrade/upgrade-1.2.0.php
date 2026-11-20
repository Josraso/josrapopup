<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.2.0: compatible con PrestaShop 9 y sin entrada en el menú lateral
 * (la gestión de popups se abre desde Módulos > Configurar).
 */
function upgrade_module_1_2_0($module)
{
    $idTab = (int) Tab::getIdFromClassName('AdminJosraPopup');
    if ($idTab) {
        $tab = new Tab($idTab);
        $tab->id_parent = -1;
        $tab->save();
    }

    return true;
}
