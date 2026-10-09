<?php
/*
 * guiconfig.inc
 *
 * Adapted from pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2026 Rubicon Communications, LLC (Netgate)
 * Adapted for MitraNet Rinjani 1.0.2 Native Linux Appliance
 *
 * Licensed under the Apache License, Version 2.0.
 */

if (!function_exists('gettext')) {
    function gettext($msg) {
        return $msg;
    }
}

if (!function_exists('_')) {
    function _($msg) {
        return $msg;
    }
}

/**
 * Generates breadcrumb HTML string matching pfSense exactly
 */
function genhtmltitle($title, $links = true) {
    return "";
}

/**
 * Display top navigation tabs (pills / tabs) matching pfSense exactly
 */
function display_top_tabs(&$tab_array, $no_drop_down = false, $type = 'tabs', $usepost = "") {
    if (empty($tab_array) || !is_array($tab_array)) {
        return;
    }

    echo '<ul class="nav nav-' . htmlspecialchars($type) . '">';
    foreach ($tab_array as $ta) {
        $title = $ta[0];
        $active = !empty($ta[1]);
        $url = $ta[2];
        $target = isset($ta[3]) ? $ta[3] : '';

        echo '<li role="presentation"' . ($active ? ' class="active"' : '') . '>';
        echo '<a href="' . htmlspecialchars($url) . '"' . ($target ? ' target="' . htmlspecialchars($target) . '"' : '') . ' ' . $usepost . '>';
        echo $title;
        echo '</a></li>';
    }
    echo '</ul>';
}

/**
 * Print information / alert box
 */
function sprint_info_box($msg, $class = "alert-warning", $btnname = "close", $btntext = "", $btnicon = "", $btnclass = "default") {
    if (strpos($class, "alert-") !== 0) {
        $class = 'alert-' . $class;
    }

    $out = '<div class="alert ' . htmlspecialchars($class) . ' clearfix" role="alert">';
    $out .= '<div class="pull-left">' . $msg . '</div>';

    if ($btnname === "close") {
        $out = '<div class="alert ' . htmlspecialchars($class) . ' clearfix" role="alert">';
        $out .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
        $out .= '<div class="pull-left">' . $msg . '</div>';
    } else if (!empty($btnname)) {
        if (empty($btntext)) {
            $btntext = $btnname;
        }
        $icon_html = !empty($btnicon) ? '<i class="' . htmlspecialchars($btnicon) . ' icon-embed-btn"></i> ' : '';
        $out .= '<form method="post" class="pull-right">';
        $out .= '<button type="submit" class="btn btn-' . htmlspecialchars($btnclass) . '" name="' . htmlspecialchars($btnname) . '" value="' . htmlspecialchars($btntext) . '">';
        $out .= $icon_html . htmlspecialchars($btntext);
        $out .= '</button>';
        $out .= '</form>';
    }

    $out .= '</div>';
    return $out;
}

function print_info_box($msg, $class = "alert-warning", $btnname = "close", $btntext = "", $btnicon = "", $btnclass = "default") {
    echo sprint_info_box($msg, $class, $btnname, $btntext, $btnicon, $btnclass);
}

function print_apply_box($msg) {
    print_info_box($msg, "warning", "apply", gettext("Apply Changes"), 'fa-solid fa-check', 'success');
}

function print_apply_result_box($retval, $extra_text = "") {
    if ($retval === 0) {
        $severity = "success";
        $msg = gettext("The changes have been applied successfully.");
    } else {
        $severity = "danger";
        $msg = gettext("There was a problem applying the changes.");
    }
    if (!empty($extra_text)) {
        $msg .= " " . $extra_text;
    }
    print_info_box($msg, $severity);
}

function print_input_errors($input_errors) {
    if (empty($input_errors) || !is_array($input_errors)) {
        return;
    }
    echo '<div class="alert alert-danger input-errors" role="alert">';
    echo '<p><strong>' . gettext('The following input errors were detected:') . '</strong></p>';
    echo '<ul>';
    foreach ($input_errors as $ierr) {
        echo '<li>' . htmlspecialchars($ierr) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

function print_callout($msg, $class = 'info', $heading = '') {
    if ('' == $msg) {
        return;
    }
    $class = strtolower($class);
    if (!in_array($class, ['default', 'danger', 'warning', 'info'])) {
        $class = 'info';
    }
    $callout = '<div class="bs-callout bs-callout-' . $class . '">';
    if ('' != $heading) {
        $callout .= '<h4>' . htmlspecialchars($heading) . '</h4>';
    }
    $callout .= $msg . '</div>';
    echo $callout;
}
