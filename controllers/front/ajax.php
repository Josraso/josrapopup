<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

if (!class_exists('JosraPopupModel', false)) {
    require_once dirname(__FILE__) . '/../../classes/JosraPopupModel.php';
}

class JosrapopupAjaxModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    /** Registra la impresión de un popup (solo clientes identificados) */
    public function displayAjax()
    {
        $response = ['success' => false];

        if (Tools::getValue('action') === 'logDisplay') {
            $idPopup = (int) Tools::getValue('id_popup');
            $idCustomer = (int) $this->context->customer->id;

            if ($idPopup && $idCustomer) {
                JosraPopupModel::logDisplay($idPopup, $idCustomer);
                $response['success'] = true;
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /** Por si alguna versión no entra por displayAjax (PrestaShop 1.7 antiguos) */
    public function initContent()
    {
        $this->displayAjax();
    }
}
