<?php

$tableName = 'xplugin_consent_manager_settings';
$action    = Shop::getAdminURL() . '/plugin.php?kPlugin=' . $oPlugin->kPlugin;
if (!empty($_POST) && validateToken()) {
    try {
        Shop::DB()->beginTransaction();
        foreach ($_POST as $settings_key => $value) {
            if ($settings_key === 'jtl_token') {
                continue;
            }

            // Serialize value to be sure it will be saved
            $settings_value = base64_encode(serialize($value));
            if (!empty(Shop::DB()->select($tableName, 'settings_key', $settings_key))) {
                // Update existing key
                Shop::DB()->update(
                    $tableName,
                    'settings_key',
                    $settings_key,
                    (object)compact('settings_key', 'settings_value')
                );
                continue;
            }

            // Insert new key
            Shop::DB()->insert(
                $tableName,
                (object)compact('settings_key', 'settings_value')
            );
        }
        Shop::DB()->commit();
        $_SESSION['consent_manager_status'] = [
            'success' => 'Saved!'
        ];
    } catch (Exception $exception) {
        Shop::DB()->rollback();
        $_SESSION['consent_manager_status'] = [
            'fail' => 'Data doesn\'t saved'
        ];
    }
    header('Location: ' . $action);
    exit;
}

$data = Shop::DB()->selectAll($tableName, [], []);
if (isset($_SESSION['consent_manager_status'])) {
    if (isset($_SESSION['consent_manager_status']['success'])) {
        $cHinweis = $_SESSION['consent_manager_status']['success'];
    } else {
        $cFehler = $_SESSION['consent_manager_status']['fail'];
    }
    unset($_SESSION['consent_manager_status']);
}

foreach ($data as $row) {
    $smarty->assign($row->settings_key, unserialize(base64_decode($row->settings_value)));
}
$smarty
    ->assign('action', $action)
    ->display(
        __DIR__ .
        DIRECTORY_SEPARATOR . 'tpl' .
        DIRECTORY_SEPARATOR . 'settings.tpl'
    );
