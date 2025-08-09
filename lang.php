<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function setLangFromRequest(): void {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['tr','en'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}

function getLang(): string {
    return $_SESSION['lang'] ?? 'tr';
}

function t(string $key): string {
    static $dict = [
        'tr' => [
            'emergency_profile' => 'Acil Profil',
            'viewed_via_qr' => 'Bu sayfa NFC/QR ile görüntülendi',
            'print_pdf' => 'PDF Yazdır',
            'open_link' => 'Bağlantı',
            'emergency_contact' => 'Acil İletişim',
            'person' => 'Kişi',
            'phone' => 'Telefon',
            'call_now' => 'Hemen Ara',
            'address' => 'Adres',
            'open_maps' => 'Haritada Aç',
            'doctor_info' => 'Doktor Bilgisi',
            'doctor' => 'Doktor',
            'medications' => 'İlaçlar',
            'other_info' => 'Diğer Bilgiler',
            'home' => 'Ana Sayfa',
        ],
        'en' => [
            'emergency_profile' => 'Emergency Profile',
            'viewed_via_qr' => 'This page is opened via NFC/QR',
            'print_pdf' => 'Print PDF',
            'open_link' => 'Open Link',
            'emergency_contact' => 'Emergency Contact',
            'person' => 'Person',
            'phone' => 'Phone',
            'call_now' => 'Call Now',
            'address' => 'Address',
            'open_maps' => 'Open in Maps',
            'doctor_info' => 'Doctor Info',
            'doctor' => 'Doctor',
            'medications' => 'Medications',
            'other_info' => 'Other Info',
            'home' => 'Home',
        ],
    ];
    $lang = getLang();
    return $dict[$lang][$key] ?? ($dict['tr'][$key] ?? $key);
}