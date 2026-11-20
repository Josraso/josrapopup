<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminJosraPopupController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'josra_popup';
        $this->identifier = 'id_popup';
        $this->className = 'JosraPopupModel';
        $this->lang = true;
        $this->bootstrap = true;
        $this->list_no_link = true;

        parent::__construct();

        $this->fields_list = [
            'id_popup' => ['title' => $this->module->l('ID'), 'align' => 'center', 'width' => 40],
            'name' => ['title' => $this->module->l('Nombre')],
            'content_type' => ['title' => $this->module->l('Tipo'), 'align' => 'center', 'width' => 90],
            'trigger_type' => ['title' => $this->module->l('Disparador'), 'align' => 'center', 'width' => 110],
            'date_from' => ['title' => $this->module->l('Desde'), 'align' => 'center', 'width' => 130, 'type' => 'datetime'],
            'date_to' => ['title' => $this->module->l('Hasta'), 'align' => 'center', 'width' => 130, 'type' => 'datetime'],
            'priority' => ['title' => $this->module->l('Prioridad'), 'align' => 'center', 'width' => 70],
            'active' => ['title' => $this->module->l('Activo'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'width' => 70],
        ];

        $this->bulk_actions = [
            'delete' => ['text' => $this->module->l('Eliminar seleccionados'), 'confirm' => $this->module->l('¿Eliminar los popups seleccionados?')],
            'enableSelection' => ['text' => $this->module->l('Activar seleccionados')],
            'disableSelection' => ['text' => $this->module->l('Desactivar seleccionados')],
        ];
    }

    /** PrestaShop 9 ya no carga solo el plugin de etiquetas que usan los campos de categorías/productos */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJqueryPlugin('tagify');
    }

    /**
     * Formulario en pestañas (Contenido / Programación / Segmentación / Diseño).
     * Las pestañas son un tipo de campo nativo y muy usado en el core de PrestaShop
     * (AdminProducts, AdminEmployees...), así que es un patrón fiable entre versiones.
     */
    public function renderForm()
    {
        /** @var JosraPopupModel $popup */
        $popup = $this->loadObject(true);

        $groupsOptions = [];
        foreach (Group::getGroups($this->context->language->id) as $group) {
            $groupsOptions[] = ['id' => $group['id_group'], 'name' => $group['name']];
        }

        $pagesOptions = [
            ['id' => 'index', 'name' => $this->module->l('Home')],
            ['id' => 'category', 'name' => $this->module->l('Página de categoría')],
            ['id' => 'product', 'name' => $this->module->l('Ficha de producto')],
            ['id' => 'cart', 'name' => $this->module->l('Carrito')],
            ['id' => 'order', 'name' => $this->module->l('Checkout / Pedido')],
            ['id' => 'cms', 'name' => $this->module->l('Páginas CMS')],
            ['id' => 'search', 'name' => $this->module->l('Resultados de búsqueda')],
            ['id' => 'contact', 'name' => $this->module->l('Contacto')],
            ['id' => 'my-account', 'name' => $this->module->l('Mi cuenta')],
        ];

        $hooksOptions = [];
        foreach (Josrapopup::AVAILABLE_HOOKS as $hook) {
            $hooksOptions[] = ['id' => $hook, 'name' => $hook];
        }

        $hasImage = (bool) $popup->image;
        $imagePreviewUrl = $hasImage ? $this->context->link->getBaseLink() . 'modules/josrapopup/upload/' . $popup->image : false;

        $tabs = [
            'Contenido' => $this->module->l('Contenido'),
            'Programacion' => $this->module->l('Programación'),
            'Segmentacion' => $this->module->l('Segmentación'),
            'Diseno' => $this->module->l('Diseño'),
        ];

        $this->fields_form = [
            'legend' => ['title' => $this->module->l('Popup'), 'icon' => 'icon-bell'],
            'tabs' => $tabs,
            'input' => [
                // --- Contenido ---
                ['type' => 'text', 'label' => $this->module->l('Nombre interno'), 'name' => 'name', 'lang' => true, 'required' => true, 'hint' => $this->module->l('Solo para identificarlo en el listado, no se muestra al cliente.'), 'tab' => 'Contenido'],
                ['type' => 'switch', 'label' => $this->module->l('Activo'), 'name' => 'active', 'is_bool' => true, 'tab' => 'Contenido', 'values' => [
                    ['id' => 'active_on', 'value' => 1, 'label' => $this->module->l('Sí')],
                    ['id' => 'active_off', 'value' => 0, 'label' => $this->module->l('No')],
                ]],
                ['type' => 'radio', 'label' => $this->module->l('Tipo de contenido'), 'name' => 'content_type', 'tab' => 'Contenido', 'values' => [
                    ['id' => 'content_type_html', 'value' => 'html', 'label' => $this->module->l('Texto / HTML')],
                    ['id' => 'content_type_image', 'value' => 'image', 'label' => $this->module->l('Imagen')],
                ]],
                ['type' => 'text', 'label' => $this->module->l('Título (opcional)'), 'name' => 'title', 'lang' => true, 'tab' => 'Contenido'],
                ['type' => 'textarea', 'label' => $this->module->l('Contenido HTML'), 'name' => 'content', 'lang' => true, 'autoload_rte' => true, 'tab' => 'Contenido'],
                ['type' => 'file', 'label' => $this->module->l('Imagen'), 'name' => 'image', 'tab' => 'Contenido', 'image' => $imagePreviewUrl],
                ['type' => 'switch', 'label' => $this->module->l('Eliminar imagen actual'), 'name' => 'delete_image', 'is_bool' => true, 'tab' => 'Contenido', 'hint' => $this->module->l('Actívalo y guarda para quitar la imagen sin subir otra.'), 'values' => [
                    ['id' => 'delete_image_on', 'value' => 1, 'label' => $this->module->l('Sí')],
                    ['id' => 'delete_image_off', 'value' => 0, 'label' => $this->module->l('No')],
                ]],
                ['type' => 'text', 'label' => $this->module->l('Enlace al hacer click en la imagen'), 'name' => 'image_link', 'tab' => 'Contenido'],
                ['type' => 'text', 'label' => $this->module->l('Ancho imagen (px, 0 = auto)'), 'name' => 'image_width', 'class' => 'fixed-width-sm', 'tab' => 'Contenido'],
                ['type' => 'text', 'label' => $this->module->l('Alto imagen (px, 0 = auto)'), 'name' => 'image_height', 'class' => 'fixed-width-sm', 'tab' => 'Contenido'],
                ['type' => 'switch', 'label' => $this->module->l('Adaptar imagen a pantalla (responsive)'), 'name' => 'image_responsive', 'is_bool' => true, 'tab' => 'Contenido', 'values' => [
                    ['id' => 'image_responsive_on', 'value' => 1, 'label' => $this->module->l('Sí')],
                    ['id' => 'image_responsive_off', 'value' => 0, 'label' => $this->module->l('No')],
                ]],
                ['type' => 'text', 'label' => $this->module->l('Texto del botón (opcional)'), 'name' => 'button_text', 'lang' => true, 'tab' => 'Contenido'],
                ['type' => 'text', 'label' => $this->module->l('Enlace del botón'), 'name' => 'button_link', 'lang' => true, 'tab' => 'Contenido'],

                // --- Programación ---
                ['type' => 'datetime', 'label' => $this->module->l('Fecha y hora de inicio'), 'name' => 'date_from', 'tab' => 'Programacion', 'hint' => $this->module->l('Déjalo vacío para que empiece ya.')],
                ['type' => 'datetime', 'label' => $this->module->l('Fecha y hora de fin'), 'name' => 'date_to', 'tab' => 'Programacion', 'hint' => $this->module->l('Déjalo vacío para que no caduque.')],
                ['type' => 'text', 'label' => $this->module->l('Prioridad'), 'name' => 'priority', 'class' => 'fixed-width-sm', 'tab' => 'Programacion', 'hint' => $this->module->l('Si varios popups compiten en la misma página, se respeta el de menor número.')],

                ['type' => 'select', 'label' => $this->module->l('Disparador (cuándo salta)'), 'name' => 'trigger_type', 'tab' => 'Programacion', 'options' => [
                    'query' => [
                        ['id' => 'load', 'name' => $this->module->l('Al cargar la página')],
                        ['id' => 'exit_intent', 'name' => $this->module->l('Exit-intent (al ir a cerrar pestaña)')],
                        ['id' => 'scroll', 'name' => $this->module->l('Al hacer scroll hasta X%')],
                        ['id' => 'click', 'name' => $this->module->l('Al hacer clic en un elemento')],
                    ],
                    'id' => 'id', 'name' => 'name',
                ]],
                ['type' => 'text', 'label' => $this->module->l('Retardo (segundos)'), 'name' => 'trigger_delay', 'class' => 'fixed-width-sm', 'tab' => 'Programacion'],
                ['type' => 'text', 'label' => $this->module->l('Porcentaje de scroll'), 'name' => 'trigger_scroll_percent', 'class' => 'fixed-width-sm', 'tab' => 'Programacion'],
                ['type' => 'text', 'label' => $this->module->l('Selector CSS del elemento'), 'name' => 'trigger_click_selector', 'tab' => 'Programacion', 'hint' => $this->module->l('Ej: .abrir-popup-newsletter')],

                ['type' => 'select', 'label' => $this->module->l('Frecuencia de visualización'), 'name' => 'frequency_type', 'tab' => 'Programacion', 'options' => [
                    'query' => [
                        ['id' => 'always', 'name' => $this->module->l('Siempre (cada carga de página)')],
                        ['id' => 'session', 'name' => $this->module->l('Una vez por sesión de navegación')],
                        ['id' => 'days', 'name' => $this->module->l('No repetir durante X días')],
                        ['id' => 'max_times', 'name' => $this->module->l('Máximo X veces en total')],
                    ],
                    'id' => 'id', 'name' => 'name',
                ]],
                ['type' => 'text', 'label' => $this->module->l('Días sin repetir'), 'name' => 'frequency_days', 'class' => 'fixed-width-sm', 'tab' => 'Programacion'],
                ['type' => 'text', 'label' => $this->module->l('Máximo de veces'), 'name' => 'frequency_max_times', 'class' => 'fixed-width-sm', 'tab' => 'Programacion'],

                // --- Segmentación (categorías/productos van aquí, grupos/páginas/hooks se inyectan a mano justo debajo) ---
                ['type' => 'tags', 'label' => $this->module->l('Categorías (IDs, separadas por coma)'), 'name' => 'categories', 'tab' => 'Segmentacion', 'hint' => $this->module->l('Vacío = todas las categorías. Solo aplica en páginas de categoría.')],
                ['type' => 'tags', 'label' => $this->module->l('Productos (IDs, separados por coma)'), 'name' => 'products', 'tab' => 'Segmentacion', 'hint' => $this->module->l('Vacío = todos los productos. Solo aplica en fichas de producto.')],

                // --- Diseño ---
                ['type' => 'switch', 'label' => $this->module->l('Fondo oscuro (overlay)'), 'name' => 'overlay', 'is_bool' => true, 'tab' => 'Diseno', 'values' => [
                    ['id' => 'overlay_on', 'value' => 1, 'label' => $this->module->l('Sí')],
                    ['id' => 'overlay_off', 'value' => 0, 'label' => $this->module->l('No')],
                ]],
                ['type' => 'switch', 'label' => $this->module->l('Cerrar al hacer clic fuera'), 'name' => 'closable_outside_click', 'is_bool' => true, 'tab' => 'Diseno', 'values' => [
                    ['id' => 'closable_on', 'value' => 1, 'label' => $this->module->l('Sí')],
                    ['id' => 'closable_off', 'value' => 0, 'label' => $this->module->l('No')],
                ]],
                ['type' => 'text', 'label' => $this->module->l('Ancho del popup (px)'), 'name' => 'popup_width', 'class' => 'fixed-width-sm', 'tab' => 'Diseno'],
                ['type' => 'select', 'label' => $this->module->l('Posición'), 'name' => 'popup_position', 'tab' => 'Diseno', 'options' => [
                    'query' => [
                        ['id' => 'center', 'name' => $this->module->l('Centro')],
                        ['id' => 'bottom-right', 'name' => $this->module->l('Abajo derecha')],
                        ['id' => 'bottom-left', 'name' => $this->module->l('Abajo izquierda')],
                        ['id' => 'top', 'name' => $this->module->l('Arriba')],
                    ],
                    'id' => 'id', 'name' => 'name',
                ]],
            ],
            'submit' => ['title' => $this->module->l('Guardar')],
        ];

        // Precargar valores de las relaciones N-a-N que no son campos nativos del ObjectModel
        $selectedGroups = [];
        $selectedPages = [];
        $selectedHooks = ['displayFooter'];
        if (Validate::isLoadedObject($popup)) {
            $selectedGroups = $popup->getGroups();
            $selectedPages = $popup->getPages();
            $selectedHooks = $popup->getHooks() ?: ['displayFooter'];
            $this->fields_value['categories'] = implode(',', $popup->getCategories());
            $this->fields_value['products'] = implode(',', $popup->getProducts());
        }

        $this->fields_value['delete_image'] = 0;

        // Popups guardados con versiones anteriores tienen "0000-00-00 00:00:00" en vez de vacío:
        // se enseña vacío (si no, al volver a guardar daría "campo no válido")
        foreach (['date_from', 'date_to'] as $dateField) {
            if (!Tools::getIsset($dateField) && self::isEmptyDate($popup->$dateField)) {
                $this->fields_value[$dateField] = '';
            }
        }

        $formHtml = parent::renderForm();

        // Popup ya guardado: enlace para verlo en la tienda aunque no esté activo
        if (Validate::isLoadedObject($popup)) {
            $formHtml = '<div class="alert alert-info">'
                . $this->l('Para ver este popup en la tienda aunque esté desactivado, fuera de fechas o solo para ciertos clientes, abre la vista previa (también en el móvil). Muestra lo último que hayas guardado.')
                . ' <a class="btn btn-default" href="' . Tools::safeOutput($this->module->getPreviewUrl((int) $popup->id)) . '" target="_blank" rel="noopener"><i class="icon-eye"></i> ' . $this->l('Vista previa') . '</a>'
                . '</div>' . $formHtml;
        }

        $segmentationHtml = $this->renderSegmentationCheckboxes($groupsOptions, $selectedGroups, $pagesOptions, $selectedPages, $hooksOptions, $selectedHooks);
        $formHtml = $this->injectIntoTab($formHtml, 'Segmentacion', $segmentationHtml);

        $formHtml .= $this->renderConditionalFieldsScript();

        return $formHtml;
    }

    /**
     * Intenta insertar HTML justo al principio del contenido de una pestaña concreta
     * del formulario (identificada por su id "#{table}_id_{TabName}"). Si por lo que
     * sea no encuentra el marcador (tema distinto, etc.), no rompe nada: simplemente
     * añade el bloque justo antes del pie del formulario como red de seguridad.
     */
    protected function injectIntoTab($formHtml, $tabName, $html)
    {
        $marker = 'id="' . $this->table . '_id_' . $tabName . '"';
        $pos = strpos($formHtml, $marker);

        if ($pos === false) {
            if (strpos($formHtml, '<div class="panel-footer">') !== false) {
                return str_replace('<div class="panel-footer">', $html . '<div class="panel-footer">', $formHtml);
            }
            return $formHtml . $html;
        }

        $tabPaneStart = strpos($formHtml, '>', $pos) + 1;
        return substr($formHtml, 0, $tabPaneStart) . $html . substr($formHtml, $tabPaneStart);
    }

    /**
     * Renderiza a mano (sin depender del tipo 'checkbox' del HelperForm, que está pensado
     * para un único checkbox y no para listas de selección múltiple) los bloques de
     * grupos, páginas y hooks. Los inputs usan el atributo HTML5 "form" para enlazarse
     * al <form> generado por PrestaShop (id = "{table}_form") aunque estén fuera de él,
     * así se envían igualmente al guardar.
     */
    protected function renderSegmentationCheckboxes($groupsOptions, $selectedGroups, $pagesOptions, $selectedPages, $hooksOptions, $selectedHooks)
    {
        $formId = $this->table . '_form';

        $renderGroup = function ($title, $hint, $name, $options, $selected) use ($formId) {
            $html = '<div class="jp-check-block">';
            $html .= '<h4 class="jp-check-title">' . htmlspecialchars($title) . '</h4>';
            if ($hint) {
                $html .= '<p class="help-block" style="margin-top:-4px;">' . $hint . '</p>';
            }
            $html .= '<div class="jp-check-grid">';
            foreach ($options as $option) {
                $id = $name . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $option['id']);
                $checked = in_array($option['id'], $selected) ? ' checked="checked"' : '';
                $html .= '<label class="jp-check-pill" for="' . $id . '">'
                    . '<input type="checkbox" form="' . $formId . '" name="' . $name . '[]" id="' . $id . '" value="' . htmlspecialchars($option['id']) . '"' . $checked . '>'
                    . '<span>' . htmlspecialchars($option['name']) . '</span>'
                    . '</label>';
            }
            $html .= '</div></div>';
            return $html;
        };

        $style = '<style>
            .jp-check-block { margin-bottom: 25px; }
            .jp-check-title { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
            .jp-check-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
            .jp-check-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #dadada; border-radius: 20px; cursor: pointer; font-weight: normal; font-size: 13px; background: #fff; transition: all .15s ease; }
            .jp-check-pill:hover { border-color: #bbb; background: #f7f7f7; }
            .jp-check-pill input[type=checkbox] { margin: 0; }
            .jp-check-pill input[type=checkbox]:checked ~ span { font-weight: 600; }
        </style>';

        $html = $style . '<div class="jp-segmentation-wrapper" style="padding: 20px;">';

        $html .= $renderGroup(
            $this->module->l('Grupos de cliente'),
            $this->module->l('Deja todos sin marcar para aplicar a todos los grupos.'),
            'groups',
            $groupsOptions,
            $selectedGroups
        );

        $html .= $renderGroup(
            $this->module->l('Páginas donde puede aparecer'),
            $this->module->l('Deja todas sin marcar para permitir en cualquier página.'),
            'pages',
            $pagesOptions,
            $selectedPages
        );

        $html .= $renderGroup(
            $this->module->l('Hooks donde se engancha'),
            $this->module->l('Punto técnico de la plantilla donde se inyecta el popup. Si no sabes cuál elegir, deja marcado "displayFooter". Debe haber al menos uno marcado o el popup no se mostrará nunca.'),
            'hooks',
            $hooksOptions,
            $selectedHooks
        );

        $html .= '</div>';

        return $html;
    }

    /**
     * JS para mostrar/ocultar campos según lo que se haya elegido en otros campos
     * (tipo de contenido, disparador, frecuencia), para que el formulario no muestre
     * de golpe 30 campos de los que solo 8 aplican en cada caso.
     */
    protected function renderConditionalFieldsScript()
    {
        return <<<'HTML'
<script>
(function () {
    function groupFor(name) {
        var el = document.querySelector('[name="' + name + '"]') || document.querySelector('[name^="' + name + '_"]');
        return el ? el.closest('.form-group') : null;
    }
    function show(name, visible) {
        var g = groupFor(name);
        if (g) {
            g.style.display = visible ? '' : 'none';
        }
    }

    function refreshContentType() {
        var checked = document.querySelector('input[name="content_type"]:checked');
        var val = checked ? checked.value : 'html';
        var isImage = val === 'image';
        ['title', 'content'].forEach(function (n) { show(n, !isImage); });
        ['image', 'delete_image', 'image_link', 'image_width', 'image_height', 'image_responsive'].forEach(function (n) { show(n, isImage); });
    }

    function refreshTrigger() {
        var sel = document.querySelector('[name="trigger_type"]');
        var val = sel ? sel.value : 'load';
        show('trigger_delay', val === 'load');
        show('trigger_scroll_percent', val === 'scroll');
        show('trigger_click_selector', val === 'click');
    }

    function refreshFrequency() {
        var sel = document.querySelector('[name="frequency_type"]');
        var val = sel ? sel.value : 'session';
        show('frequency_days', val === 'days');
        show('frequency_max_times', val === 'max_times');
    }

    function init() {
        refreshContentType();
        refreshTrigger();
        refreshFrequency();

        document.querySelectorAll('input[name="content_type"]').forEach(function (el) {
            el.addEventListener('change', refreshContentType);
        });
        var triggerSel = document.querySelector('[name="trigger_type"]');
        if (triggerSel) {
            triggerSel.addEventListener('change', refreshTrigger);
        }
        var freqSel = document.querySelector('[name="frequency_type"]');
        if (freqSel) {
            freqSel.addEventListener('change', refreshFrequency);
        }
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
</script>
HTML;
    }

    /**
     * Tras guardar el objeto principal, persistimos también las tablas de relación
     * y gestionamos la imagen (subida, sustitución, borrado, y protegerla frente
     * a pérdidas accidentales cuando no se toca en una edición).
     */
    public function processSave()
    {
        // Fechas vacías o a cero ("0000-00-00 00:00:00") = sin límite. Se dejan vacías antes de
        // validar: PrestaShop no da por buena una fecha a cero y no dejaba volver a guardar el popup.
        $emptyDates = [];
        foreach (['date_from', 'date_to'] as $dateField) {
            $value = trim((string) Tools::getValue($dateField));
            if (self::isEmptyDate($value)) {
                $_POST[$dateField] = '';
                $emptyDates[] = $dateField;
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
                // Fecha con hora pero sin segundos
                $_POST[$dateField] = $value . ':00';
            }
        }

        $idPopup = (int) Tools::getValue($this->identifier);
        $previousImage = null;
        if ($idPopup) {
            $previousImage = Db::getInstance()->getValue(
                'SELECT image FROM ' . _DB_PREFIX_ . 'josra_popup WHERE id_popup = ' . $idPopup
            );
        }

        /** @var JosraPopupModel $object */
        $object = parent::processSave();

        if ($object && Validate::isLoadedObject($object)) {
            $groups = Tools::getValue('groups', []);
            $object->setGroups(is_array($groups) ? array_map('intval', $groups) : []);

            $categories = array_filter(array_map('trim', explode(',', Tools::getValue('categories', ''))));
            $object->setCategories(array_map('intval', $categories));

            $products = array_filter(array_map('trim', explode(',', Tools::getValue('products', ''))));
            $object->setProducts(array_map('intval', $products));

            $pages = Tools::getValue('pages', []);
            $object->setPages(is_array($pages) ? $pages : []);

            $hooks = Tools::getValue('hooks', []);
            $hooks = is_array($hooks) ? $hooks : [];
            if (empty($hooks)) {
                // Sin esto el popup se guardaría pero no aparecería en ningún sitio (el JOIN
                // por hook no encontraría ninguna fila). Forzamos un valor por defecto seguro.
                $hooks = ['displayFooter'];
            }
            $object->setHooks($hooks);

            $this->handleImage($object, $previousImage);

            // Sin fecha se guarda NULL (PrestaShop la guardaría como "0000-00-00 00:00:00")
            if ($emptyDates) {
                $set = [];
                foreach ($emptyDates as $dateField) {
                    $set[] = '`' . bqSQL($dateField) . '` = NULL';
                    $object->$dateField = null;
                }
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'josra_popup` SET ' . implode(', ', $set) . ' WHERE `id_popup` = ' . (int) $object->id
                );
            }
        }

        return $object;
    }

    /**
     * Gestiona los 3 casos posibles del campo imagen en cada guardado:
     * 1) Se sube un archivo nuevo -> lo guarda y borra el anterior si existía.
     * 2) Se marca "eliminar imagen actual" y no se sube nada nuevo -> borra el archivo y limpia el campo.
     * 3) No se toca nada -> nos aseguramos de que el valor en BBDD sigue siendo el que ya tenía
     *    (el guardado estándar de PrestaShop, al no llegar el campo 'image' por POST en un <input type="file">,
     *    puede dejarlo vacío; lo restauramos explícitamente para que nunca se pierda sin querer).
     */
    protected function handleImage(JosraPopupModel $object, $previousImage)
    {
        $hasNewFile = !empty($_FILES['image']['tmp_name']);
        $wantsDelete = (bool) Tools::getValue('delete_image');

        if ($hasNewFile) {
            $uploadDir = _PS_MODULE_DIR_ . 'josrapopup/upload/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (ImageManager::isRealImage($_FILES['image']['tmp_name']) && in_array($ext, $allowedExt)) {
                $filename = 'popup_' . (int) $object->id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $object->image = $filename;
                    $object->save();
                    if ($previousImage && $previousImage !== $filename) {
                        JosraPopupModel::deleteImageFile($previousImage);
                    }
                }
            }
            return;
        }

        if ($wantsDelete && $previousImage) {
            JosraPopupModel::deleteImageFile($previousImage);
            $object->image = null;
            $object->save();
            return;
        }

        // Nadie ha tocado la imagen: garantizamos que no se ha perdido en el guardado estándar.
        if ($previousImage && $object->image != $previousImage) {
            $object->image = $previousImage;
            $object->save();
        }
    }

    /**
     * El borrado individual pasa por JosraPopupModel::delete() (ya limpia el fichero),
     * pero el borrado masivo de PrestaShop ejecuta un DELETE SQL directo sin instanciar
     * cada objeto, así que hay que limpiar los ficheros de imagen a mano aquí antes.
     */
    public function processBulkDelete()
    {
        if ($this->access('delete')) {
            $ids = Tools::getValue($this->table . 'Box', []);
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    $image = Db::getInstance()->getValue(
                        'SELECT image FROM ' . _DB_PREFIX_ . 'josra_popup WHERE id_popup = ' . (int) $id
                    );
                    if ($image) {
                        JosraPopupModel::deleteImageFile($image);
                    }
                }
            }
        }

        return parent::processBulkDelete();
    }

    /** Acción «Vista previa» de la lista: abre la tienda con el popup, aunque esté desactivado */
    public function displayPreviewLink($token, $id, $name = null)
    {
        return '<a href="' . Tools::safeOutput($this->module->getPreviewUrl((int) $id)) . '" target="_blank" rel="noopener" title="' . $this->l('Vista previa') . '">'
            . '<i class="icon-eye"></i> ' . $this->l('Vista previa') . '</a>';
    }

    /** Fecha vacía o a cero: el popup no tiene límite por ese lado */
    public static function isEmptyDate($value)
    {
        $value = trim((string) $value);

        return $value === '' || strpos($value, '0000-00-00') === 0;
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('preview');
        $this->addRowAction('delete');

        // Panel de actualizaciones del módulo: arriba si hay versión nueva, abajo si no
        $updater = $this->module->getUpdater();
        $info = $updater->getInfo();
        $panel = $updater->renderPanel();
        $list = parent::renderList();

        return !empty($info['update_available']) ? $panel . $list : $list . $panel;
    }
}
