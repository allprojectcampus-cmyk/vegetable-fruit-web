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

// Fungsi untuk mengambil data JSON dari Google Sheets
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

// 1. Fetch Data Menu (Dengan Anti-Cache)
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
                // Mengambil URL Gambar dari Kolom E (indeks 4)
                'gambar'    => $row['c'][4]['v'] ?? 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
                // Mengambil Deskripsi dari Kolom F (indeks 5)
                'deskripsi' => $row['c'][5]['v'] ?? '' 
            ];
        }
    }
}

// 2. Fetch Data Komentar (Dengan Anti-Cache)
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

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vegetable & Fruit - Raw Freshness for Everyone</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4CAF50;
            --secondary: #FF9800;
            --tertiary: #FFEB3B;
            --neutral: #2D3436;
            --bg-color: #F9FBF9;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-color);
            color: var(--neutral);
            overflow-x: hidden; 
        }

        /* Navbar */
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .navbar-brand {
            font-weight: 700;
            color: var(--primary) !important;
            font-size: 1.2rem;
        }
        @media (min-width: 992px) {
            .navbar-brand { font-size: 1.5rem; }
        }

        /* Buttons Responsive */
        .btn-primary-custom {
            background-color: var(--primary);
            color: #fff;
            border-radius: 50px;
            padding: 10px 24px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        .btn-primary-custom:hover { background-color: #3e8e41; color: #fff; transform: translateY(-2px); }
        
        .btn-secondary-custom {
            background-color: var(--secondary);
            color: #fff;
            border-radius: 50px;
            padding: 10px 24px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        .btn-secondary-custom:hover { background-color: #e68a00; color: #fff; transform: translateY(-2px); }

        /* Hero Section Mobile First */
        .hero-section {
            background: linear-gradient(rgba(249,251,249,0.85), rgba(249,251,249,0.95)), url('https://images.unsplash.com/photo-1498837167922-c77900827308?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover;
            border-radius: 16px;
            padding: 60px 15px; 
            margin-top: 20px;
            text-align: center;
        }
        .hero-title {
            color: var(--primary);
            font-weight: 700;
            font-size: 2rem; 
            margin-bottom: 15px;
            line-height: 1.2;
        }
        @media (min-width: 992px) {
            .hero-section { padding: 100px 20px; border-radius: 24px; margin-top: 30px; }
            .hero-title { font-size: 3.5rem; margin-bottom: 20px; }
        }
        
        /* Product Cards Mobile First */
        .product-card {
            background: #ffffff;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        .product-img {
            height: 120px; 
            object-fit: cover;
            width: 100%;
            background-color: #f4fafd;
        }
        .price-text {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.9rem; 
        }
        .badge-kategori {
            background-color: var(--tertiary);
            color: #2b3234;
            font-weight: 600;
            font-size: 0.65rem; 
            padding: 0.35em 0.65em !important;
        }
        .btn-card-wa {
            font-size: 0.8rem;
            padding: 8px;
        }
        
        @media (min-width: 768px) {
            .product-img { height: 180px; }
            .price-text { font-size: 1.15rem; }
            .badge-kategori { font-size: 0.8rem; padding: 0.5em 0.8em !important; }
            .btn-card-wa { font-size: 1rem; padding: 10px 24px; }
            .product-card:hover { transform: translateY(-8px); }
        }

        /* Testimonial & Forms */
        .testi-card {
            border-radius: 16px;
            border: 1px solid #e2e9ec;
            background: #fff;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }

        /* Footer / Partners */
        .partner-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            margin: 5px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .partner-badge:hover {
            transform: translateY(-3px);
            opacity: 0.9;
            color: white;
        }
        .bg-gofood { background-color: #EE2737; }
        .bg-grabfood { background-color: #00B14F; }
        .bg-shopeefood { background-color: #EE4D2D; }
        
        @media (min-width: 768px) {
            .partner-badge { font-size: 1rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top py-2 py-lg-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-basket-fill text-success fs-4 fs-lg-3 me-2"></i>
                Vegetable & Fruit
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto gap-2 gap-lg-4 fw-semibold text-center mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link text-dark" href="#menu">Menu</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#lokasi">Outlet</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#testimoni">Testimonial</a></li>
                </ul>
                <div class="d-flex flex-column flex-lg-row align-items-center mt-3 mt-lg-0 pb-3 pb-lg-0">
                    <span class="me-lg-3 mb-3 mb-lg-0 fw-bold text-muted"><i class="bi bi-geo-alt-fill text-danger"></i> Medan</span>
                    <a href="#menu" class="btn btn-primary-custom w-100 w-lg-auto">Order Now</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <section class="hero-section">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="hero-title">Racikan Alami untuk Semua Kalangan</h1>
                    <p class="fs-6 fs-md-5 text-muted mb-4">Kesegaran mentah untuk gaya hidup sehat Anda. Jus buah asli dan salad segar, disiapkan dengan higienis setiap hari.</p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="#menu" class="btn btn-primary-custom w-100 w-sm-auto">Lihat Menu</a>
                        <a href="#lokasi" class="btn btn-secondary-custom w-100 w-sm-auto">Cari Outlet</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="menu" class="mt-4 mt-lg-5">
            <?php if (empty($menuByCategory)): ?>
                <div class="alert alert-warning text-center">Menu akan tersedia beberapa saat lagi</div>
            <?php else: ?>
                <?php foreach ($menuByCategory as $kategori => $menus): ?>
                    <h3 class="fw-bold mt-4 mt-lg-5 mb-3 fs-4" style="color: var(--primary);"><?= htmlspecialchars($kategori) ?></h3>
                    <div class="row g-2 g-md-4">
                        <?php foreach ($menus as $item): 
                            $waText = urlencode("Halo min, saya mau pesan " . $item['nama_menu']);
                        ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="product-card d-flex flex-column h-100">
                                <div class="position-relative">
                                    <img src="<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_menu']) ?>" class="product-img">
                                    <span class="badge badge-kategori position-absolute top-0 end-0 m-2 m-md-3 rounded-pill">
                                        <?= htmlspecialchars($kategori) ?>
                                    </span>
                                </div>
                                <div class="p-2 p-md-3 p-lg-4 d-flex flex-column flex-grow-1">
                                    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold mb-1 mb-xl-0 fs-6 text-truncate w-100" style="max-width: 90%;"><?= htmlspecialchars($item['nama_menu']) ?></h5>
                                        <span class="price-text mt-1 mt-xl-0"><?= htmlspecialchars($item['harga']) ?></span>
                                    </div>
                                    <p class="text-muted flex-grow-1 d-none d-md-block" style="font-size: 0.85rem;"><?= htmlspecialchars($item['deskripsi']) ?></p>
                                    <p class="text-muted flex-grow-1 d-md-none text-truncate" style="font-size: 0.75rem;"><?= htmlspecialchars($item['deskripsi']) ?></p>
                                    
                                    <a href="https://wa.me/6281213663184?text=<?= $waText ?>" target="_blank" class="btn btn-primary-custom btn-card-wa w-100 mt-2 mt-md-3 d-flex justify-content-center align-items-center">
                                        <i class="bi bi-whatsapp me-1 me-md-2"></i> 
                                        <span class="d-none d-md-inline">Pesan via WA</span>
                                        <span class="d-inline d-md-none">Pesan</span> 
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section id="lokasi" class="mt-5 pt-4 pt-lg-5">
            <h2 class="fw-bold text-center mb-4 fs-3" style="color: var(--primary);">Lokasi Outlet Kami</h2>
            <div class="row g-4">
                <div class="col-md-6" id="map-pancing">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <iframe src="https://maps.google.com/maps?q=Jl.%20Pancing%20No.137,%20Kenangan%20Baru,%20Kec.%20Percut%20Sei%20Tuan,%20Kabupaten%20Deli%20Serdang,%20Sumatera%20Utara%2020222&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                        <div class="p-3 p-md-4 d-flex flex-column justify-content-between flex-grow-1">
                            <div>
                                <h5 class="fw-bold fs-6 fs-md-5">Outlet 1: Pancing</h5>
                                <p class="text-muted small">Jl. Pancing No.137, Kenangan Baru, Kec. Percut Sei Tuan, Kabupaten Deli Serdang, Sumatera Utara 20222</p>
                            </div>
                            <a href="https://maps.google.com/?q=Jl.+Pancing+No.137,+Kenangan+Baru,+Kec.+Percut+Sei+Tuan,+Kabupaten+Deli+Serdang,+Sumatera+Utara+20222" target="_blank" class="btn btn-secondary-custom w-100 mt-3">
                                <i class="bi bi-geo-alt-fill"></i> Rute Google Maps
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="map-karya">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <iframe src="https://maps.google.com/maps?q=Jl.%20Karya%20Bakti%20No.3,%20Indra%20Kasih,%20Kec.%20Medan%20Tembung,%20Kota%20Medan,%20Sumatera%20Utara%2020221&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                        <div class="p-3 p-md-4 d-flex flex-column justify-content-between flex-grow-1">
                            <div>
                                <h5 class="fw-bold fs-6 fs-md-5">Outlet 2: Karya Bakti</h5>
                                <p class="text-muted small">Jl. Karya Bakti No.3, Indra Kasih, Kec. Medan Tembung, Kota Medan, Sumatera Utara 20221</p>
                            </div>
                            <a href="https://maps.google.com/?q=Jl.+Karya+Bakti+No.3,+Indra+Kasih,+Kec.+Medan+Tembung,+Kota+Medan,+Sumatera+Utara+20221" target="_blank" class="btn btn-secondary-custom w-100 mt-3">
                                <i class="bi bi-geo-alt-fill"></i> Rute Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="testimoni" class="mt-5 pt-4 pt-lg-5 mb-5">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-6 order-2 order-lg-1">
                    <h3 class="fw-bold mb-3 mb-lg-4 fs-4 text-center text-lg-start" style="color: var(--primary);">Apa Kata Mereka?</h3>
                    <div class="d-flex flex-column gap-3">
                        <?php if (empty($komentars)): ?>
                            <p class="text-muted text-center text-lg-start">Belum ada ulasan. Jadilah yang pertama!</p>
                        <?php else: ?>
                            <?php foreach ($komentars as $testi): ?>
                                <div class="testi-card p-3 p-md-4 shadow-sm">
                                    <div class="text-warning mb-2 fs-6">
                                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                    </div>
                                    <p class="fst-italic mb-2 fs-6">"<?= htmlspecialchars($testi['komentar']) ?>"</p>
                                    <h6 class="fw-bold mb-0 text-end">- <?= htmlspecialchars($testi['nama']) ?></h6>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6 order-1 order-lg-2">
                    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4" style="background-color: #f4fafd;">
                        <h4 class="fw-bold mb-3 mb-md-4 fs-5 text-center text-lg-start">Kirim Testimoni</h4>
                        <form action="https://wa.me/6281213663184" method="GET" target="_blank">
                            <div class="mb-3">
                                <label class="form-label fw-semibold fs-6">Nama</label>
                                <input type="text" name="text" class="form-control rounded-3 py-2" placeholder="Masukkan nama Anda" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold fs-6">Komentar</label>
                                <textarea class="form-control rounded-3" rows="3" placeholder="Bagaimana pengalaman Anda?" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary-custom w-100 py-2">Kirim Testimoni</button>
                            <small class="text-muted mt-2 d-block text-center" style="font-size: 0.75rem;">*Testimoni akan ditinjau owner sebelum ditampilkan</small>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="mt-4 mb-4 mb-lg-5 text-center pb-4 border-bottom">
            <h5 class="fw-bold text-muted mb-3 fs-6">Tersedia juga di:</h5>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="<?= $linkGoFood ?>" target="_blank" class="partner-badge bg-gofood"><i class="bi bi-bag-check-fill me-1"></i> GoFood</a>
                <a href="<?= $linkGrabFood ?>" target="_blank" class="partner-badge bg-grabfood"><i class="bi bi-bag-check-fill me-1"></i> GrabFood</a>
                <a href="<?= $linkShopeeFood ?>" target="_blank" class="partner-badge bg-shopeefood"><i class="bi bi-bag-check-fill me-1"></i> ShopeeFood</a>
            </div>
        </section>

    </div> 

    <footer class="bg-light pt-4 pt-lg-5 pb-3 text-center text-md-start">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5 class="fw-bold fs-5" style="color: var(--primary);">
                        <i class="bi bi-basket-fill me-2"></i>Vegetable & Fruit
                    </h5>
                    <p class="text-muted small mt-2 mt-md-3">Raw Freshness for Everyone. Menyajikan jus buah dan sayur berkualitas premium di Kota Medan.</p>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold fs-6">LOKASI</h6>
                    <ul class="list-unstyled text-muted small mt-2 mt-md-3 lh-lg">
                        <li><a href="#map-pancing" class="text-decoration-none text-muted">Jl. Pancing No. 137</a></li>
                        <li><a href="#map-karya" class="text-decoration-none text-muted">Jl. Karya Bakti No. 3</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold fs-6">HUBUNGI KAMI</h6>
                    <ul class="list-unstyled text-muted small mt-2 mt-md-3 lh-lg">
                       <li>
                            <a href="https://instagram.com/vegetablefruit.mdn" target="https://www.instagram.com/vegetableandfruitbusiness?igsh=MTcyeWo2djZkYncyNg==" class="text-decoration-none text-muted">
                                <i class="bi bi-instagram me-2"></i> @vegetableandfruitbusiness
                            </a>
                        </li>
                        <li>
                            <a href="https://wa.me/6281213663184" target="_blank" class="text-decoration-none text-muted">
                                <i class="bi bi-whatsapp me-2"></i> 0812-1366-3184
                            </a>
                        </li> 
                    </ul>
                </div>
            </div>
            <div class="text-center mt-4 mt-lg-5 pt-3 border-top text-muted" style="font-size: 0.75rem;">
                © 2026 Vegetable & Fruit, Medan. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
