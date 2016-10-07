<?php
/*
TODO.PHP
Escrito por John Mejia (C) Julio 2013

Script para manejo de un listado de pendientes (ToDo List) Simple

Referencias:

http://www.w3schools.com/html/html_colors.asp
http://en.wikipedia.org/wiki/Samples_of_monospaced_typefaces

*/

//--------------------------------------
// Variables globales y de configuración
//--------------------------------------
$_archivo = 'todo.txt'; // Archivo texto a guardar los datos
$_data = array(); // Contenedor

define('TAG_COMENTA', '>>>');
define('TAG_SEPARA', ':');
define('TAG_INICIO', '{');
define('TAG_CIERRE', '}');
define('G_PAPELERA', '[Papelera]');
define('G_SINCLASE', '[Sin Clasificar]');
define('BUSCAR_LIMITE', 30); // Maximo de entradas a mostrar
//--------------------------------------

Data_Leer();

$pagina = strtolower(Post_Leer('pagina'));
if ($pagina == '') { $pagina = 'inicio'; }

Pagina_Encabezado($pagina);

$fun = 'Contenido_'.ucfirst($pagina);
if (function_exists($fun)) {
    $fun();
    }
else {
    Pagina_Error("Página no encontrada: $pagina");
    }

// Pagina_Debug('request', $_REQUEST);

Pagina_Cierre();

// ************************************************************

function Contenido_Editar() {

    Contenido_Nuevo();
}

// ************************************************************

function Contenido_EGrupo() {
// Formulario para adición de nueva actividad

    Contenido_NGrupo();

}

// ************************************************************

function Contenido_NGrupo() {
// Formulario para adición de nueva actividad

    // Nombre del boton de guardar
    $label_guardar = 'Adicionar grupo';
    $pagina = 'ngrupo';

    // Listado de grupos
    $grupos = Data_Grupos(true);

    // Valida si indicó actividad para almacenar
    $p = Post_Leer('grp', array());

    $nombre = '';

    // Grupo actualmente selecto
    $grupo_actual = Post_Leer('grupo');
    if ($grupo_actual != '' && isset($grupos[$grupo_actual])) {
        // Primer lectura
        // Elimina todas las opciones diferentes al grupo actual
        $nombre = $grupos[$grupo_actual];
        $descripcion = Data_InfoGrupo($grupo_actual, '@INFO');
        $grupos = array($grupo_actual => $nombre);
        // Carga datos originales
        $p = array_merge(array('gnombre' => $nombre, 'gcomenta' => $descripcion), $p);
        // Pagina_Debug('editar', $p);
        // Pagina_Debug('editar', $_REQUEST);
        $label_guardar = 'Guardar cambios';
        $pagina = 'egrupo';
        }

    // Valida que haya recibido los valores esperados
    $params = array('gnombre', 'gcomenta', 'ctl');
    foreach ($params as $k => $llave) {
        if (!isset($p[$llave])) { $p[$llave] = ''; }
        }

    // Determina si procede a guardar valores
    if (isset($p['ok']) && $p['ok'] == $label_guardar)
    {
        if ($p['ctl'] == Data_CTL()) {
            // Guardar valores recibidos
            // Pagina_Debug('guardar', $p);
            if (Data_Validar($p)) {
                // Guarda los datos capturados
                Data_Guardar();
                // Regresa a la pagina de inicio
                Contenido_Inicio();
                return;
                }
            }
        else {
            Pagina_Error('No se pudo guardar los datos recibidos, es posible que ya hayan sido guardados. Revise e intente de nuevo');
            }
        }
    elseif (isset($p['ctl']) && $p['ctl'] != '') {
        // Cancelo operacion
        Contenido_Inicio();
        return;
        }

?>
<? Pagina_Titulo($pagina, $nombre) ?>

<form method="post" id="form-nuevo">
<div>
    Grupo: <input type="text" name="grp[gnombre]" value="<?= htmlspecialchars($p['gnombre']); ?>">
</div>
<div>
    <div>Comentario:</div>
    <textarea name="grp[gcomenta]"><?= htmlspecialchars($p['gcomenta']) ?></textarea>
</div>
<div>
    <input type="submit" name="grp[ok]" value="<?= $label_guardar ?>">
    <input type="button" value="Cancelar" onclick="document.getElementById('form-nuevo').submit();">
</div>
<input type="hidden" name="grp[ctl]" value="<?= Data_CTL() ?>">
<input type="hidden" name="grupo" value="<?= $grupo_actual ?>">
</form>
<?

}

// ************************************************************

function Contenido_Nuevo() {
// Formulario para adición de nueva actividad

    // Nombre del boton de guardar
    $label_guardar = 'Adicionar actividad';
    $label_grupo = 'Grupo';
    $pagina = 'nuevo';

    // Listado de grupos
    $grupos = Data_Grupos(true);

    $estados = array(
        ''  => 'Pendiente',
        'OK'  => 'Realizada',
        'NOK' => 'Cancelada',
        'SUS' => 'Suspendida',
        'INF' => 'Informativo',
        );

    $prioridad = array(
        1 => 'Ninguna',
        0 => 'Baja',
        2 => 'Media',
        3 => 'Importante',
        );

    // Valida si indicó actividad para almacenar
    $p = Post_Leer('p', array());

    // Registro a editar (si aplica)
    $pos = Post_Leer('pos', 0);

    // Grupo actualmente selecto
    $grupo_actual = Post_Leer('grupo');

    if ($grupo_actual != '' && isset($grupos[$grupo_actual])) {
        // Primer lectura
        if ($pos > 0) {
            // Elimina todas las opciones diferentes al grupo actual
            $nombre = $grupos[$grupo_actual];
            // $grupos = array($grupo_actual => $nombre);

            // Captura grupo a mover esta actividad
            if (isset($p['grupo'])) { $p['gmover'] = $p['grupo']; }

            // Carga datos originales
            $info_pre = Data_InfoGrupo($grupo_actual, $pos);
            if (is_array($info_pre)) { $p = array_merge($info_pre, $p); }
            // Pagina_Debug('editar', $p);
            // Pagina_Debug('editar', $_REQUEST);
            $label_guardar = 'Guardar cambios';
            $label_grupo = 'Mover a grupo';
            $pagina = 'editar';

            }
        else {
            $pos = '';
            }

        // Asegura valor de grupo
        $p['grupo'] = $grupo_actual;
        }
    else {
        $grupo_base = G_SINCLASE;
        $llave_grupo = Data_LlaveGrupo($grupo_base);
        if (isset($grupos[$llave_grupo])) {
            $p['grupo'] = $llave_grupo;
            }
        }

    // Valida que haya recibido los valores esperados
    $params = array('grupo', 'actividad', 'estado', 'prioridad', 'comenta', 'ctl', 'gmover');
    foreach ($params as $k => $llave) {
        if (!isset($p[$llave])) { $p[$llave] = ''; }
        }

    // Determina si procede a guardar valores
    if (isset($p['ok']) && $p['ok'] == $label_guardar)
    {
        if ($p['ctl'] == Data_CTL()) {
            // Guardar valores recibidos
            // Pagina_Debug('guardar', $p);
            if (Data_Validar($p)) {
                // Guarda los datos capturados
                Data_Guardar();
                // Regresa a la pagina de inicio
                if ($grupo_actual != '') { Contenido_Grupo(); }
                else { Contenido_Inicio(); }
                return;
                }
            }
        else {
            Pagina_Error('No se pudo guardar los datos recibidos, es posible que ya hayan sido guardados. Revise e intente de nuevo');
            }
        }
    elseif (isset($p['ctl']) && $p['ctl'] != '') {
        // Canceló operacion
        Contenido_Grupo();
        return;
        }

?>
<? Pagina_Titulo($pagina, Pagina_Actividad($pos)) ?>

<form method="post" id="form-nuevo">
<div>
    <?= $label_grupo ?>: <?= Form_Listado('p[grupo]', $grupos, $p['grupo']); ?>
</div>
<div>
    <textarea name="p[actividad]"><?= htmlspecialchars($p['actividad']) ?></textarea>
	<p class="ayuda">Use: * para listar items, -- para resaltar anotaciones</p>
</div>
<div>
    Estado: <?= Form_Listado('p[estado]', $estados, $p['estado']); ?>
    &nbsp;
    Prioridad: <?= Form_Listado('p[prioridad]', $prioridad, $p['prioridad']); ?>
</div>
<div>
    <div>Comentario:</div>
    <textarea name="p[comenta]"><?= htmlspecialchars($p['comenta']) ?></textarea>
</div>
<div>
    <input type="submit" name="p[ok]" value="<?= $label_guardar ?>">
    <input type="button" value="Cancelar" onclick="document.getElementById('form-nuevo').submit();">
</div>
<input type="hidden" name="p[ctl]" value="<?= Data_CTL() ?>">
<input type="hidden" name="grupo" value="<?= $grupo_actual ?>">
<input type="hidden" name="pos" value="<?= $pos ?>">
</form>
<?
}

