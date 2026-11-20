# Changelog — Josra Popup Manager

## 1.4.1 — 2026-11-20
- Arreglado: las imágenes puestas dentro del editor de texto también se salían por la derecha en el móvil; ahora se ajustan al ancho del popup sin deformarse (igual vídeos, iframes y tablas).
- Arreglado: al elegir «Imagen» en el tipo de contenido desaparecían los botones para volver a «Texto / HTML» y el editor seguía a la vista. Ahora los botones se quedan siempre y solo se ve lo que toca: con Texto, el título y el editor; con Imagen, la subida y las opciones de la imagen.

## 1.4.0 — 2026-11-20
- Arreglado: en el móvil las imágenes del popup se salían por la derecha y quedaban cortadas. Ahora se ajustan al ancho de la pantalla manteniendo la proporción, con menos marco blanco, y la X de cerrar se ve también sobre imágenes oscuras.
- Nuevo: vista previa. Desde la lista de popups o desde su ficha se abre la tienda con el popup, aunque esté desactivado, fuera de fechas o solo para ciertos clientes. El enlace sirve también en el móvil y no cuenta como visto.

## 1.3.0 — 2026-11-20
- Arreglado: al editar un popup guardado sin fecha de inicio o de fin, salía «campo no válido» y no dejaba guardar. Las fechas vacías se guardan ahora como «sin límite», y los popups que ya estaban así se arreglan solos al actualizar.
- Las fechas escritas sin segundos (2026-12-01 10:00) también se aceptan.
- Nuevo: el módulo avisa de las versiones nuevas y se puede actualizar desde la propia tienda (panel «Actualizaciones» bajo la lista de popups y, en PrestaShop 8.1 o superior, en Módulos > Actualizaciones).

## 1.2.0
- Compatible con PrestaShop 9.
- Sin entrada en el menú lateral: la gestión de popups se abre desde Módulos > Configurar.

## 1.1.0
- Las fechas de inicio y fin llevan también hora, para programar los popups a una hora concreta.

## 1.0.0
- Primera versión.
