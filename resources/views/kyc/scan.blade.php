<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC ID Scanner</title>
    <style>
        :root {
            --bg: #f3f7f5;
            --card: #ffffff;
            --text: #13221a;
            --muted: #4b6358;
            --brand: #0e7c4d;
            --danger: #a32020;
            --border: #d7e5dd;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top, #ecf7f1, var(--bg));
            color: var(--text);
        }

        .page {
            max-width: 900px;
            margin: 36px auto;
            padding: 0 16px 40px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.06);
        }

        h1 {
            margin: 0 0 6px;
            font-size: clamp(1.4rem, 2.7vw, 1.95rem);
        }

        .subtitle {
            margin: 0 0 20px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .alert {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 0.92rem;
        }

        .alert-error {
            background: #ffe9e9;
            color: #7d1111;
            border: 1px solid #f5c5c5;
        }

        .alert-success {
            background: #e7f7ed;
            color: #145534;
            border: 1px solid #c6e9d3;
        }

        .upload-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }

        input[type="file"],
        input[type="text"],
        input[type="date"] {
            width: 100%;
            border: 1px solid #c5d9cd;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.95rem;
            background: #fff;
        }

        input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(14, 124, 77, 0.14);
        }

        .button-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
        }

        button {
            border: 0;
            border-radius: 10px;
            background: var(--brand);
            color: #fff;
            font-weight: 600;
            padding: 10px 16px;
            cursor: pointer;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .preview {
            margin-top: 10px;
            border: 1px dashed #b7ccc0;
            border-radius: 12px;
            padding: 8px;
            background: #f8fcfa;
        }

        .preview img {
            max-width: 100%;
            max-height: 320px;
            display: none;
            border-radius: 8px;
            object-fit: contain;
        }

        .preview img.visible {
            display: block;
        }

        .fields {
            margin-top: 22px;
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field-span {
            grid-column: 1 / -1;
        }

        .hint {
            margin-top: 12px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        @media (max-width: 720px) {
            .fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <h1>KYC ID Scanner</h1>
        <p class="subtitle">Upload an ID image, scan with Google Vision, review auto-filled fields, and edit if needed.</p>

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        @if (!empty($scanSuccess))
            <div class="alert alert-success">ID scanned successfully. Please verify the extracted fields below.</div>
        @endif

        <form id="idScanForm" method="POST" action="{{ route('kyc.scan-id') }}" enctype="multipart/form-data">
            @csrf

            <div class="upload-grid">
                <div>
                    <label for="id_image">ID Image (JPG, JPEG, PNG, max 5MB)</label>
                    <input
                        type="file"
                        id="id_image"
                        name="id_image"
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        required
                    >
                </div>
            </div>

            <div class="preview">
                <img
                    id="idPreview"
                    src="{{ !empty($uploadedPreview) ? $uploadedPreview : '' }}"
                    alt="Uploaded ID preview"
                    class="{{ !empty($uploadedPreview) ? 'visible' : '' }}"
                >
            </div>

            <div class="button-row">
                <button type="submit" id="scanButton">Scan ID</button>
                <div id="loadingState" class="loading" hidden>Scanning image, please wait...</div>
            </div>

            <div class="fields">
                <div>
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $data['name'] ?? '') }}"
                        autocomplete="name"
                    >
                </div>

                <div>
                    <label for="dob">Date of Birth</label>
                    <input
                        type="date"
                        id="dob"
                        name="dob"
                        value="{{ old('dob', $data['dob'] ?? '') }}"
                    >
                </div>

                <div class="field-span">
                    <label for="address">Address</label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="{{ old('address', $data['address'] ?? '') }}"
                        autocomplete="street-address"
                    >
                </div>

                <div class="field-span">
                    <label for="id_number">ID Number</label>
                    <input
                        type="text"
                        id="id_number"
                        name="id_number"
                        value="{{ old('id_number', $data['id_number'] ?? '') }}"
                    >
                </div>
            </div>
        </form>

        <p class="hint">You can edit any auto-filled field before saving the KYC record.</p>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('idScanForm');
    const fileInput = document.getElementById('id_image');
    const preview = document.getElementById('idPreview');
    const loadingState = document.getElementById('loadingState');
    const scanButton = document.getElementById('scanButton');
    const maxBytes = 5 * 1024 * 1024;

    const showPreview = (file) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            preview.src = String(event.target?.result || '');
            preview.classList.add('visible');
        };
        reader.readAsDataURL(file);
    };

    fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!file) {
            preview.src = '';
            preview.classList.remove('visible');
            return;
        }

        if (file.size > maxBytes) {
            alert('File size must be 5MB or below.');
            fileInput.value = '';
            preview.src = '';
            preview.classList.remove('visible');
            return;
        }

        showPreview(file);
    });

    form.addEventListener('submit', () => {
        loadingState.hidden = false;
        scanButton.disabled = true;
        scanButton.textContent = 'Scanning...';
    });
})();
</script>
</body>
</html>
