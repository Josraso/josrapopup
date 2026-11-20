<?php
/**
 * Josra Popup Manager
 * Gestión avanzada de popups para PrestaShop 1.7, 8 y 9
 *
 * @author    Josra
 * @copyright Josra
 * @license   Uso privado
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/JosrapopupUpdater.php';

if (!class_exists('JosraPopupModel', false)) {
    require_once dirname(__FILE__) . '/classes/JosraPopupModel.php';
}

class Josrapopup extends Module
{
    /** Hooks disponibles para enganchar popups (mostrados como opciones en el admin) */
    const AVAILABLE_HOOKS = [
        'displayHeader',
        'displayFooter',
        'displayHome',
        'displayFooterAfter',
        'displayLeftColumn',
        'displayRightColumn',
        'displayBeforeBodyClosingTag',
    ];

    public function __construct()
    {
        $this->name = 'josrapopup';
        $this->tab = 'front_office_features';
        $this->version = '1.4.0';
        $this->author = 'Josra';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Josra Popup Manager');
        $this->description = $this->l('Crea y programa popups ilimitados: segmentación por grupo, categoría y producto, control de páginas, triggers y frecuencia de visualización.');
        $this->confirmUninstall = $this->l('¿Seguro que quieres desinstalar? Se perderán todos los popups configurados.');
    }

    public function install()
    {
        include dirname(__FILE__) . '/sql/install.php';

        return parent::install()
            && $this->installTab()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayFooter')
            && $this->registerHook('displayHome')
            && $this->registerHook('displayFooterAfter')
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('displayRightColumn')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->getUpdater()->registerHooks();
    }

    public function uninstall()
    {
        // Aviso al servidor de actualizaciones (nunca impide desinstalar)
        $this->getUpdater()->notifyUninstall();

        include dirname(__FILE__) . '/sql/uninstall.php';

        Configuration::deleteByName('JOSRAPOPUP_UPD_TS');
        Configuration::deleteByName('JOSRAPOPUP_UPD_INFO');
        Configuration::deleteByName('JOSRAPOPUP_UPD_SU');
        Configuration::deleteByName('JOSRAPOPUP_UPD_LT');

        return $this->uninstallTab() && parent::uninstall();
    }

    protected function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminJosraPopup';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Josra Popups';
        }
        // Pestaña oculta: no aparece en el menú lateral, se entra desde Módulos > Configurar
        $tab->id_parent = -1;
        $tab->module = $this->name;

        return $tab->add();
    }

    protected function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminJosraPopup');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return true;
    }

    public function getUpdater()
    {
        return new JosrapopupUpdater($this);
    }

    /** Aviso periódico al servidor de actualizaciones (solo en el back-office) */
    public function hookActionAdminControllerSetMedia($params)
    {
        $this->getUpdater()->maybePing();
    }

    /** PS 8.1+: la versión nueva aparece en Gestor de módulos > Actualizaciones */
    public function hookActionListModules($params)
    {
        return $this->getUpdater()->getNativeModuleList();
    }

    /**
     * Configurar: lleva a la gestión de popups. Si el panel de actualizaciones tiene algo que
     * decir (resultado de comprobar o actualizar, licencia), se queda aquí y lo muestra, con un
     * botón para ir a los popups.
     */
    public function getContent()
    {
        if (!Tab::getIdFromClassName('AdminJosraPopup')) {
            $this->installTab();
        }

        $updater = $this->getUpdater();
        $output = $updater->processRequest();
        $popupsUrl = $this->context->link->getAdminLink('AdminJosraPopup');

        if ($output === '') {
            Tools::redirectAdmin($popupsUrl);
        }

        $button = '<div class="panel"><a class="btn btn-primary" href="' . Tools::safeOutput($popupsUrl) . '">'
            . '<i class="icon-bell"></i> ' . $this->l('Ir a la gestión de popups') . '</a></div>';
        $info = $updater->getInfo();
        $panel = $updater->renderPanel();

        return !empty($info['update_available'])
            ? $output . $panel . $button
            : $output . $button . $panel;
    }

    /**
     * Inyectamos el CSS/JS del front solo cuando hace falta (todas las páginas,
     * ya que la restricción real de "dónde salta" la resuelve resolvePopups()).
     */
    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerStylesheet(
            'josrapopup-front-css',
            'modules/' . $this->name . '/views/css/josrapopup-front.css',
            ['media' => 'all', 'priority' => 150]
        );
        $this->context->controller->registerJavascript(
            'josrapopup-front-js',
            'modules/' . $this->name . '/views/js/josrapopup-front.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    /**
     * Hook genérico usado por todos los hooks de visualización registrados.
     * Devuelve el HTML contenedor + el JSON de configuración de los popups
     * que apliquen a esta página, para que el JS del front decida cuándo mostrarlos.
     */
    public function hookDisplayHeader()
    {
        return $this->renderPopupsForCurrentHook('displayHeader');
    }

    public function hookDisplayFooter()
    {
        return $this->renderPopupsForCurrentHook('displayFooter');
    }

    public function hookDisplayHome()
    {
        return $this->renderPopupsForCurrentHook('displayHome');
    }

    public function hookDisplayFooterAfter()
    {
        return $this->renderPopupsForCurrentHook('displayFooterAfter');
    }

    public function hookDisplayLeftColumn()
    {
        return $this->renderPopupsForCurrentHook('displayLeftColumn');
    }

    public function hookDisplayRightColumn()
    {
        return $this->renderPopupsForCurrentHook('displayRightColumn');
    }

    public function hookDisplayBeforeBodyClosingTag()
    {
        return $this->renderPopupsForCurrentHook('displayBeforeBodyClosingTag');
    }

    /**
     * Resuelve qué popups aplican al hook dado, para el contexto actual
     * (tienda, idioma, grupo de cliente, página, categoría/producto en curso)
     * y devuelve el bloque HTML (oculto) + JSON de config para el JS.
     */
    protected function renderPopupsForCurrentHook($hookName)
    {
        $preview = $this->getPreviewPopup();
        if ($preview !== false) {
            // Vista previa: solo ese popup, una vez por página, en el primer hook que se pinte
            static $previewDone = false;
            if ($previewDone || $preview === null) {
                return '';
            }
            $previewDone = true;
            $popups = [$preview];
        } else {
            $popups = $this->resolvePopups($hookName);
        }
        if (empty($popups)) {
            return '';
        }

        $this->context->smarty->assign([
            'josrapopup_list' => $popups,
            // JSON seguro dentro de <script> (el contenido HTML puede llevar "</script>", comillas, etc.)
            'josrapopup_json' => json_encode($popups, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'hook_name' => $hookName,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/popup.tpl');
    }

    /** Clave de la vista previa de un popup (el enlace no se puede adivinar para otro popup) */
    public function getPreviewToken($idPopup)
    {
        return Tools::substr(hash_hmac('sha256', 'josrapopup-preview-' . (int) $idPopup, _COOKIE_KEY_), 0, 20);
    }

    /** Enlace a la tienda que enseña el popup aunque esté desactivado, fuera de fechas o segmentado */
    public function getPreviewUrl($idPopup)
    {
        return $this->context->link->getPageLink('index', true, null, [
            'josrapopup_preview' => (int) $idPopup,
            'josrapopup_token' => $this->getPreviewToken($idPopup),
        ]);
    }

    /**
     * Popup pedido en vista previa: false si no hay vista previa en esta página, null si el
     * enlace no es válido, o los datos del popup listos para el JS (sale al cargar, siempre,
     * sin contar como visto).
     */
    protected function getPreviewPopup()
    {
        static $cache;
        if ($cache !== null) {
            return $cache['popup'];
        }
        $cache = ['popup' => false];

        $idPopup = (int) Tools::getValue('josrapopup_preview');
        if (!$idPopup) {
            return false;
        }
        $cache['popup'] = null;
        if (!hash_equals($this->getPreviewToken($idPopup), (string) Tools::getValue('josrapopup_token'))) {
            return null;
        }
        $popup = new JosraPopupModel($idPopup, (int) $this->context->language->id, (int) $this->context->shop->id);
        if (!Validate::isLoadedObject($popup)) {
            return null;
        }

        $data = $this->formatPopupForFront($popup);
        $data['trigger_type'] = 'load';
        $data['trigger_delay'] = 0;
        $data['frequency_type'] = 'always';
        $data['is_customer_logged'] = false;
        $data['is_preview'] = true;
        $cache['popup'] = $data;

        return $data;
    }

    /**
     * Devuelve un array de popups (ya filtrados por segmentación/página/frecuencia-BBDD)
     * listos para pintarse, con toda la config que necesita el JS del front.
     */
    public function resolvePopups($hookName)
    {
        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;
        $idGroup = (int) $this->context->customer->id_default_group ?: (int) Configuration::get('PS_UNIDENTIFIED_GROUP');
        $idCustomer = (int) $this->context->customer->id;

        $currentController = Tools::getValue('controller', $this->getCurrentPageIdentifier());
        $idCategory = (int) Tools::getValue('id_category');
        $idProduct = (int) Tools::getValue('id_product');

        $now = date('Y-m-d H:i:s');

        $sql = 'SELECT p.id_popup FROM ' . _DB_PREFIX_ . 'josra_popup p
                INNER JOIN ' . _DB_PREFIX_ . 'josra_popup_hook ph ON ph.id_popup = p.id_popup AND ph.hook = "' . pSQL($hookName) . '"
                LEFT JOIN ' . _DB_PREFIX_ . 'josra_popup_shop psh ON psh.id_popup = p.id_popup
                WHERE p.active = 1
                AND (p.date_from IS NULL OR p.date_from = "0000-00-00 00:00:00" OR p.date_from <= "' . pSQL($now) . '")
                AND (p.date_to IS NULL OR p.date_to = "0000-00-00 00:00:00" OR p.date_to >= "' . pSQL($now) . '")
                AND (psh.id_shop IS NULL OR psh.id_shop = ' . $idShop . ')
                GROUP BY p.id_popup
                ORDER BY p.priority ASC';

        $ids = array_column(Db::getInstance()->executeS($sql), 'id_popup');
        if (empty($ids)) {
            return [];
        }

        $result = [];
        foreach ($ids as $idPopup) {
            $popup = new JosraPopupModel((int) $idPopup, $idLang, $idShop);
            if (!Validate::isLoadedObject($popup)) {
                continue;
            }

            if (!$this->matchesGroup($popup, $idGroup)) {
                continue;
            }
            if (!$this->matchesPage($popup, $currentController)) {
                continue;
            }
            if ($currentController === 'category' && !$this->matchesCategory($popup, $idCategory)) {
                continue;
            }
            if ($currentController === 'product' && !$this->matchesProduct($popup, $idProduct)) {
                continue;
            }
            if ($idCustomer && !$this->matchesFrequencyForCustomer($popup, $idCustomer)) {
                continue;
            }

            $result[] = $this->formatPopupForFront($popup);
        }

        return $result;
    }

    protected function matchesGroup(JosraPopupModel $popup, $idGroup)
    {
        $groups = $popup->getGroups();
        return empty($groups) || in_array((int) $idGroup, $groups);
    }

    protected function matchesPage(JosraPopupModel $popup, $currentController)
    {
        $pages = $popup->getPages();
        return empty($pages) || in_array($currentController, $pages);
    }

    protected function matchesCategory(JosraPopupModel $popup, $idCategory)
    {
        $categories = $popup->getCategories();
        return empty($categories) || in_array((int) $idCategory, $categories);
    }

    protected function matchesProduct(JosraPopupModel $popup, $idProduct)
    {
        $products = $popup->getProducts();
        return empty($products) || in_array((int) $idProduct, $products);
    }

    /**
     * Solo aplicable a clientes identificados (invitados se controlan 100% vía JS/cookie).
     */
    protected function matchesFrequencyForCustomer(JosraPopupModel $popup, $idCustomer)
    {
        switch ($popup->frequency_type) {
            case 'always':
            case 'session':
                // 'session' se controla en el navegador (sessionStorage); en BBDD no restringimos.
                return true;

            case 'max_times':
                return JosraPopupModel::countDisplaysForCustomer($popup->id, $idCustomer) < (int) $popup->frequency_max_times;

            case 'days':
                $last = JosraPopupModel::lastDisplayForCustomer($popup->id, $idCustomer);
                if (!$last) {
                    return true;
                }
                $diffDays = (time() - strtotime($last)) / 86400;
                return $diffDays >= (int) $popup->frequency_days;

            default:
                return true;
        }
    }

    protected function getCurrentPageIdentifier()
    {
        $controller = Tools::getValue('controller');
        if ($controller) {
            return $controller;
        }
        // Fallback para la home cuando no llega ?controller=
        if ($this->context->controller instanceof FrontController) {
            return $this->context->controller->php_self;
        }
        return 'index';
    }

    protected function formatPopupForFront(JosraPopupModel $popup)
    {
        $image = null;
        if ($popup->content_type === 'image' && $popup->image) {
            $image = [
                'url' => Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/upload/' . $popup->image,
                'link' => $popup->image_link,
                'width' => (int) $popup->image_width,
                'height' => (int) $popup->image_height,
                'responsive' => (bool) $popup->image_responsive,
            ];
        }

        return [
            'id' => (int) $popup->id,
            'content_type' => $popup->content_type,
            'title' => $popup->title,
            'content' => $popup->content,
            'image' => $image,
            'button_text' => $popup->button_text,
            'button_link' => $popup->button_link,

            'trigger_type' => $popup->trigger_type,
            'trigger_delay' => (int) $popup->trigger_delay,
            'trigger_scroll_percent' => (int) $popup->trigger_scroll_percent,
            'trigger_click_selector' => $popup->trigger_click_selector,

            'frequency_type' => $popup->frequency_type,
            'frequency_days' => (int) $popup->frequency_days,
            'frequency_max_times' => (int) $popup->frequency_max_times,

            'overlay' => (bool) $popup->overlay,
            'closable_outside_click' => (bool) $popup->closable_outside_click,
            'popup_width' => (int) $popup->popup_width,
            'popup_position' => $popup->popup_position,

            'is_customer_logged' => (bool) $this->context->customer->id,
            'ajax_log_url' => $this->context->link->getModuleLink($this->name, 'ajax', ['action' => 'logDisplay', 'ajax' => 1]),
        ];
    }
}
