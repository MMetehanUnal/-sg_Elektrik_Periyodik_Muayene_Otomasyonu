<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabrika Otomasyon Sistemi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body class="<?php echo isLoggedIn() ? 'logged-in' : ''; ?>">

    <?php if (isLoggedIn()): ?>
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button class="btn btn-primary me-3" id="sidebarCollapse">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a class="navbar-brand fw-bold" href="/pages/dashboard.php">
                        <i class="fas fa-microchip me-2"></i> OTOMASYON SİSTEMİ
                    </a>
                </div>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMainContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarMainContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <?php if (isset($_SESSION['active_institution_id'])): ?>
                            <li class="nav-item me-3">
                                <div
                                    class="institution-badge d-flex align-items-center bg-white bg-opacity-10 rounded-pill px-3 py-1 text-white border border-white border-opacity-25">
                                    <i class="fas fa-building me-2 small"></i>
                                    <span class="small fw-medium me-2">
                                        <?php echo htmlspecialchars($_SESSION['active_institution_name'] ?? ''); ?>
                                    </span>
                                    <a href="/pages/tesis_secimi.php?action=logout_institution" class="text-white hover-opacity"
                                        title="Kurum Seçimini Kapat">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#"
                                id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar-circle me-2">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı'); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                                aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item py-2" href="/pages/ayarlar.php"><i class="fas fa-cog me-2 text-muted"></i>
                                        Ayarlar</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item py-2 text-danger" href="/logout.php"><i
                                            class="fas fa-sign-out-alt me-2"></i> Çıkış Yap</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            <!-- Sidebar -->
            <div class="sidebar shadow" id="sidebar">
                <div class="sidebar-content">
                    <ul class="nav flex-column p-3">
                        <li class="nav-item">
                            <a class="nav-link side-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                                href="/pages/dashboard.php">
                                <i class="fas fa-home me-2"></i> Dashboard
                            </a>
                        </li>

                        <li class="nav-section-title mt-4 mb-2">YÖNETİM</li>
                        <li class="nav-item">
                            <a class="nav-link side-link <?php echo basename($_SERVER['PHP_SELF']) == 'kurumlar.php' ? 'active' : ''; ?>"
                                href="/pages/kurumlar.php">
                                <i class="fas fa-building me-2"></i> Kurumlar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link side-link <?php echo basename($_SERVER['PHP_SELF']) == 'yetkili_kisiler.php' ? 'active' : ''; ?>"
                                href="/pages/yetkili_kisiler.php">
                                <i class="fas fa-user-tie me-2"></i> Yetkili Kişiler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link side-link <?php echo basename($_SERVER['PHP_SELF']) == 'cihazlar.php' ? 'active' : ''; ?>"
                                href="/pages/cihazlar.php">
                                <i class="fas fa-tools me-2"></i> Cihazlar
                            </a>
                        </li>

                        <li class="nav-section-title mt-4 mb-2">İŞLEMLER</li>
                        <li class="nav-item">
                            <a class="nav-link side-link <?php echo basename($_SERVER['PHP_SELF']) == 'tesis_secimi.php' ? 'active' : ''; ?>"
                                href="/pages/tesis_secimi.php">
                                <i class="fas fa-check-circle me-2"></i> Kurum Seçimi
                            </a>
                        </li>

                        <?php if (isset($_SESSION['active_institution_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link side-link <?php echo basename($_SERVER['PHP_SELF']) == 'tesis_bilgileri.php' ? 'active' : ''; ?>"
                                    href="/pages/forms/tesis_bilgileri.php">
                                    <i class="fas fa-info-circle me-2"></i> Kurum Bilgileri
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link side-link has-submenu" data-bs-toggle="collapse" href="#periyodikSubmenu"
                                    role="button" aria-expanded="false">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span><i class="fas fa-clipboard-check me-2"></i> Periyodik Kontrol</span>
                                        <i class="fas fa-chevron-down small"></i>
                                    </div>
                                </a>
                                <div class="collapse" id="periyodikSubmenu">
                                    <ul class="nav flex-column ms-3 mt-1">
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/topraklama_kontrol.php">
                                                Topraklama
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/ic_tesisat_kontrol.php">
                                                İç Tesisat
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/forms/yildirimdan_korunma_kontrol.php">
                                                Yıldırımdan Korunma
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/yangin_algilama_kontrol.php">
                                                Yangın Algılama
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/sihhi_tesisat_kontrol.php">
                                                Sıhhi Tesisat
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/gaz_tesisat_kontrol.php">
                                                Gaz Tesisatı
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/isinma_tesisat_kontrol.php">
                                                Isınma Tesisatı
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/genlesme_tanki_kontrol.php">
                                                Genleşme Tankı
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/engelli_rampasi_kontrol.php">
                                                Engelli Rampası
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/boyler_tanki_kontrol.php">
                                                Boyler Tankı
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/jenarator_kontrol.php">
                                                Jeneratör
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/forms/kamera_bakim_kontrol.php">
                                                Kamera Bakım
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link side-link has-submenu" data-bs-toggle="collapse" href="#sonuclarSubmenu"
                                    role="button" aria-expanded="false">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span><i class="fas fa-list-check me-2"></i> Sonuçlar</span>
                                        <i class="fas fa-chevron-down small"></i>
                                    </div>
                                </a>
                                <div class="collapse" id="sonuclarSubmenu">
                                    <ul class="nav flex-column ms-3 mt-1">
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/results/topraklama_sonuclar.php">
                                                Topraklama Sonuçları
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub" href="/pages/results/ic_tesisat_sonuclar.php">
                                                İç Tesisat Sonuçları
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/yildirimdan_korunma_sonuclar.php">
                                                Yıldırımdan Korunma
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/yangin_algilama_sonuclar.php">
                                                Yangın Algılama
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/sihhi_tesisat_sonuclar.php">
                                                Sıhhi Tesisat
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/gaz_tesisat_sonuclar.php">
                                                Gaz Tesisatı
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/isinma_tesisat_sonuclar.php">
                                                Isınma Tesisatı
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/genlesme_tanki_sonuclar.php">
                                                Genleşme Tankı
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/engelli_rampasi_sonuclar.php">
                                                Engelli Rampası
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/boyler_tanki_sonuclar.php">
                                                Boyler Tankı
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/jenarator_sonuclar.php">
                                                Jeneratör
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link side-link-sub"
                                                href="/pages/results/kamera_bakim_sonuclar.php">
                                                Kamera Bakım
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link side-link <?php echo basename($_SERVER['PHP_SELF']) == 'raporlar.php' ? 'active' : ''; ?>"
                                    href="/pages/raporlar.php">
                                    <i class="fas fa-file-pdf me-2"></i> Raporlar
                                </a>
                            </li>

                            <li class="nav-section-title mt-4 mb-2">EK ÖZELLİKLER</li>
                            <li class="nav-item">
                                <a class="nav-link side-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['genel_rapor.php', 'genel_rapor_duzenle.php']) ? 'active' : ''; ?>"
                                    href="/pages/genel_rapor.php">
                                    <i class="fas fa-file-alt me-2"></i> Genel Rapor
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-section-title mt-4 mb-2">GENEL</li>
                        <li class="nav-item mb-3">
                            <a class="nav-link side-link <?php echo basename($_SERVER['PHP_SELF']) == 'dokumanlar.php' ? 'active' : ''; ?>"
                                href="/pages/dokumanlar.php">
                                <i class="fas fa-file-download me-2"></i> Dökümanlar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Page Content -->
            <div class="content p-4" id="content">
                <div class="container-fluid">
                <?php endif; ?>