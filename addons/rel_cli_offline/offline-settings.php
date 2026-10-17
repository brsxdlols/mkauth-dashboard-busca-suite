<?php
function off_display_settings($link) {
    $defaults = array('enabled'=>true, 'hours'=>'6');
    $result = mysqli_query($link, 'SELECT enabled, hours FROM sis_rel_cli_offline_display_cfg WHERE id = 1');
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $hours = (float)$row['hours'];
        if ($hours >= 0.01 && $hours <= 876000) return array('enabled'=>(bool)$row['enabled'], 'hours'=>rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.'));
    }
    return $defaults;
}
function off_display_valid_hours($value) {
    return is_string($value) && preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $value) && (float)$value >= 0.01 && (float)$value <= 876000;
}
function off_display_alert($seconds, $settings) {
    return $settings['enabled'] && is_numeric($seconds) && (float)$seconds >= (float)$settings['hours'] * 3600;
}

function off_coordinate_pair($value) {
    if (!is_string($value)) return null;
    $value = trim($value);
    if (strlen($value) > 1 && $value[0] === '(' && substr($value, -1) === ')') $value = trim(substr($value, 1, -1));
    if (!preg_match('/^([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)$/D', $value, $parts)) return null;
    $latitude = (float)$parts[1]; $longitude = (float)$parts[2];
    if (abs($latitude) > 90 || abs($longitude) > 180 || ($latitude == 0 && $longitude == 0)) return null;
    return array($latitude, $longitude);
}

function off_has_coordinates($value) { return off_coordinate_pair($value) !== null; }