// ************************************************************

function Contenido_Grupo() {
// Pagina de inicio

    $contenido = '';

	$descripcion = '';

    $grupos = Data_Grupos(); // Requerido para identificar los grupos estandar

    $grupo_actual = Post_Leer('grupo');

    $estados = array(
        ''  => '', // Pendiente
        'OK'  => 'Realizada',
        'NOK' => 'Cancelada',
        'SUS' => 'Suspendida',
        'INF' => 'Informativo',
        );

    $prioridad = array(
        1 => '', // Ninguna
        0 => 'Baja',
        2 => 'Media',
        3 => 'Importante',
        );

    $pendientes = 0;
    $total = 0;
    $info_grupo = false;

    $buscar = trim(Post_Leer('buscar', ''));

    if ($buscar != '') {
        // Busca entradas que coincidan
        $info_grupo = Data_Buscar($buscar, $grupo_actual);
        }
    elseif ($grupo_actual != '') {
        // Indicó grupo a buscar
        $info_grupo = Data_InfoGrupo($grupo_actual);
        if (is_array($info_grupo)) {
			if (isset($info_grupo['@INFO'])) {
				$descripcion = $info_grupo['@INFO'];
				unset($info_grupo['@INFO']);
				}
            }
        }

    if (is_array($info_grupo) && count($info_grupo) > 0) {

        $lista_ordenada = array();
        foreach ($info_grupo as $pos => $p) {
            if (isset($p['actividad'])) {
                $llave = $p['prioridad'].'-'.sprintf('%010d', $pos);
                $lista_ordenada[$llave] = $pos;
                }
            }

        krsort($lista_ordenada);

        // Pagina_Debug('ordenado', $lista_ordenada);

        $llave_papelera = Grupo_Papelera();

        foreach ($lista_ordenada as $llave => $pos) {
            $p = $info_grupo[$pos];
            $estado_p = '';
            $comenta_p = '';
            $prioridad_p = '';

            $enlace_pos = '<a href="?pagina=editar&pos='.$pos.'&grupo='.$grupo_actual.'">'.Pagina_Actividad($pos).'</a>';
            $enlace_accion = '<a href="?pagina=eliminar&pos='.$pos.'&grupo='.$grupo_actual.'">Eliminar</a>';

            if ($grupo_actual == $llave_papelera) {
                $enlace_accion = '';
                }

            // Grupo (solo para busquedas globales)
            if ($grupo_actual == '' && isset($p['grupo-llave']) && $p['grupo-llave'] != '') {
				$llave_grupo = $p['grupo-llave'];
                $nombre_grupo = Data_GrupoNombre($llave_grupo);
                $estado_p .= '<span class="label label_grupo">'.$nombre_grupo.'</span> ';
				$enlace_pos = '<a href="?pagina=editar&pos='.$pos.'&grupo='.$llave_grupo.'">'.Pagina_Actividad($pos).'</a>';
                }

            // Datos de papelera
            if (isset($p['del-grupo']) && $p['del-grupo'] != '') {
                $nombre_grupo = Data_GrupoNombre($p['del-grupo']);
                $estado_p .= '<span class="label label_grupo">'.$nombre_grupo.'</span> ';
                $enlace_pos = Pagina_Actividad($pos);
                $enlace_accion = '<a href="?pagina=restaurar&pos='.$pos.'&grupo='.$grupo_actual.'">Restaurar</a>';
                }

            // Estado
            if (isset($estados[$p['estado']]) && $estados[$p['estado']] != '') {
                $estado_p .= '<span class="label">'.$estados[$p['estado']].'</span> ';
                }

            if ($p['estado'] == '') { $pendientes ++; }
            $total ++;

            // Comentario
            if ($p['comenta'] != '') {
                $comenta_p = '<div class="descripcion">'.Pagina_Texto($p['comenta']).'</div>';
                }

            // Prioridad
            $p['prioridad'] = 1 * $p['prioridad'];
            if (isset($prioridad[$p['prioridad']]) && $prioridad[$p['prioridad']] != '') {
                $prioridad_p = '<span class="label prioridad_'.$p['prioridad'].'">'.$prioridad[$p['prioridad']].'</span> ';
                }

            $contenido .= '<tr class="estado_'.strtolower($p['estado']).'">'.
                '<td class="pos numero">'.$enlace_pos.'</td>'.
                // '<td class="mini" align="center">'.$estado_p.'</td>'.
                // '<td class="mini" align="center"><span class="prioridad_'.$p['prioridad'].'">'.$prioridad[$p['prioridad']].'</span></td>'.
                '<td>'.
                Pagina_Texto($p['actividad'], $estado_p.$prioridad_p).
                $comenta_p.
                '</td>'.
                // '<td>'.$p['fecha-crea'].'</td>'.
                '<td class="fecha">'.Data_Fecha($p['fecha-mod']).'</td>'.
                '<td class="eliminar">'.$enlace_accion.'</td>'.
                '</tr>';
            }
        }

        // Adiciona titulos
        /*if ($contenido != '') {
            $contenido = '<tr>'.
                    '<th class="pos">No.</th>'.
                    // '<th class="mini">Estado</th>'.
                    // '<th class="mini">Prioridad</th>'.
                    '<th>Actividad</th>'.
                    // '<th>Creado en</th>'.
                    '<th>Modificado en</th>'.
                    '</tr>'.
                    $contenido;
            }*/


    $nombre_grupo = Data_GrupoNombre($grupo_actual);

    $pagina = 'grupo';
    $listado = ''; // Por defecto usa vacio
    if ($grupo_actual == '') {
        $listado = 'inicio';
        if ($buscar != '') { $pagina = 'binicio'; }
        else { Contenido_Inicio(); return; }
        }

    $buscar_ok = true;

    if ($contenido == '') {
        // No hay datos registrados
        $enlace = '';
        if (!Comparar_Nombre($nombre_grupo, G_PAPELERA)) {
            $enlace = Pagina_Enlaces('man-nuevo', $listado, 'Adicionar nueva actividad');
            $contenido = "<tr><td>No hay actividades registradas aún. $enlace</td></tr>";
            }
        else {
            $contenido = "<tr><td>No hay actividades eliminadas</td></tr>";
            }
        // Si no hay busqueda en curso, no muestra caja de busqueda
        if ($buscar == '') { $buscar_ok = false; }
        }


    // Porcentaje de pendientes
    $p = '';
    if ($pendientes > 0 && $total > 0) {
        $p = ' ( '.round($pendientes * 100 / $total).'% )';
        }

?>
<? Pagina_Titulo($pagina, $nombre_grupo, $listado) ?>
<? if ($buscar_ok) { Pagina_Buscar(); } ?>
<div class="descripcion-macro"><p><?= Pagina_Texto($descripcion) ?></p></div>
<table class="listado" cellspacing="0"><?= $contenido ?></table>
<p>Total pendientes: <b><?= $pendientes ?></b> <?= Pagina_Porcentaje($pendientes, $total) ?></p>
<p>Total actividades: <b><?= $total ?></b></p>
<?

    // global $_data; Pagina_Debug('data', $_data);
}

