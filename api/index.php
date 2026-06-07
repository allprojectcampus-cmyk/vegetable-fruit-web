<?php
/**
 * ==============================================================================
 * SISTEM PENARIK DATA (BACKEND) - cURL GOOGLE SHEETS & TAUTAN DELIVERY
 * ==============================================================================
 */

// --- MENGHANCURKAN CACHE VERCEL AGAR REAL-TIME ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// --- TAUTAN MITRA ONLINE DELIVERY ---
$linkGoFood     = "https://gofood.co.id/"; 
$linkGrabFood   = "https://food.grab.com/id/";
$linkShopeeFood = "https://shopeefood.co.id/";

function fetchGoogleSheetData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $result = curl_exec($ch);
    curl_close($ch);

    $start = strpos($result, '{');
    $end = strrpos($result, '}');
    if ($start !== false && $end !== false) {
        $jsonString = substr($result, $start, $end - $start + 1);
        return json_decode($jsonString, true);
    }
    return null;
}

// 1. Fetch Data Menu 
// (Ditambahkan &nocache=" . time() agar menembus blokir memori Google)
$urlMenu = "https://docs.google.com/spreadsheets/d/1p74nKGJfQG5oRGhYI8KMXmUCSKyLtt9iK8y06B1q2SE/gviz/tq?tqx=out:json&sheet=MENU&nocache=" . time();
$dataMenuRaw = fetchGoogleSheetData($urlMenu);

$menuByCategory = [];
if (isset($dataMenuRaw['table']['rows'])) {
    foreach ($dataMenuRaw['table']['rows'] as $row) {
        $namaMenu = $row['c'][1]['v'] ?? null;
        if ($namaMenu) { 
            $kategori = $row['c'][2]['v'] ?? 'Lainnya';
            $menuByCategory[$kategori][] = [
                'id'        => $row['c'][0]['v'] ?? '',
                'nama_menu' => $namaMenu,
                'harga'     => $row['c'][3]['v'] ?? 0,
                'gambar'    => $row['c'][4]['v'] ?? 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                'deskripsi' => $row['c'][5]['v'] ?? '' 
            ];
        }
    }
}

// 2. Fetch Data Komentar 
// (Ditambahkan &nocache=" . time() agar menembus blokir memori Google)
$urlKomentar = "https://docs.google.com/spreadsheets/d/1p74nKGJfQG5oRGhYI8KMXmUCSKyLtt9iK8y06B1q2SE/gviz/tq?tqx=out:json&sheet=KOMENTAR&nocache=" . time();
$dataKomentarRaw = fetchGoogleSheetData($urlKomentar);

$komentars = [];
if (isset($dataKomentarRaw['table']['rows'])) {
    foreach ($dataKomentarRaw['table']['rows'] as $row) {
        $status = (string)($row['c'][2]['v'] ?? '0');
        if ($status === '1' || $status === '1.0') {
            $komentars[] = [
                'nama'     => $row['c'][0]['v'] ?? 'Anonim',
                'komentar' => $row['c'][1]['v'] ?? ''
            ];
        }
    }
}
?>
