<?php
// Cookie e sessão separados: paciente nunca se torna usuário administrativo.
if (session_status()===PHP_SESSION_NONE) session_name('CLINIC_PATIENT');
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/patient_portal.php';
header('Cache-Control: no-store, private');header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: frame-ancestors 'none'");
function portal_header($title) {
    ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?= e($title) ?> — <?= e(APP_NAME) ?></title><?= clinic_asset_styles(['resources/entries/app.js']) ?></head><body class="bg-slate-50 text-slate-800"><main class="max-w-4xl mx-auto p-4 space-y-5"><h1 class="text-2xl font-bold"><?= e($title) ?></h1><?php
}
function portal_footer(){echo '</main></body></html>';}
function portal_require_patient(){ $p=portal_patient();if(!$p)redirect(APP_URL.'/portal/index.php');return $p; }