// ************************************************************

function Contenido_Purgar() {
// Elimina papelera
global $_data;

	// $grupo_base = G_PAPELERA;
	// $llave_grupo = Data_LlaveGrupo($grupo_base);
    $llave_papelera = Grupo_Papelera();
	if (isset($_data[$llave_papelera])) {
        unset($_data[$llave_papelera]);
        if (Data_Guardar('Eliminadas actividades de la Papelera')) {
            Data_Leer(); // Recarga datos
            }
        }

    Contenido_Grupo();

}

// ************************************************************

function Grupo_Papelera() {

	$grupo_base = G_PAPELERA;
	$llave_papelera = Data_LlaveGrupo($grupo_base);

    return $llave_papelera;
}

// ************************************************************

function Contenido_Eliminar() {
// Elimina papelera
global $_data;

    // Obtiene llave por defecto (para nuevos)
    $pos = 1 * Post_Leer('pos', 0);

	// $grupo_base = G_PAPELERA;
	// $llave_papelera = Data_LlaveGrupo($grupo_base);
    $llave_papelera = Grupo_Papelera();
    if (!isset($_data[$llave_papelera])) { $_data[$llave_papelera] = array(); }

    // Grupo actualmente selecto
    $grupo_actual = Post_Leer('grupo');

    $grupos = Data_Grupos(); // Requerido para identificar los grupos estandar

	if ($pos > 0 && isset($grupos[$grupo_actual]) && isset($_data[$grupo_actual][$pos])) {
        // Pagina_Debug('eliminar '.$pos, $_data[$grupo_actual][$pos]);
        $fecha = date('YmdHis'); // Fecha borrado

        $p = $_data[$grupo_actual][$pos];
        $p['del-fecha'] = $fecha;
        $p['del-grupo'] = $grupo_actual;

        $_data[$llave_papelera][$pos] = $p;

        unset($_data[$grupo_actual][$pos]); // Elimina del grupo actual

        $info_pos = Pagina_Actividad($pos);
        if (Data_Guardar('Actividad '.$info_pos.' movida a la Papelera. <a href="?pagina=grupo&grupo='.$llave_papelera.'">Ver Papelera</a>')) {
            Data_Leer(); // Recarga datos
            }
        }

    Contenido_Grupo();

}

// ************************************************************

function Contenido_Restaurar() {
// Elimina papelera
global $_data;

    // Obtiene llave por defecto (para nuevos)
    $pos = 1 * Post_Leer('pos', 0);

    $llave_papelera = Grupo_Papelera();
    if (!isset($_data[$llave_papelera])) { $_data[$llave_papelera] = array(); }

    // Grupo actualmente selecto
    $grupo_actual = Post_Leer('grupo');

    $grupos = Data_Grupos(); // Requerido para identificar los grupos estandar

	if ($pos > 0 && isset($_data[$llave_papelera][$pos])) {
        // Pagina_Debug('eliminar '.$pos, $_data[$llave_papelera][$pos]);

        $p = $_data[$llave_papelera][$pos];

        $llave_grupo = $p['del-grupo']; // Grupo original

        if (isset($grupos[$llave_grupo])) {
            // NOTA: El valor de $pos es unico independiente del grupo!
            $_data[$llave_grupo][$pos] = $p;
            unset($_data[$llave_papelera][$pos]); // Elimina de la papelera

            $info_pos = Pagina_Actividad($pos);
            $nombre_grupo = Data_GrupoNombre($llave_grupo);

            if (Data_Guardar('Restaurada actividad '.$info_pos.'. <a href="?pagina=grupo&grupo='.$llave_grupo.'">Ver grupo '.$nombre_grupo.'</a>')) {
                Data_Leer(); // Recarga datos
                }
            }
        else { Pagina_Error('No es posible restaurar la actividad: Grupo destino no existe'); return; }

        }

    Contenido_Grupo();

}

// ************************************************************

