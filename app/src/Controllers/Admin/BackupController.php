<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Backup;
use MenuQr\BackupException;
use MenuQr\Http;
use MenuQr\Settings;

final class BackupController extends AdminController
{
    private const PAGE = '/admin/backup';
    /** Typed by the admin before an import, in either language. */
    private const CONFIRM_WORDS = ['REEMPLAZAR', 'REPLACE'];

    public static function show(): void
    {
        self::admin();
        self::render('backup', 'backup', ['title' => t('nav.backup')]);
    }

    public static function export(): void
    {
        self::admin();
        $path = Backup::export(isset($_GET['stats']));
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(Settings::get('restaurant_name', 'menu'))), '-') ?: 'menu';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="menu-qr-' . $slug . '-' . date('Y-m-d') . '.zip"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store');
        readfile($path);
        unlink($path);
    }

    public static function import(): void
    {
        self::guardPost(self::PAGE);

        if (!in_array(strtoupper(Http::input('confirm')), self::CONFIRM_WORDS, true)) {
            self::back(self::PAGE, t('backup.error.confirm'), 'error');
        }
        $upload = $_FILES['backup'] ?? null;
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            self::back(self::PAGE, t('backup.error.too_big'), 'error');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $upload['tmp_name'])) {
            self::back(self::PAGE, t('backup.error.no_file'), 'error');
        }

        try {
            $result = Backup::import((string) $upload['tmp_name']);
        } catch (BackupException $exception) {
            self::back(self::PAGE, $exception->getMessage(), 'error');
        }
        self::back(self::PAGE, t('backup.imported', $result));
    }
}
