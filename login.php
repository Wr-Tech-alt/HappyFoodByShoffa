<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Redirect bila sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$username = '';

// Proses POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) (filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
    $password = trim((string) (filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW) ?? ''));

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $userModel = new User();
        $result = $userModel->login($username, $password);

        if ($result) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['nama_lengkap'] = $result['nama_lengkap'] ?? '';
            $_SESSION['role'] = $result['role'] ?? '';

            header('Location: index.php');
            exit();
        } else {
            $error = 'Username atau password salah!';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $active_tab = 'login';
} else {
    $qtab = strtolower(trim((string) (filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '')));
    $active_tab = ($qtab === 'about') ? 'about' : 'login';
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login - HappyFood Inventory</title>

    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root{
            --bg-dark:#0f1724;
            --panel-dark:#2d3250;
            --panel-muted:#424769;
            --muted:#676f9d;
            --accent:#f9b17a;
            --white:#ffffff;
        }

        html,body{
            height:100%;
            margin:0;
            font-family: "Raleway", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: linear-gradient(180deg, #0c1220 0%, #0f1724 100%);
            color:var(--white);
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }

        .page-wrap{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:48px 18px 72px;
        }

        .login-container{
            width:820px; 
            max-width:calc(100% - 48px);
            height:640px;
            border-radius:18px;
            overflow:hidden;
            display:flex;
            box-shadow: 0 26px 70px rgba(2,8,23,0.75);
            background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
        }

        .login-left{
            width:50%;
            min-width:360px;
            background: linear-gradient(180deg, rgba(56,58,83,0.96) 0%, rgba(41,41,61,0.96) 100%);
            padding:22px 28px 44px;
            box-sizing:border-box;
            color:var(--white);
            display:flex;
            flex-direction:column;
            justify-content:flex-start;
            gap:10px;
        }

        .top-tabs{ display:flex; gap:10px; margin-bottom:6px; }
        .tab-btn{
            background:transparent;
            padding:7px 14px;
            border-radius:10px;
            border:1px solid transparent;
            color:#d9dbe9;
            font-weight:600;
            cursor:pointer;
        }
        .tab-btn.active{
            background: linear-gradient(180deg, #f9b17a, #f98d66);
            color:#111;
            border-color: rgba(255,255,255,0.06);
            box-shadow: 0 8px 18px rgba(249,177,122,0.18);
        }

        .header-block { margin-bottom:6px; }
        .brand { font-weight:700; font-size:22px; letter-spacing:0.6px; color:var(--white); }
        .subtitle { font-size:13px; color: #c9cbe0; margin-top:4px; }

        .welcome-title{ font-size:26px; font-weight:700; margin:6px 0 6px 0; color:var(--white); }
        .welcome-sub{ font-size:13px; color:#c9cbe0; margin-bottom:12px; }

        /* form spacing */
        .form-row { margin-bottom:18px; }
        .form-label{ color:#cfcfe6; font-size:13px; margin-bottom:8px; display:block; }

        .input-group .input-icon{ background:transparent; border:none; color:var(--muted); min-width:46px; justify-content:center; }
        .form-control{
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            color: var(--white);
            height:46px;
            border-radius:8px;
            box-shadow:none;
            padding-left:12px;
        }
        .form-control::placeholder{ color: rgba(255,255,255,0.35); }
        .form-control:focus{
            outline:none;
            border-color: rgba(249,177,122,0.9);
            box-shadow: 0 6px 30px rgba(249,177,122,0.06);
            background: rgba(255,255,255,0.02);
            color:var(--white);
        }

        .form-actions{
            margin-top:32px;
            margin-bottom:16px;
        }
        .btn-login{
            background: linear-gradient(180deg, rgba(249,177,122,1) 0%, rgba(249,141,102,1) 100%);
            color: #111;
            font-weight:700;
            border:none;
            padding:14px 18px;
            border-radius:10px;
            box-shadow: 0 8px 30px rgba(249,177,122,0.18);
            transition: transform .15s ease, box-shadow .15s ease;
            font-size:16px;
        }
        .btn-login:hover{ transform: translateY(-3px); box-shadow: 0 18px 40px rgba(249,177,122,0.22); }

        .minor{ font-size:12px; color:#bfc0db; }
        .left-footer{ margin-top:6px; display:flex; justify-content:space-between; align-items:center; gap:12px; }

        .login-right{
            width:50%;
            background: url('assets/cake2.jpeg') center center / cover no-repeat;
            position:relative;
            display:flex;
            align-items:flex-end;
            justify-content:center;
        }
        .login-right::before{
            content:"";
            position:absolute;
            inset:0;
            background: linear-gradient(180deg, rgba(13,17,28,0.0) 0%, rgba(5,8,14,0.42) 100%);
            pointer-events:none;
        }

        .tab-content > .pane { display:none; }
        .tab-content > .pane.active { display:block; }

        .about-block{
            color:#d7d9f0;
            font-size:14px;
            line-height:1.6;
            background: transparent;
            border-radius:8px;
            padding:6px 0 0 0;
        }

        .login-container{ border-radius:22px; }

        @media (max-width:920px){
            .login-container{ width:760px; height:600px; }
        }
        @media (max-width:900px){
            .login-container{ flex-direction:column; height:auto; width:760px; }
            .login-left, .login-right{ width:100%; min-width:unset; }
            .login-right{ height:260px; order:2; background-position:center 30%; }
        }
        @media (max-width:520px){
            .login-container{ width:100%; border-radius:12px; padding:0; }
            .login-left{ padding:18px; }
            .page-wrap{ padding-bottom:48px; }
            .form-row { margin-bottom:14px; }
            .form-actions { margin-top:14px; }
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="login-container">
            <div class="login-left">
                <div class="top-tabs" role="tablist" aria-label="Login Tabs">
                    <button type="button" class="tab-btn <?= $active_tab === 'login' ? 'active' : '' ?>" data-target="pane-login" id="tab-login" aria-controls="pane-login" aria-selected="<?= $active_tab === 'login' ? 'true' : 'false' ?>">Login</button>
                    <button type="button" class="tab-btn <?= $active_tab === 'about' ? 'active' : '' ?>" data-target="pane-about" id="tab-about" aria-controls="pane-about" aria-selected="<?= $active_tab === 'about' ? 'true' : 'false' ?>">Tentang</button>
                </div>

                <div class="header-block">
                    <div class="brand">HappyFood</div>
                    <div class="subtitle">Sistem Inventory</div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES) ?>
                    </div>
                <?php endif; ?>

                <div class="tab-content" style="width:100%;">
                    <!-- LOGIN PANE -->
                    <div id="pane-login" class="pane <?= $active_tab === 'login' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tab-login">
                        <div>
                            <div class="welcome-title">Selamat Datang!</div>
                            <div class="welcome-sub">Silahkan Masuk Untuk Melanjutkan Ke Dashboard</div>
                        </div>

                        <form method="POST" action="">
                            <div class="form-row">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text input-icon">👤</span>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required value="<?= htmlspecialchars($username, ENT_QUOTES) ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text input-icon">🔒</span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                                </div>
                            </div>

                            <div class="form-actions">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-login">Masuk</button>
                                </div>
                            </div>
                        </form>

                        <div class="left-footer">
                            <div class="minor">Default: <strong>shofa</strong> / password</div>
                            <div class="minor">Butuh bantuan? Hubungi admin</div>
                        </div>
                    </div>

                    <!-- ABOUT PANE -->
                    <div id="pane-about" class="pane <?= $active_tab === 'about' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tab-about">
                        <div style="margin-top:6px;">
                            <div class="welcome-title" style="font-size:22px;">Tentang Sistem</div>
                            <div class="about-block">
                                <p><strong>HappyFood Inventory</strong> adalah sistem sederhana untuk mengelola stok dan persediaan produk makanan di toko/katering kecil. Sistem ini memudahkan admin untuk memantau stok, menambah produk baru, dan melihat riwayat transaksi secara ringkas.</p>

                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet.</p>

                                <p>Suspendisse potenti. Phasellus euismod libero in neque molestie et elementum lorem facilisis. Cras ultricies ligula sed magna dictum porta. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus.</p>

                                <p class="minor">Versi: <strong>0.1 (demo)</strong> &nbsp;•&nbsp; Developer: Tim Outsource</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="login-right" aria-hidden="true"></div>
        </div>
    </div>

    <script>
        (function(){
            const tabs = document.querySelectorAll('.tab-btn');
            const panes = document.querySelectorAll('.pane');

            function activate(targetId, pushState = true) {
                tabs.forEach(t => t.classList.toggle('active', t.dataset.target === targetId));
                panes.forEach(p => p.classList.toggle('active', p.id === targetId));
                tabs.forEach(t => t.setAttribute('aria-selected', t.dataset.target === targetId ? 'true' : 'false'));
                if (pushState) {
                    const label = targetId === 'pane-about' ? 'about' : 'login';
                    const url = new URL(location);
                    url.searchParams.set('tab', label);
                    history.replaceState(null, '', url);
                }
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', function(){
                    activate(this.dataset.target);
                });
            });

            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam === 'about') {
                activate('pane-about', false);
            } else {
                const initial = document.querySelector('.pane.active') || document.getElementById('pane-login');
                activate(initial.id, false);
            }
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