function Contenido_Inicio() {
// Pagina de inicio

    $contenido = '';

    $grupos = Data_Grupos();

	$total = 0;
	$pendientes = 0;
    // $eliminados = 0;

    $clase = '';

    $buscar = trim(Post_Leer('buscar', ''));
    if ($buscar != '')
    {
        // Busca entradas que coincidan
        Contenido_Grupo();
        return;
    }

    // Listado completo de grupos
    foreach ($grupos as $llave_grupo => $grupo_base) {

        $atotal = Data_Subtotal($llave_grupo);
        if (!Comparar_Nombre($grupo_base, G_PAPELERA)) {
            $total += $atotal['total'];
            $pendientes += $atotal['pendientes'];
            }
        /*else {
            $eliminados += $atotal['total'];
            $atotal = array('total' => $eliminados); // Modifica valor a mostrar
            $clase = 'class="papelera"';
            }*/

        $ufecha = Data_Ultima($llave_grupo);
        $descripcion = '';
        $data = Data_InfoGrupo($llave_grupo, '@INFO');

        if (!is_array($data) && $data != '') {
            // Solo los primeros X caracteres
            if (strlen($data) > 130) {
                $data = substr($data, 0, 130).'...';
                }
            // Solo la primera linea
            $i = strpos($data, "\n");
            if ($i !== false) {
                $data = substr($data, 0, $i).'...';
                }
            $descripcion = "<div class=\"descripcion\">".Pagina_Texto($data)."</div>";
            }

        $infototal = 0;
        if ($atotal['total'] > 0) { $infototal = implode(' / ', $atotal); }

        $enlace = '';
        $grupo_base_htm = htmlspecialchars($grupo_base);
        if (Comparar_Nombre($grupo_base, G_PAPELERA) || Comparar_Nombre($grupo_base, G_SINCLASE)) {
            $enlace = "[ <a href=\"?pagina=grupo&grupo=$llave_grupo\">".trim(str_replace(array('[', ']'), '', $grupo_base_htm))."</a> ]";
            }
        else {
            $enlace = "<a href=\"?pagina=grupo&grupo=$llave_grupo\">$grupo_base_htm</a>";
            }

        // Edita semaforo de resumen ("pendientes" no se define para papelera)
        $resumen = '#eee';
        if ($atotal['total'] > 0 && isset($atotal['pendientes'])) {
            if ($atotal['pendientes'] == $atotal['total']) { $resumen = '#E00000'; } // rojo
            elseif ($atotal['pendientes'] > 0) { $resumen = '#FFFF66'; } // amarillo
            elseif ($atotal['pendientes'] == 0) { $resumen = '#009999'; } // verde
            }

        $contenido .= "<tr $clase><td><span class=\"resumen\" style=\"background:$resumen\"></span></td><td>$enlace $descripcion</td>".
                        "<td class=\"mini numero\" align=\"right\">$infototal</td><td class=\"fecha\">$ufecha</td></tr>";
        }


// Adiciona titulos
/*if ($contenido != '') {
    $contenido = "<tr><th>Grupos</th><th class=\"mini\">Total</th><th>Última</th></tr>".$contenido;
    }*/

if ($contenido == '') {
    // NO hay datos registrados
    echo "No hay actividades registradas, adicione una nueva actividad usando el enlace indicado arriba";
    return;
    }

?>
<? Pagina_Titulo('inicio') ?>
<? Pagina_Buscar() ?>
<table class="listado" cellspacing="0"><?= $contenido ?></table>
<p>Total pendientes: <b><?= $pendientes ?></b> <?= Pagina_Porcentaje($pendientes, $total) ?></p>
<p>Total actividades: <b><?= $total ?></b></p>
<?

	// <p class="error">Total eliminados: <b>< ? = $eliminados ? ></b></p>

}

// ************************************************************

function Pagina_Buscar() {

    $buscar = trim(Post_Leer('buscar', ''));

?>
<form method="post" id="form-buscar">
<div>
    <input type="text" id="buscar" name="buscar" value="<?= htmlspecialchars($buscar); ?>">
    <input type="submit" value="Buscar">
</div>
</form>
<?

}

// ************************************************************

function Pagina_Titulo($pagina, $infoadd = '', $listado = '') {

    $titulo = '';

    $titulos = array(
            'inicio'    => 'Indice general',
            'binicio'   => 'Indice general - Resultado búsqueda',
            'nuevo'     => 'Nueva actividad',
            'grupo'     => '%',
            'editar'    => 'Editar Actividad No. %',
            'ngrupo'    => 'Nuevo grupo',
            'egrupo'    => 'Editar grupo: %',
            // 'vgrupo'    => 'Volver a grupo %'
            );

    if (isset($titulos[$pagina])) { $titulo = $titulos[$pagina]; }

    $titulo = str_replace('%', $infoadd, $titulo);

    // NOTA: No incluye enlaces si la pagina es papelera
    // $listado = '';
    if (Comparar_Nombre($titulo, G_PAPELERA)) { $listado = 'inicio,purgar'; }
    elseif (Comparar_Nombre($titulo, G_SINCLASE)) { $listado = 'inicio,nuevo'; }

?>
<div class="enlaces"><?= Pagina_Enlaces($pagina, $listado) ?></div>
<div class="contenido"><!-- contenido de página -->
<h2><?= $titulo ?></h2>
<?
    // return $titulo;
}

// ************************************************************

function Pagina_Enlaces($pagina, $listado = '', $titulo_alt = '') {
// Lista los enlaces permanentes por pagina

    // Listado de enlaces permanentes
    $enlaces = array(
            'inicio'    => '<< Indice general',
            'binicio'   => 'Indice general - Resultado búsqueda',
            'nuevo'     => '+ Nueva actividad',
            'ngrupo'    => '+ Nuevo grupo',
            'egrupo'    => 'Editar grupo',
            'vgrupo'    => '<< Volver a grupo %',
            'cancelar'  => 'Cancelar',
            'purgar'    => 'Eliminar contenido'
            );

    // Enlaces por pagina
    $enlaces_pag = array(
            'inicio'    => 'inicio,nuevo,ngrupo',
            'binicio'    => 'inicio,nuevo,ngrupo',
            'grupo'     => 'inicio,nuevo,egrupo',
            'nuevo'     => 'inicio,cancelar',
            'editar'    => 'inicio,cancelar',
            'man-nuevo' => 'nuevo',
            'purgar'    => ''
            );

    $lista_enlaces = '';

    if ($listado == '') {
        $listado = 'inicio,ngrupo'; // Listado por defecto
        if (isset($enlaces_pag[$pagina])) { $listado = $enlaces_pag[$pagina]; } // Enlaces para la pagina actual
        }

    // Parametros adicionales para cada enlace
    $params = array('grupo');
    $enlace_params = array('pagina' => ''); // Ubica este de primero por ser el principal
    foreach ($params as $k => $llave) {
        $valor_param = Post_Leer($llave);
        if ($valor_param != '') { $enlace_params[$llave] = $valor_param; }
        }

    // Explora enlaces
    $arreglo = explode(',', $listado);
    foreach ($arreglo as $k => $llave) {
        if (isset($enlaces[$llave]) && $llave != $pagina) {
            $enlace_pagina = $enlaces[$llave]; // Pagina destino del enlace
            if ($titulo_alt != '') { $enlace_pagina = $titulo_alt; } // Usa titulo alterno
            // if ($lista_enlaces != '') { $lista_enlaces .= ' | '; }
			$enlace = '';
			if ($llave == 'cancelar') {
				$enlace_params['pagina'] = 'grupo';
				$enlace = http_build_query($enlace_params);
                }
			elseif ($llave != 'inicio') {
				$enlace_params['pagina'] = $llave;
				$enlace = http_build_query($enlace_params);
				}
            $lista_enlaces .= "<a href=\"?$enlace\">$enlace_pagina</a>";
            }
        }

    // Pagina_Debug('enlaces '.$pagina, $lista_enlaces);

    return $lista_enlaces;

}

