<?php
require_once '../config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sterling insurance Company Incorporated</title>
    <link rel='icon' type='image/png' href='../css/images/SterlingLogo.png'>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../public/css/index.css">
    <link rel="stylesheet" href="../../public/css/global.css">
    <style>
        .type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .type-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #d8e7de;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 8px 24px rgba(20, 58, 41, 0.1);
        }
        
        .type-card:nth-child(1) {
            border-color: #c8ddf6;
        }
        
        .type-card:nth-child(2) {
            border-color: #bbe6c9;
        }

        .type-card:nth-child(3) {
            border-color: #f3c792;
        }
        
        .type-card:hover {
            box-shadow: 0 16px 34px rgba(20, 58, 41, 0.17);
            transform: translateY(-4px);
        }
        
        .type-card:nth-child(1):hover {
            background: #f3f9ff;
        }
        
        .type-card:nth-child(2):hover {
            background: #f1fdf4;
        }

        .type-card:nth-child(3):hover {
            background: #fff7ed;
        }
        
        .type-card:nth-child(1) .type-card-btn {
            background: #2c75cc;
        }
        
        .type-card:nth-child(2) .type-card-btn {
            background: #157345;
        }

        .type-card:nth-child(3) .type-card-btn {
            background: #c06a12;
        }
        
        .type-card:nth-child(1):hover .type-card-btn {
            background: #1f5ea9;
        }
        
        .type-card:nth-child(2):hover .type-card-btn {
            background: #0f5a35;
        }

        .type-card:nth-child(3):hover .type-card-btn {
            background: #9b550d;
        }

        .type-card:nth-child(3) .type-card-icon {
            color: #b55e09;
        }
        
        .type-card-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .type-card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #142c21;
        }
        
        .type-card-desc {
            color: var(--gray-500);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .type-card-btn {
            display: inline-block;
            padding: 10px 30px;
            background: #12663d;
            color: white;
            border-radius: 10px;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .type-card:hover .type-card-btn {
            background: #0f5a35;
        }

        body.kyc-compact .topbar {
            height: 56px;
            padding: 0 22px;
        }

        body.kyc-compact .topbar-left h1 {
            font-size: 0.95rem;
        }

        body.kyc-compact .breadcrumb-trail {
            font-size: 0.64rem;
        }

        body.kyc-compact .content {
            padding: 18px 22px;
        }

        body.kyc-compact .steps-bar {
         
            margin-bottom: 14px;
            border-radius: 12px;
        }

        body.kyc-compact .steps-bar.sticky {
            top: 56px;
        }

        body.kyc-compact .step {
            gap: 8px;
        }

        body.kyc-compact .step-num {
            width: 28px;
            height: 28px;
            font-size: 0.72rem;
        }

        body.kyc-compact .step-info span:first-child {
            font-size: 0.62rem;
        }

        body.kyc-compact .step-info strong {
            font-size: 0.72rem;
        }

        body.kyc-compact .card {
            margin-bottom: 12px;
            border-radius: 12px;
        }

        body.kyc-compact .card-header {
            padding: 14px 18px 0;
        }

        body.kyc-compact .card-title {
            font-size: 0.86rem;
        }

        body.kyc-compact .card-subtitle {
            font-size: 0.7rem;
        }

        body.kyc-compact .card-body {
         
        }

        body.kyc-compact .type-selector {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin: 14px 0 2px;
        }

        body.kyc-compact .type-card {
            border-radius: 12px;
            padding: 20px 18px;
            box-shadow: 0 6px 20px rgba(20, 58, 41, 0.1);
        }

        body.kyc-compact .type-card-icon {
            font-size: 2.3rem;
            margin-bottom: 10px;
        }

        body.kyc-compact .type-card-title {
            font-size: 1.06rem;
            margin-bottom: 8px;
        }

        body.kyc-compact .type-card-desc {
            font-size: 0.8rem;
            line-height: 1.4;
            margin-bottom: 12px;
        }

        body.kyc-compact .type-card-btn {
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            body.kyc-compact .topbar {
                height: auto;
                min-height: 52px;
                padding: 8px 12px;
            }

            body.kyc-compact .content {
                padding: 14px;
            }

            body.kyc-compact .steps-bar {
                padding: 10px 12px;
                margin-bottom: 10px;
            }

            body.kyc-compact .steps-bar.sticky {
                top: 52px;
            }

            body.kyc-compact .card-header {
                padding: 10px 12px 0;
            }

            body.kyc-compact .card-body {
                padding: 10px 12px;
            }

            body.kyc-compact .type-selector {
                grid-template-columns: 1fr;
                gap: 10px;
                margin-top: 10px;
            }

            body.kyc-compact .type-card {
                padding: 16px 14px;
            }
        }
    </style>
</head>
<body class="kyc-compact">

<?php
$activePage = 'kyc-verification';
include '../includes/sidebar.php';
?>

<!-- ═══════════════════════════════════════════════ MAIN -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1>KYC Verification</h1>
            <div class="breadcrumb-trail">
                <i class="bi bi-house" style="font-size:.65rem;"></i>
                Dashboard &rsaquo; Clients &rsaquo; <span>Select Client Type</span>
            </div>
        </div>
        <div class="topbar-right">
        </div>
    </header>

    <!-- Content -->
    <main class="content">


        <!-- Steps -->
        <div class="steps-bar">
            <div class="step active" id="step-1">
                <div class="step-num">1</div>
                <div class="step-info">
                    <span>Step 1</span>
                    <strong>Client Type</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-2">
                <div class="step-num">2</div>
                <div class="step-info">
                    <span>Step 2</span>
                    <strong>Client Details</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-3">
                <div class="step-num">3</div>
                <div class="step-info">
                    <span>Step 3</span>
                    <strong>Contact Details</strong>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-4">
                <div class="step-num">4</div>
                <div class="step-info">
                    <span>Step 4</span>
                    <strong>Review</strong>
                </div>
            </div>
        </div>

        <!-- Client Type Selection -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Select Client Type</div>
                    <div class="card-subtitle">Choose the type of client you're registering for KYC verification</div>
                </div>
            </div>

            <div class="card-body">
                <div class="type-selector">
                    <!-- Individual Client Card -->
                    <a href="kyc-individual.php" class="type-card">
                        <div class="type-card-icon">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="type-card-title">Individual Client</div>
                        <div class="type-card-desc">
                            Register a natural person as a client. Complete personal information and identity verification.
                        </div>
                        <div class="type-card-btn">Select Individual</div>
                    </a>

                    <!-- Corporate Client Card -->
                    <a href="kyc-corporate.php" class="type-card">
                        <div class="type-card-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="type-card-title">Corporate Client</div>
                        <div class="type-card-desc">
                            Register a company or organization as a client. Provide corporate information and business verification.
                        </div>
                        <div class="type-card-btn">Select Corporate</div>
                    </a>

                    <!-- Obligee Client Card -->
                    <a href="kyc-corporate.php?type=obligee" class="type-card">
                        <div class="type-card-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="type-card-title">Obligee Client</div>
                        <div class="type-card-desc">
                            Register an obligee client using the same workflow and requirements as corporate registration.
                        </div>
                        <div class="type-card-btn">Select Obligee</div>
                    </a>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
// ── Collapse Steps to Tiny Progress on Scroll ───────────────
const stepsBar = document.querySelector('.steps-bar');
const mainContent = document.querySelector('.main');

window.addEventListener('scroll', function() {
    if (!stepsBar) return;

    const scrollPosition = mainContent?.getBoundingClientRect().top || 0;

    if (scrollPosition < 0) {
        stepsBar.classList.add('sticky');
    } else {
        stepsBar.classList.remove('sticky');
    }
});
</script>

</body>
</html>
