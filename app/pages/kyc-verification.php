<?php
require_once '../config/session.php';
requireLogin();

$requestedClassification = strtolower(trim($_GET['classification'] ?? 'client'));
$defaultClassification = $requestedClassification === 'agent' ? 'agent' : 'client';
$isAgentDefault = $defaultClassification === 'agent';
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

        .selection-group {
            border: 1px solid #d8e7de;
            border-radius: 16px;
            padding: 16px 16px 6px;
            background: #f9fcfa;
            margin-bottom: 16px;
        }

        .selection-group.preferred {
            border-color: #9dcdb4;
            box-shadow: 0 10px 24px rgba(20, 58, 41, 0.08);
        }

        .selection-group.client-group.preferred {
            border-color: #9dcdb4;
            box-shadow: 0 10px 24px rgba(20, 58, 41, 0.08);
        }

        .selection-group.agent-group.preferred {
            border-color: #d9c2ff;
            box-shadow: 0 10px 24px rgba(91, 33, 182, 0.08);
        }

        .selection-group-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 6px 6px 0;
        }

        .selection-group-header i {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #e9f5ee;
            color: #0f5a35;
        }

        .selection-group.agent-group .selection-group-header i {
            background: #f3e8ff;
            color: #5b21b6;
        }

        .selection-group-title {
            font-size: 1rem;
            font-weight: 700;
            color: #173427;
        }

        .selection-group.agent-group .selection-group-title {
            color: #4c1d95;
        }

        .selection-group-subtitle {
            margin: 2px 0 0 40px;
            font-size: 0.82rem;
            color: var(--gray-500);
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

        .type-card.individual {
            border-color: #c8ddf6;
        }

        .type-card.corporate {
            border-color: #bbe6c9;
        }

        .type-card.obligee {
            border-color: #e3c39c;
        }

        .type-card.agent {
            border-color: #d9c2ff;
        }

        .type-card.individual:hover {
            background: #f3f9ff;
        }

        .type-card.corporate:hover {
            background: #f1fdf4;
        }

        .type-card.obligee:hover {
            background: #fbf3eb;
        }

        .type-card.agent:hover {
            background: #faf5ff;
        }

        .type-card.individual .type-card-btn {
            background: #2f7fd6;
        }

        .type-card.corporate .type-card-btn {
            background: #2ea371;
        }

        .type-card.obligee .type-card-btn {
            background: #8b5a2b;
        }

        .type-card.agent .type-card-btn {
            background: #7c3aed;
        }

        .type-card.individual:hover .type-card-btn {
            background: #1f5ea9;
        }

        .type-card.corporate:hover .type-card-btn {
            background: #16633f;
        }

        .type-card.obligee:hover .type-card-btn {
            background: #6b4320;
        }

        .type-card.agent:hover .type-card-btn {
            background: #6d28d9;
        }

        .type-card.individual .type-card-icon {
            color: #2f7fd6;
        }

        .type-card.corporate .type-card-icon {
            color: #2ea371;
        }

        .type-card.obligee .type-card-icon {
            color: #8b5a2b;
        }

        .type-card.agent .type-card-icon {
            color: #7c3aed;
        }

        .type-card:nth-child(3) .type-card-icon {
            color: #b55e09;
        }

        .type-card.individual .type-card-icon {
            color: #2f7fd6;
        }

        .type-card.corporate .type-card-icon {
            color: #2ea371;
        }

        .type-card.obligee .type-card-icon {
            color: #8b5a2b;
        }

        .type-card.agent .type-card-icon {
            color: #7c3aed;
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

        body.kyc-compact .selection-group {
            padding: 12px 12px 2px;
            margin-bottom: 12px;
        }

        body.kyc-compact .selection-group-title {
            font-size: 0.9rem;
        }

        body.kyc-compact .selection-group-subtitle {
            font-size: 0.75rem;
            margin-left: 34px;
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

            body.kyc-compact .selection-group {
                padding: 12px 10px 2px;
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
                Dashboard &rsaquo; KYC Verification &rsaquo; <span>Select Registration Type</span>
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
                    <div class="card-title">Select Registration Type</div>
                    <div class="card-subtitle">Choose whether you're registering a client or an agent, then select the client type.</div>
                </div>
            </div>

            <div class="card-body">
                <section class="selection-group client-group <?php echo $isAgentDefault ? '' : 'preferred'; ?>">
                    <div class="selection-group-header">
                        <i class="bi bi-people"></i>
                        <div class="selection-group-title">Client Registrations</div>
                    </div>
                    <p class="selection-group-subtitle">Use this when registering policyholders and obligees as clients.</p>

                    <div class="type-selector">
                        <a href="kyc-individual.php?classification=client" class="type-card individual">
                            <div class="type-card-icon">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="type-card-title">Individual Client</div>
                            <div class="type-card-desc">
                                Register a natural person as a client. Complete personal information and identity verification.
                            </div>
                            <div class="type-card-btn">Select Individual Client</div>
                        </a>

                        <a href="kyc-corporate.php?type=corporate&amp;classification=client" class="type-card corporate">
                            <div class="type-card-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="type-card-title">Corporate Client</div>
                            <div class="type-card-desc">
                                Register a company or organization as a client. Provide corporate information and business verification.
                            </div>
                            <div class="type-card-btn">Select Corporate Client</div>
                        </a>

                        <a href="kyc-obligee.php?classification=client" class="type-card obligee">
                            <div class="type-card-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="type-card-title">Obligee Client</div>
                            <div class="type-card-desc">
                                Register an obligee client using a dedicated obligee workflow.
                            </div>
                            <div class="type-card-btn">Select Obligee Client</div>
                        </a>
                    </div>
                </section>

                <section class="selection-group agent-group <?php echo $isAgentDefault ? 'preferred' : ''; ?>">
                    <div class="selection-group-header">
                        <i class="bi bi-person-badge"></i>
                        <div class="selection-group-title">Agent Registrations</div>
                    </div>
                    <p class="selection-group-subtitle">Use this when the account being onboarded is an individual agent.</p>

                    <div class="type-selector">
                        <a href="kyc-individual.php?classification=agent" class="type-card agent">
                            <div class="type-card-icon">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="type-card-title">Individual Agent</div>
                            <div class="type-card-desc">
                                Register an individual as an agent with identity and contact verification.
                            </div>
                            <div class="type-card-btn">Select Individual Agent</div>
                        </a>
                    </div>
                </section>
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
