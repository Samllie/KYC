# PaddleOCR Flask Service

## Run Locally

Use Python 3.11 for best PaddleOCR compatibility on Windows.

1. Create virtual environment:

```bash
py -3.11 -m venv .venv
```

2. Activate environment:

```bash
.venv\Scripts\activate
```

3. Install dependencies:

```bash
python -m pip install -r requirements.txt
```

4. Start the API:

```bash
python app.py
```

If `pip` is not available as a direct command, always use `python -m pip`.

Service endpoints:
- `GET /health`
- `POST /ocr` (form-data field: `image`)

## PHP Integration

Set these environment variables in Apache/PHP (optional):

- `PYTHON_OCR_URL` (default: `http://127.0.0.1:5001/ocr`)
- `PYTHON_OCR_HEALTH_URL` (default: `http://127.0.0.1:5001/health`)
