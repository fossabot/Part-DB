<?php

/*
part-db version 0.4
Copyright (C) 2016 Jan B�hmer

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/

include_once('start_session.php');

use PartDB\HTML;

$messages = array();
$fatal_error = false; // if a fatal error occurs, only the $messages will be printed, but not the site content

/********************************************************************************
 *
 *   Evaluate $_REQUEST
 *
 *********************************************************************************/
$part_id            = isset($_REQUEST['pid'])               ? (integer)$_REQUEST['pid']             : 0;

$action = 'default';
if (isset($_REQUEST["label_generate"])) {
    $action = 'generate';
}
if (isset($_REQUEST["label_download"])) {
    $action = 'download';
}


/********************************************************************************
 *
 *   Initialize Objects
 *
 *********************************************************************************/

$html = new HTML($config['html']['theme'], $user_config['theme'], 'Label');


/********************************************************************************
 *
 *   Execute Actions
 *
 *********************************************************************************/

switch ($action) {
    case "generate":
        $html->setVariable("preview_src", "generate_part_label.php?pid=" . $part_id, "string");
        break;

    case "download":
        $html->setVariable("preview_src", "generate_part_label.php?download&pid=" . $part_id, "string");
        break;
}

/********************************************************************************
 *
 *   Set the rest of the HTML variables
 *
 *********************************************************************************/

if (! $fatal_error) {
    try {
        $html->setVariable("pid", $part_id, "integer");
        $html->setVariable("download_link", 'generate_part_label.php?pid='.$part_id.'&download', "string");
    } catch (Exception $e) {
        $messages[] = array('text' => nl2br($e->getMessage()), 'strong' => true, 'color' => 'red');
        $fatal_error = true;
    }
}

/********************************************************************************
 *
 *   Generate HTML Output
 *
 *********************************************************************************/


//If a ajax version is requested, say this the template engine.
if (isset($_REQUEST["ajax"])) {
    $html->setVariable("ajax_request", true);
}


$reload_link = $fatal_error ? 'show_part_label.php?pid='.$part_id : '';  // an empty string means that the...
$html->printHeader($messages, $reload_link);                           // ...reload-button won't be visible




if (! $fatal_error) {
    $html->printTemplate('show_part_label');
}


$html->printFooter();
