import os

# Prevent Paddle CPU OneDNN fused-conv crashes seen in this environment.
os.environ.setdefault("FLAGS_use_mkldnn", "0")
os.environ.setdefault("ONEDNN_VERBOSE", "0")

from flask import Flask, request, jsonify
import numpy as np
import cv2
from paddleocr import PaddleOCR

app = Flask(__name__)

# Initialize OCR once at startup for better latency.
ocr_engine = PaddleOCR(use_angle_cls=True, lang="en", show_log=False)


def preprocess_image(file_bytes: bytes) -> np.ndarray:
    image_np = np.frombuffer(file_bytes, dtype=np.uint8)
    image = cv2.imdecode(image_np, cv2.IMREAD_COLOR)
    if image is None:
        raise ValueError("Unable to decode image")

    max_width = 1800
    h, w = image.shape[:2]
    if w > max_width:
        scale = max_width / float(w)
        image = cv2.resize(image, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)

    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    denoised = cv2.bilateralFilter(gray, 7, 50, 50)
    normalized = cv2.normalize(denoised, None, 0, 255, cv2.NORM_MINMAX)
    return normalized


def run_paddle_ocr(processed: np.ndarray):
    result = None
    try:
        result = ocr_engine.ocr(processed, cls=True)
    except Exception:
        # Fallback: skip detection and run recognition on the full image.
        # This avoids some CPU OneDNN/fused-conv crashes in specific builds.
        result = ocr_engine.ocr(processed, det=False, rec=True, cls=False)

    texts = []
    confidences = []

    for page in result or []:
        if not page:
            continue
        for line in page:
            if not line or len(line) < 2:
                continue
            text_info = line[1]
            if not text_info or len(text_info) < 2:
                continue
            text_value = str(text_info[0]).strip()
            confidence = float(text_info[1]) if text_info[1] is not None else 0.0
            if text_value:
                texts.append(text_value)
                confidences.append(round(confidence, 4))

    return texts, confidences


@app.get("/health")
def health():
    return jsonify({"success": True, "message": "PaddleOCR service is running"})


@app.post("/ocr")
def ocr_endpoint():
    if "image" not in request.files:
        return jsonify({"success": False, "message": "No image file received"}), 400

    image_file = request.files["image"]
    file_bytes = image_file.read()
    if not file_bytes:
        return jsonify({"success": False, "message": "Uploaded image is empty"}), 400

    try:
        processed = preprocess_image(file_bytes)
        texts, confidences = run_paddle_ocr(processed)
        return jsonify({
            "success": True,
            "text": texts,
            "confidence": confidences,
        })
    except Exception as exc:
        return jsonify({"success": False, "message": f"OCR failed: {exc}"}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=False)