// ************************************************************

function Pagina_Encabezado($pagina) {
// Apertura de todas las páginas

?><!doctype html>
<html>
<head>
<title>ToDo List Simple</title>
<style>
a, a:visited { color:blue; }
body { font-family: Tahoma; font-size:12pt; }
h2 { margin:0; padding:0; margin-bottom:15px; }
h4 { margin:0; padding:0; margin-bottom:4px; }
.pie { font-size:8pt; margin-top:20px; border-top:1px solid #ddd; padding-top:10px; color:#999; }
.enlaces { font-size:10pt; padding:12px; padding-left:4px; background:#f2f2f2; border;1px solid #ccc; margin-bottom:20px; }
.enlaces a { padding:8px 12px 8px 12px; background:#eee; margin-right:4px; border:1px solid #ccc; color:#333; text-decoration:none; }
.enlaces a:hover { background:#777; color:#eee; }
.buscar { margin-bottom:20px; }
.buscar input[type=text] { width:220px; padding:4px; }
.buscar input[type=submit] { height:25px; }
.contenido { padding: 20px; border:1px solid #eee; color:#333; }
.error { color:darkred; background:#FFCCCC; padding:10px; margin-bottom:20px; }
.info { background:#F0F8FF; padding:10px; margin-bottom:20px; }
.debug { background:#f2f2f2; padding:10px; margin:20px; border:1px solid #ccc; }
.debug pre { font-family:Consolas,"New Courier"; }
form div { margin-bottom:8px; }
form textarea { width:520px; height:150px; }
form input[type="text"] { width:420px; }
.listado { width:100%; border:1px solid #ccc; border-bottom:none; }
.listado th { background:#eee; padding:8px; border-bottom:1px solid #ccc; }
.listado td { padding:8px; border-bottom:1px solid #ccc; }
.listado .mini { width:120px; }
.listado .pos { width:70px; white-space:nowrap; }
.listado .numero { text-align:right; font-family:Consolas,"New Courier" }
.listado .fecha { text-align:right; font-family:Consolas,"New Courier"; width:200px; }
.listado .eliminar { text-align:right; width:50px; font-size:10pt; }
.descripcion { color:#777; font-size:10pt; margin-top:8px; }
.descripcion-macro { color:#333; margin-bottom:20px; border-left:7px solid #ddd; padding-left:12px; }
.label { font-weight:bold; font-size:9pt; padding:2px 4px 2px 4px; text-align:center; }
.estado_ok { background:#CCFFCC; }
.estado_ok .label { background:darkgreen; color:#f2f2f2; }
.estado_nok { background:#FFCCCC; }
.estado_nok .label { background:darkred; color:#f2f2f2; }
.estado_sus { background:#FFCC99; }
.estado_sus .label { background:#222; color:#f2f2f2; }
.estado_inf .label { background:#222; color:#fff; }
.prioridad_0 { background:#ccc; color:#222; }
.prioridad_2 { background:#222; color:#f2f2f2; }
.prioridad_3 { background:darkred; color:#f2f2f2; }
.label_grupo { background:#333 !important; color:#f2f2f2 !important; }
.papelera { background:#eee; }
.resumen { padding-left:18px; border:1px solid #ccc; }
td p { margin:4px; }
.item-lista li { font-size:10pt; margin-bottom:12px; }
.nota { font-style:italic; font-size:10pt; color:#777; }
.ayuda { font-size:8pt; color:blue; }
</style>
</head>
<body>
<h1>ToDo List Simple</h1>
<?
//.comentario { color:blue; font-size:10pt; margin-top:8px; }
}

// ************************************************************

function Pagina_Cierre() {

?>
</div><!-- contenido -->
<div class="pie">ToDo List Simple. Escrito por John Mejia (C) 2013 // <?= date('Y/m/d H:i:s') ?></div>
</body>
</html>
<?

}

// ************************************************************

function Pagina_Info($info) {
// Reporta error

    echo "<div class=\"info\"><h4>Información</h4>$info</div>";
}

// ************************************************************

function Pagina_Error($infoerror) {
// Reporta error

    echo "<div class=\"error\"><h4>Error</h4>$infoerror</div>";
}

// ************************************************************

function Post_Leer($nombre, $xdefecto = '', $proteger = false) {
// Captura valor recibido por Web

    $valor = $xdefecto;
    if (isset($_REQUEST[$nombre])) {
        $valor = $_REQUEST[$nombre];
        if (!is_array($valor)) {
			if (get_magic_quotes_gpc()) { $valor = stripslashes($valor); }
			$valor = trim($valor);
			}
        else {
            foreach ($valor as $k => $v) {
				if (get_magic_quotes_gpc()) { $v = stripslashes($v); }
				$valor[$k] = trim($v);
				}
            }
        }

    // Protege para uso en controles
    if ($proteger) { $valor = htmlspecialchars($valor); }

    return $valor;
}

// ************************************************************

function Form_Listado($nombre, $arreglo, $valor_actual = '') {
// Crea listado de opciones

    $listado = '';
    $valor_actual = trim($valor_actual);

    foreach ($arreglo as $llave => $valor) {
        $selecto = '';
        if (trim($llave) == $valor_actual) { $selecto = 'selected'; }
        $listado .= "<option value=\"$llave\" $selecto>$valor</option>";
        }
    // if ($listado != '') {
        $listado = "<select name=\"$nombre\">$listado</select>";
        // }

    return $listado;
}

// ************************************************************

function Pagina_Debug($titulo, $variable) {

    echo "<div class=\"debug\"><h4>$titulo</h4><pre>";
    echo htmlspecialchars(print_r($variable, true));
    echo "</pre></div>".PHP_EOL;
}

// ************************************************************

function Data_LlaveGrupo($grupo_base) {
// Genera llave a usar para identificar cada grupo

    $llave = md5(strtolower($grupo_base));

    // echo "$llave<hr>";

    return $llave;
}

// ************************************************************

function Data_Leer() {
// Carga datos del archivo
global $_data, $_archivo;

    // Inicializa contenedor
    $_data = array(
        '@SGTE' => 1, // Inicia conteo en 1
        '@GRUPOS' => array(),
        '@CTL' => '',
        );

    // Si no hay archivo no hace nada
    if (!file_exists($_archivo)) { return; }

    $lineas = file($_archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

    // Llave para papelera
	// $grupo_base = G_PAPELERA;
	// $llave_papelera = Data_LlaveGrupo($grupo_base);
    $llave_papelera = Grupo_Papelera();

    $grupo_base = '';
	$estado_global = '';
	$sgte = 1;

    // Rompe en lineas
    foreach ($lineas as $k => $linea) {
		$linea = trim($linea);
		if ($linea == '' || substr($linea, 0, 1) == ';') {
			// Comentario, ignora
			continue;
			}
        elseif (substr($linea, 0, 1) == '[' && substr($linea, -1, 1) == ']') {
            // Grupo
            $grupo_base = trim(substr($linea, 1, -1));
			$llave_grupo = Data_LlaveGrupo($grupo_base);
            if (!isset($_data[$llave_grupo])) {
				$_data['@GRUPOS'][$llave_grupo] = $grupo_base;
				$_data[$llave_grupo] = array();
				}
			$estado_global = '';
            // echo $grupo_base.'<hr>';
            }
		// Soporte para carga inicial, donde se define el estado con una linea de marca
		elseif ($linea == '!Pendientes') {
			$estado_global = '';
			}
		elseif ($linea == '!Realizados') {
			$estado_global = 'OK';
			}
		// Procede a evaluar linea de actividades
        else {
            // Formato de linea: posicion = { atributos }contenido >>> comentario
			if (substr($linea, 0, 1) == '#') {
				// Numeracion automatica (puede generar duplicados, se usa
				// solo para soportar carga inicial)
				$linea = $sgte.'='.substr($linea, 1);
				$sgte ++;
				}
            $i = strpos($linea, '=');
            if ($i !== false) {

                $comenta = '';
                $atributos = $estado_global.':1::'; // Valores por defecto para atributos: pendiente, no prioridad (1)

				$llave_grupo = Data_LlaveGrupo($grupo_base);

                // Obtiene numero de actividad
                $pos = substr($linea, 0, $i);
                $linea = trim(substr($linea, $i + 1));

				$upos = strtoupper($pos);
                if ($upos == 'CTL') {
                    $_data['@CTL'] = $linea;
                    continue; // Nada mas por hacer
                    }
				elseif ($upos == 'INFO') {
					// Comentario del grupo
					$_data[$llave_grupo]['@INFO'] = Data_Texto($linea, true);
					// echo "$llave_grupo // $linea<hr>";
					continue;
					}

                // $pos debe tener un valor mayor a cero
                $pos = 1 * $pos;
                if ($pos <= 0) { continue; }

                // Valida si hay atributos (opcionales)
                if (substr($linea, 0, 1) == TAG_INICIO) {
                    $i = strpos($linea, TAG_CIERRE);
                    if ($i !== false) {
                        $atributos = substr($linea, 1, $i - 1).'::::::';
                        $linea = trim(substr($linea, $i + 1));
                        }
                    }
                // Valida si hay comentarios
                $i = strpos($linea, TAG_COMENTA);
                if ($i !== false) {
                    $comenta = substr($linea, $i + strlen(TAG_COMENTA));
                    $linea = trim(substr($linea, 0, $i));
                    }

                // Procesa atributos
                $arreglo = explode(TAG_SEPARA, $atributos);

                // Guarda valores
                $_data[$llave_grupo][$pos] = array(
                    'actividad'     => Data_Texto($linea, true),
                    'comenta'       => Data_Texto($comenta, true),
                    'estado'        => $arreglo[0],
                    'prioridad'     => $arreglo[1],
                    'fecha-crea'    => $arreglo[2],
                    'fecha-mod'     => $arreglo[3]
                    );

                if ($llave_grupo == $llave_papelera) {
                    $_data[$llave_grupo][$pos]['del-fecha'] = $arreglo[4];
                    $_data[$llave_grupo][$pos]['del-grupo'] = $arreglo[5];
                    }

                // Si no es papelera, elimina los tipos

                // $_data['@GRUPOS'][$llave_grupo] = $grupo_base;

                if ($_data['@SGTE'] < $pos) { $_data['@SGTE'] = $pos; }
                }
            }
        }

    // Pagina_Debug('inicio', $_data);
}

// ************************************************************

function Data_Grupos($no_papelera = false) {
// Retorna listado de grupos asociados
global $_data;

    // Recupera arreglo almacenado
    $grupos = $_data['@GRUPOS'];

	// Ordena grupos
	asort($grupos);

	// Adiciona grupo por defecto, si no hay mas grupos
	$grupo_base = G_SINCLASE;
	$llave_grupo = Data_LlaveGrupo($grupo_base);
	if (isset($grupos[$llave_grupo])) { unset($grupos[$llave_grupo]); }
	$grupos[$llave_grupo] = $grupo_base;

	// Adiciona Papelera (siempre)
	// $grupo_base = G_PAPELERA;
	// $llave_grupo = Data_LlaveGrupo($grupo_base);
    if (!$no_papelera) {
        $llave_papelera = Grupo_Papelera();
        if (isset($grupos[$llave_papelera])) { unset($grupos[$llave_papelera]); }
        $grupos[$llave_papelera] = G_PAPELERA;
        }

	// Asegura el orden en los grupos
	$_data['@GRUPOS'] = $grupos;

    return $grupos;
}

// ************************************************************

function Data_Validar($p) {
// Almacena datos
global $_data;

    $nuevo = array();

    $grupo_selecto = '';
    $grupo_mover = '';

    // Obtiene llave por defecto (para nuevos)
    $pos = 1 * Post_Leer('pos', 0);

    foreach ($p as $llave => $valor) {
        $llave = strtolower($llave);
        switch ($llave) {
            case 'grupo':
                $grupos = Data_Grupos();
                foreach ($grupos as $llave_grupo => $grupo_base) {
                    // echo "$llave_grupo == $valor<hr>";
                    if ($llave_grupo == $valor) {
                        $grupo_selecto = $grupo_base;
                        }
                    }
                break;

            case 'actividad':
            case 'comenta':
            case 'prioridad':
            case 'estado':
                $nuevo[$llave] = $valor;
                break;

            case 'gnombre':
                // Nombre para nuevo grupo
                $llave_grupo = Data_LlaveGrupo($valor);

                // Valida que no exista previamente
                /*$grupos = Data_Grupos();
                $retornar = true;
                if (isset($grupos[$llave_grupo])) {
                    if ($pos <= 0) {
                        // Intenta adicionar uno que ya existe
                        Pagina_Error('El nombre de grupo dado ya existe');
                        $retornar = false;
                        }
                    }

                if (!$retornar) { return $retornar; }*/

                // Adicionar
                $grupo_selecto = $valor;
                $nuevo = Data_InfoGrupo($llave_grupo, $pos);
                if (!is_array($nuevo)) { $nuevo = array(); }

                // Actualiza arreglo global
                $_data[$llave_grupo] = $nuevo;
                $_data['@GRUPOS'][$llave_grupo] = $grupo_selecto;

                break;

            case 'gcomenta':
                // Comentario asociado al grupo actual
                $nuevo['@INFO'] = $valor;
                break;

            case 'gmover':
                // Mover al grupo indicado (si es diferente al actual)
                $grupo_mover = $valor;
                break;

            default:
            }
        }

    $retornar = true;

    if ($grupo_selecto == '') {
        Pagina_Error('No pudo encontrar el grupo a usar para los datos ingresados, intente de nuevo');
        $retornar = false;
        }
    elseif (isset($nuevo['actividad'])) {
        // Adiciona actividad
        // No ingresa si solo adiciona grupo

        $fecha = date('YmdHis'); // Fecha de creacion/edicion

        $llave_grupo = Data_LlaveGrupo($grupo_selecto);

        if ($pos > 0) {
            // Si no existe entrada actual, remueve control de posicion
            if (!isset($_data[$llave_grupo][$pos])) {
                $pos = 0;
                }
            else {
                // Captura valores previos
                $nuevo = array_merge($_data[$llave_grupo][$pos], $nuevo);
                }
            }

        if ($pos <= 0) {
            $pos = 1;
            if (isset($_data['@SGTE'])) { $pos = $_data['@SGTE'] + 1; }
            // Actualiza control
            $_data['@SGTE'] = $pos;
            // Adiciona fecha
            $nuevo['fecha-crea'] = $fecha;
            }

        $nuevo['fecha-mod'] = $fecha;

        // Actualiza valores
        // $_data[$llave_grupo][$pos] = $nuevo;

        // Valida si debe mover la actividad a otro grupo
        if ($grupo_mover != '' && $grupo_mover != $llave_grupo && isset($_data['@GRUPOS'][$grupo_mover])) {
            $_data[$grupo_mover][$pos] = $nuevo;
            // Remueve de la posicion actual
            if (isset($_data[$llave_grupo][$pos])) { unset($_data[$llave_grupo][$pos]); }
            }
        else {
            // Actualiza valores en el grupo actual
            $_data[$llave_grupo][$pos] = $nuevo;
            }
        }
    elseif (isset($nuevo['@INFO'])) {
        $_data[$llave_grupo] = $nuevo;
        }

    // Pagina_Debug('guardar-nuevo '.$pos, $_data['@GRUPOS']);
    // Pagina_Debug('guardar-nuevo '.$pos, $nuevo);
    // Pagina_Debug('guardar-nuevo '.$llave_grupo, $_data[$llave_grupo]);

    return $retornar;
}

// ************************************************************

function Data_Guardar($mensaje_exito = '') {
global $_data, $_archivo;

    $retornar = false;

    $contenido = '';

    // Pagina_Debug('guardar-data', $_data);

    if (file_exists($_archivo)) {
        // Guarda backup
        copy($_archivo, $_archivo.'-bk');
        }

    $fp = fopen($_archivo, 'w');

    // Llave para papelera
	// $grupo_base = G_PAPELERA;
	// $llave_papelera = Data_LlaveGrupo($grupo_base);
    $llave_papelera = Grupo_Papelera();

    foreach ($_data as $llave_grupo => $info_grupo) {

        // Abandona ciclo si ocurre algun error en el guardado
        if (!$fp) { break; }

        // Ignora llaves de valores de control
        // if (substr($grupo, 0, 1) == '@') { continue; }
        if (!isset($_data['@GRUPOS'][$llave_grupo])) { continue; }

        $grupo = $_data['@GRUPOS'][$llave_grupo];

        $contenido .= '['.$grupo.']'.PHP_EOL;

        foreach ($info_grupo as $pos => $p) {
			if ($pos === '@INFO') {
				$comenta_p = Data_Texto($p);
				if ($comenta_p != '') {
					$parcial = 'INFO='.$comenta_p;
					}
				}
			else {
                $info_papelera = '';

                if ($llave_grupo == $llave_papelera) {
                    // Adiciona valores de borrado
                    $info_papelera = TAG_SEPARA.
                        $p['del-fecha'].
                        TAG_SEPARA.
                        $p['del-grupo'];
                    }

				$parcial = $pos.
                        '='.
                        TAG_INICIO.
                        strtoupper($p['estado']).
                        TAG_SEPARA.
                        (1 * $p['prioridad']).
                        TAG_SEPARA.
                        $p['fecha-crea'].
                        TAG_SEPARA.
                        $p['fecha-mod'].
                        $info_papelera.
                        TAG_CIERRE.
                        Data_Texto($p['actividad']);
				}

            if (is_array($p) && $p['comenta'] != '') { $parcial .= TAG_COMENTA.Data_Texto($p['comenta']); }

            $contenido .= $parcial.PHP_EOL;

            if ($fp && strlen($contenido) > 10000) {
                // Guarda bloque
                if (!fwrite($fp, $contenido)) { fclose($fp); $fp = false; }
                $contenido = '';
                }
            }

        $contenido .= PHP_EOL;
        }

    // Adiciona control para evitar duplicados al guardar
    $contenido .= PHP_EOL.'CTL='.Data_CTL(date('YmdHis'));

    // Guarda resto
    if ($fp) { fwrite($fp, $contenido); }

    if ($fp) {
        // Cierra archivo previamente abierto
        fclose($fp);
        // Reporta todo Ok
        if ($mensaje_exito == '') { $mensaje_exito = 'Archivo guardado con éxito'; }
        Pagina_Info($mensaje_exito);

        $retornar = true;
        }
    else {
        // Reporta error
        Pagina_Error('No pudo crear archivo para contenidos');
        }

    // Pagina_Debug('contenido', $contenido);

    return $retornar;
}

// ************************************************************

function Data_Subtotal($llave_grupo) {
// Total de elementos por grupo
global $_data;

    $total = 0;
	$pendientes = 0;

    if (isset($_data[$llave_grupo])) {
		$info_grupo = $_data[$llave_grupo];
		foreach ($info_grupo as $pos => $p) {
			if ($pos > 0) { $total ++; }
			if (!isset($p['estado']) || $p['estado'] == '') { // Pendientes
				$pendientes ++;
				}
			}
		// $total = count($_data[$llave_grupo]);
		// if (isset($_data[$llave_grupo]['@INFO'])) { $total --; }
		}

    // if ($total > 0 && $pendientes > 0) { $total = $pendientes.' / '.$total; }
	$arreglo = array('pendientes' => $pendientes, 'total' => $total);

	return $arreglo;
}

// ************************************************************

function Data_InfoGrupo($llave_grupo, $indice = 0) {
// Datos de elementos por grupo
global $_data;

    $datos = '';

    if (isset($_data[$llave_grupo])) {
        if ($indice !== 0) {
            if (isset($_data[$llave_grupo][$indice])) {
                $datos = $_data[$llave_grupo][$indice];
                }
            elseif ($indice > 0) {
				// Solo para cuando solicita indices numericos (ignora @INFO)
                Pagina_Error('No existen datos para la actividad solicitada');
                }
            }
        else {
            $datos = $_data[$llave_grupo];
            }
        }
    else {
		// Solo para cuando solicita indices numericos (ignora @INFO)
        if (is_numeric($indice) && $indice <= 0) {
            Pagina_Info('No existen datos para el grupo solicitado');
            }
        }

    return $datos;
}

// ************************************************************

function Data_CTL($valor = '') {
// Control de archivo
global $_data;

    $salida = '';

    if ($valor == '' && isset($_data['@CTL'])) { $valor = $_data['@CTL']; }

    $salida = md5('todo-simple-'.$valor);

    return $salida;
}

// ************************************************************

function Data_GrupoNombre($grupo_actual) {
// Control de archivo
global $_data;

    $salida = '';

    if (isset($_data['@GRUPOS'][$grupo_actual])) { $salida = $_data['@GRUPOS'][$grupo_actual]; }

    return $salida;
}

// ************************************************************

function Data_Ultima($llave_grupo) {
global $_data;

    $salida = '';

    if (isset($_data[$llave_grupo])) {
        $info_grupo = $_data[$llave_grupo];
        foreach ($info_grupo as $pos => $p) {
            if (is_array($p) && isset($p['fecha-mod']) && $p['fecha-mod'] > $salida) {
                $salida = $p['fecha-mod'];
                }
            }
        }

    return Data_Fecha($salida);
}

// ************************************************************

function Data_Fecha($fecha) {
// Da formato a valores de fecha

	$salida = '---'; // Para formato de tabla

    if ($fecha != '') {
        $hora = substr($fecha, 8, 2);
        $ampm = 'AM';
        if ($hora >= 12) {
            $ampm = 'PM';
            if ($hora > 12) { $hora -= 12; }
            }
        $salida = substr($fecha, 0, 4).'/'.substr($fecha, 4, 2).'/'.substr($fecha, 6, 2).' '.sprintf('%02d', $hora).':'.substr($fecha, 10, 2).' '.$ampm;
        }

	return $salida;
}

// ************************************************************

function Comparar_Nombre($valor1, $valor2) {
// Compara dos nombres sin espacios

    $valor1 = trim(strtolower(str_replace(' ', '', $valor1)));
    $valor2 = trim(strtolower(str_replace(' ', '', $valor2)));

    return ($valor1 == $valor2);
}

// ************************************************************

function Pagina_Porcentaje($pendientes, $total) {
// Porcentaje de pendientes

    $retornar = '';
    if ($pendientes > 0 && $total > 0) {
        $p = 100 - round($pendientes * 100 / $total);
        $p2 = 2 * $p;
        $retornar = ' <span style="background:#009999;padding-left:'.$p2.'px"></span><span style="padding-left:'.(200 - $p2).'px;background:#E00000;"></span>&nbsp;'.$p.' %';
        }

    return $retornar;
}

// ************************************************************

function Data_Texto($cadena, $reverso = false) {
// Purga datos capturados desde/hacia archivo

	$que = array("\r" => '', "\n" => "\\n", "\t" => "    ");
	if (!$reverso) {
		// Para guardar en archivo
		$cadena = trim(str_replace(array_keys($que), $que, $cadena));
		}
	else {
		// Lee de archivo
		$cadena = trim(str_replace($que, array_keys($que), $cadena));
		// echo "$cadena<hr>";
		}

	return $cadena;
}

// ************************************************************

function Pagina_Texto($cadena, $texto_add = '') {
// Da formato HTML a texto

    $contenido = htmlspecialchars($cadena);
    $arreglo = explode("\n", $contenido);

    $cadena = '';
    if ($texto_add != '') { $cadena = '<p>'.$texto_add.'</p>'; }

    $ul_abierto = false;

    foreach ($arreglo as $k => $linea)
    {
        $linea = trim($linea);
        if ($linea != '')
        {
            $pre = '';
            if (substr($linea,0,1) == '*')
            {
                if (!$ul_abierto) {
					$ul_abierto = true;
					$pre = '<ul class="item-lista">';
					}

                $linea = '<li>'.substr($linea, 1).'</li>';
            }
            elseif (substr($linea,0,2) == '--')
            {
                // Anotacion
                if ($ul_abierto) { $ul_abierto = false; $pre = '</ul>'; }
                $linea = '<p class="nota">'.substr($linea, 2).'</p>';
            }
            else
            {
                if ($ul_abierto) {
					$ul_abierto = false;
					$pre = '</ul>';
					}
                $linea = '<p>'.$linea.'</p>';
            }

            $cadena .= $pre.$linea."\n";
        }
    }
	// $cadena = '<p>'.$texto_add.str_replace("\n", "</p><p>", htmlentities($cadena)).'</p>';

	if ($ul_abierto) {
		$cadena .= '</ul>';
		}

	return $cadena;
}

// ************************************************************

function Pagina_Actividad($pos) {

	return sprintf('%06d', $pos);
}

// ************************************************************

function Data_Buscar($buscar, $llave_grupo = '') {
// Datos de elementos por grupo
global $_data;

    $info = '';

    $datos = array();

    $limite_buscar = 0; // Maximo de entradas a mostrar
    $resultado = '';

    $lbuscar = strtolower(trim($buscar));
    if ($lbuscar == '') { return $datos; }

    $grupo_buscar = array();

    if ($llave_grupo != '' && isset($_data[$llave_grupo])) {
        $grupo_buscar[] = $llave_grupo;
        }
    else {
        $grupo_buscar = array_keys($_data['@GRUPOS']);
        }

    foreach ($grupo_buscar as $k => $llave_grupo2) {
        if (isset($_data[$llave_grupo2])) { // La papelera puede estar en grupos y no en $_data
            foreach ($_data[$llave_grupo2] as $pos => $p) {
                if ($pos == '@INFO') { continue; }
                $info = strtolower(trim(Pagina_Actividad($pos)."\n".$p['actividad']."\n".$p['comenta']));
                if (strpos($info, $lbuscar) !== false) {

                    if ($limite_buscar >= BUSCAR_LIMITE) {
                        // Alcanzó el límite a mostrar
                        $resultado = 'Se encontraron más ocurrencias que las permitidas para mostrar ('.BUSCAR_LIMITE.'). Refina los parámetros de búsqueda';
                        break 2;
                        }

                    $p['grupo-llave'] = $llave_grupo2;
                    $datos[$pos] = $p;
                    $limite_buscar ++;
                    }
                }
            }
        }

    if ($resultado == '') { $resultado = 'Se encontraron '.$limite_buscar.' actividades buscando por "'.$buscar.'"'; }

    $enlace_remover = '';
    if ($llave_grupo != '') { $enlace_remover = 'pagina=grupo&grupo='.$llave_grupo; }

    $resultado .= '. <a href="?'.$enlace_remover.'">Remover búsqueda</a>';

    Pagina_Info($resultado);

    // Pagina_Debug('buscar-grupos', $datos);

    // Solo para cuando solicita indices numericos (ignora @INFO)
    // Pagina_Error('No existen datos para la actividad solicitada');

    return $datos;
}

// ************************************************************

?>