<?php
$data = [
    'sendmail_path' => ini_get('sendmail_path'),
    'SMTP' => ini_get('SMTP'),
    'smtp_port' => ini_get('smtp_port'),
    'cfg_file' => php_ini_loaded_file()
];
file_put_contents('config_check.json', json_encode($data));
echo "Check complete.";
?>
