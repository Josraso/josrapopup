<?php
/**
 * Josra Popup Manager
 * Modelo principal: representa un popup configurable.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class JosraPopupModel extends ObjectModel
{
    /** @var int */
    public $id;

    /** @var bool Activo / inactivo */
    public $active = 1;

    /** @var string Fecha inicio de campaña (puede estar vacía = sin límite) */
    public $date_from;

    /** @var string Fecha fin de campaña (puede estar vacía = sin límite) */
    public $date_to;

    /** @var int Prioridad, menor = se muestra antes si compiten varios popups */
    public $priority = 10;

    /** ---- Contenido ---- */
    /** @var string 'html' | 'image' */
    public $content_type = 'html';

    /** @var string Ruta relativa de la imagen (si content_type = image) */
    public $image;

    /** @var string URL destino al hacer click en la imagen (opcional) */
    public $image_link;

    /** @var int Ancho en px, 0 = auto */
    public $image_width = 0;

    /** @var int Alto en px, 0 = auto */
    public $image_height = 0;

    /** @var bool Si se debe limitar al tamaño de pantalla (max-width/max-height %) */
    public $image_responsive = 1;

    /** ---- Trigger ---- */
    /** @var string 'load' | 'exit_intent' | 'scroll' | 'click' */
    public $trigger_type = 'load';

    /** @var int Retardo en segundos (trigger = load) */
    public $trigger_delay = 0;

    /** @var int Porcentaje de scroll (trigger = scroll) */
    public $trigger_scroll_percent = 50;

    /** @var string Selector CSS del elemento (trigger = click) */
    public $trigger_click_selector;

    /** ---- Frecuencia ---- */
    /** @var string 'always' | 'session' | 'days' | 'max_times' */
    public $frequency_type = 'session';

    /** @var int Nº de días para no repetir (frequency_type = days) */
    public $frequency_days = 7;

    /** @var int Nº máximo de veces a mostrar (frequency_type = max_times) */
    public $frequency_max_times = 3;

    /** ---- Diseño ---- */
    public $overlay = 1;
    public $closable_outside_click = 1;
    public $popup_width = 600;
    public $popup_position = 'center'; // center | bottom-right | bottom-left | top

    public $date_add;
    public $date_upd;

    /** Campos traducibles */
    public $name;
    public $title;
    public $content;
    public $button_text;
    public $button_link;

    public static $definition = [
        'table' => 'josra_popup',
        'primary' => 'id_popup',
        'multilang' => true,
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_from' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true],
            'date_to' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true],
            'priority' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],

            'content_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'image' => ['type' => self::TYPE_STRING, 'validate' => 'isFileName', 'allow_null' => true],
            'image_link' => ['type' => self::TYPE_STRING, 'validate' => 'isUrlOrEmpty', 'allow_null' => true],
            'image_width' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'image_height' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'image_responsive' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            'trigger_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'trigger_delay' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'trigger_scroll_percent' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'trigger_click_selector' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],

            'frequency_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'frequency_days' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'frequency_max_times' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],

            'overlay' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'closable_outside_click' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'popup_width' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'popup_position' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],

            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            // Traducibles
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => 255],
            'content' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'button_text' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => 255],
            'button_link' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'size' => 255],
        ],
    ];

    /**
     * Grupos de cliente asociados (vacío = todos)
     */
    public function getGroups()
    {
        $sql = 'SELECT id_group FROM ' . _DB_PREFIX_ . 'josra_popup_group WHERE id_popup = ' . (int) $this->id;
        return array_map('intval', array_column(Db::getInstance()->executeS($sql), 'id_group'));
    }

    public function setGroups(array $idGroups)
    {
        Db::getInstance()->delete('josra_popup_group', 'id_popup = ' . (int) $this->id);
        foreach ($idGroups as $idGroup) {
            Db::getInstance()->insert('josra_popup_group', [
                'id_popup' => (int) $this->id,
                'id_group' => (int) $idGroup,
            ]);
        }
    }

    public function getCategories()
    {
        $sql = 'SELECT id_category FROM ' . _DB_PREFIX_ . 'josra_popup_category WHERE id_popup = ' . (int) $this->id;
        return array_map('intval', array_column(Db::getInstance()->executeS($sql), 'id_category'));
    }

    public function setCategories(array $idCategories)
    {
        Db::getInstance()->delete('josra_popup_category', 'id_popup = ' . (int) $this->id);
        foreach ($idCategories as $idCategory) {
            Db::getInstance()->insert('josra_popup_category', [
                'id_popup' => (int) $this->id,
                'id_category' => (int) $idCategory,
            ]);
        }
    }

    public function getProducts()
    {
        $sql = 'SELECT id_product FROM ' . _DB_PREFIX_ . 'josra_popup_product WHERE id_popup = ' . (int) $this->id;
        return array_map('intval', array_column(Db::getInstance()->executeS($sql), 'id_product'));
    }

    public function setProducts(array $idProducts)
    {
        Db::getInstance()->delete('josra_popup_product', 'id_popup = ' . (int) $this->id);
        foreach ($idProducts as $idProduct) {
            Db::getInstance()->insert('josra_popup_product', [
                'id_popup' => (int) $this->id,
                'id_product' => (int) $idProduct,
            ]);
        }
    }

    /**
     * Páginas: guardamos el nombre del controller (ej. 'index', 'product', 'category', 'cart', 'order', 'cms')
     * y opcionalmente un id_object para restringir a una página CMS/categoría concreta ya cubierto por otras tablas.
     */
    public function getPages()
    {
        $sql = 'SELECT page FROM ' . _DB_PREFIX_ . 'josra_popup_page WHERE id_popup = ' . (int) $this->id;
        return array_column(Db::getInstance()->executeS($sql), 'page');
    }

    public function setPages(array $pages)
    {
        Db::getInstance()->delete('josra_popup_page', 'id_popup = ' . (int) $this->id);
        foreach ($pages as $page) {
            Db::getInstance()->insert('josra_popup_page', [
                'id_popup' => (int) $this->id,
                'page' => pSQL($page),
            ]);
        }
    }

    public function getHooks()
    {
        $sql = 'SELECT hook FROM ' . _DB_PREFIX_ . 'josra_popup_hook WHERE id_popup = ' . (int) $this->id;
        return array_column(Db::getInstance()->executeS($sql), 'hook');
    }

    public function setHooks(array $hooks)
    {
        Db::getInstance()->delete('josra_popup_hook', 'id_popup = ' . (int) $this->id);
        foreach ($hooks as $hook) {
            Db::getInstance()->insert('josra_popup_hook', [
                'id_popup' => (int) $this->id,
                'hook' => pSQL($hook),
            ]);
        }
    }

    public function getShops()
    {
        $sql = 'SELECT id_shop FROM ' . _DB_PREFIX_ . 'josra_popup_shop WHERE id_popup = ' . (int) $this->id;
        return array_map('intval', array_column(Db::getInstance()->executeS($sql), 'id_shop'));
    }

    public function setShops(array $idShops)
    {
        Db::getInstance()->delete('josra_popup_shop', 'id_popup = ' . (int) $this->id);
        foreach ($idShops as $idShop) {
            Db::getInstance()->insert('josra_popup_shop', [
                'id_popup' => (int) $this->id,
                'id_shop' => (int) $idShop,
            ]);
        }
    }

    /**
     * Registra una impresión en el log (clientes identificados) o devuelve
     * el conteo de impresiones para un id_customer dado.
     */
    public static function countDisplaysForCustomer($idPopup, $idCustomer)
    {
        $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'josra_popup_display_log
                WHERE id_popup = ' . (int) $idPopup . ' AND id_customer = ' . (int) $idCustomer;
        return (int) Db::getInstance()->getValue($sql);
    }

    public static function lastDisplayForCustomer($idPopup, $idCustomer)
    {
        $sql = 'SELECT MAX(date_add) FROM ' . _DB_PREFIX_ . 'josra_popup_display_log
                WHERE id_popup = ' . (int) $idPopup . ' AND id_customer = ' . (int) $idCustomer;
        return Db::getInstance()->getValue($sql);
    }

    public static function logDisplay($idPopup, $idCustomer)
    {
        return Db::getInstance()->insert('josra_popup_display_log', [
            'id_popup' => (int) $idPopup,
            'id_customer' => (int) $idCustomer,
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function countTotalDisplays($idPopup)
    {
        $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'josra_popup_display_log WHERE id_popup = ' . (int) $idPopup;
        return (int) Db::getInstance()->getValue($sql);
    }

    /**
     * Ruta absoluta en disco a la imagen de este popup, o null si no tiene.
     */
    public function getImagePath()
    {
        if (!$this->image) {
            return null;
        }
        return _PS_MODULE_DIR_ . 'josrapopup/upload/' . $this->image;
    }

    /**
     * Borra físicamente un fichero de imagen del directorio de uploads, si existe.
     * Método estático para poder usarse también en borrados masivos sin cargar el objeto completo.
     */
    public static function deleteImageFile($filename)
    {
        if (!$filename) {
            return;
        }
        $path = _PS_MODULE_DIR_ . 'josrapopup/upload/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Al eliminar el popup, nos llevamos también su imagen del disco para no dejar huérfanos.
     */
    public function delete()
    {
        self::deleteImageFile($this->image);
        return parent::delete();
    }
}
