<?php
/**
 * Zpracování poptávkového formuláře z webu Autodoprava Miroslav Karmasin.
 * Nahrajte tento soubor vedle index.html na hosting, který podporuje PHP
 * (běžné u naprosté většiny českých webhostingů – Wedos, Active24, Forpsi...).
 *
 * Formulář na webu odesílá data přes fetch() na tento soubor a čeká JSON
 * odpověď { ok: true } / { ok: false, error: "..." }.
 */

header('Content-Type: application/json; charset=utf-8');

// --- Nastavení -------------------------------------------------------
$recipient = 'miroslavkarmasin@centrum.cz';   // kam poptávky chodí
$siteName  = 'Autodoprava Miroslav Karmasin';

// Adresa, ze které bude e-mail formálně odeslán (musí být ideálně na
// stejné doméně, kde web běží, aby ho poštovní servery nepovažovaly
// za spam). Pokud web poběží na mikraj.cz, nechte tak, jak je.
$fromAddress = 'web@mikraj.cz';

// --- Pomocné funkce ----------------------------------------------------
function respond($ok, $error = null) {
    echo json_encode(['ok' => $ok, 'error' => $error]);
    exit;
}

function clean($value) {
    $value = trim((string) $value);
    // ochrana proti header injection
    $value = str_replace(["\r", "\n"], ' ', $value);
    return $value;
}

// --- Jen POST ------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Nepovolená metoda.');
}

// --- Honeypot: pokud je vyplněný, jde o robota – tváříme se, že vše proběhlo ---
if (!empty($_POST['website'])) {
    respond(true);
}

// --- Načtení a validace polí ---------------------------------------
$jmeno   = clean($_POST['jmeno']   ?? '');
$telefon = clean($_POST['telefon'] ?? '');
$email   = clean($_POST['email']   ?? '');
$trasa   = clean($_POST['trasa']   ?? '');
$zasilka = clean($_POST['zasilka'] ?? '');
$zprava  = trim((string) ($_POST['zprava'] ?? ''));

if ($jmeno === '' || $telefon === '' || $email === '' || $zprava === '') {
    respond(false, 'Vyplňte prosím jméno, telefon, e-mail a zprávu.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Zadejte prosím platný e-mail.');
}

// --- Sestavení e-mailu -----------------------------------------------
$subject = '=?UTF-8?B?' . base64_encode("Poptávka přepravy – {$jmeno}") . '?=';

$body  = "Nová poptávka z webu {$siteName}\n\n";
$body .= "Jméno a příjmení: {$jmeno}\n";
$body .= "Telefon: {$telefon}\n";
$body .= "E-mail: {$email}\n";
$body .= "Trasa (odkud – kam): " . ($trasa !== '' ? $trasa : '-') . "\n";
$body .= "Popis zásilky: " . ($zasilka !== '' ? $zasilka : '-') . "\n\n";
$body .= "Zpráva:\n{$zprava}\n";

$headers   = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = "From: {$siteName} <{$fromAddress}>";
$headers[] = "Reply-To: {$jmeno} <{$email}>";

$sent = mail($recipient, $subject, $body, implode("\r\n", $headers));

if ($sent) {
    respond(true);
} else {
    respond(false, 'E-mail se nepodařilo odeslat. Zkuste to prosím znovu nebo nám zavolejte.');
}
