"""
face_verify.py - Verificacion facial para ESP32-S3 CAM
Usa OpenCV LBPH (LBPHFaceRecognizer) - sin dlib, compatible con Python 3.12, 3.13, 3.14.
Requisitos: pip install opencv-contrib-python numpy

Para registrar un rostro, guarda el archivo como face_<user_id>.jpg en uploads/rostros/
"""
import sys
import os
import re
import json
from datetime import datetime
import cv2
import numpy as np

# ===== RUTAS =====
BASE_PATH = os.getenv('PRERMI_BASE_PATH', '/var/www/html/PRERMI')
ROSTROS_PATH    = os.path.join(BASE_PATH, "uploads", "rostros")
CAPTURAS_PATH   = os.path.join(BASE_PATH, "uploads", "capturas_cam")

# ===== TOLERANCIA LBPH =====
# LBPH retorna "confianza" donde valores MAS BAJOS = mejor match.
# < 80: match excelente, < 120: muy bueno, < 140: util para camaras IoT con ruido.
# Se flexibiliza para reducir falsos negativos en ESP32-S3 CAM.
NIGHT_MATCH_THRESHOLD = 155.0
DAY_STRICT_FACTOR = 0.97  # Dia ligeramente mas estricto que noche

# Probabilidad minima para considerar valido el match.
# Protege contra detecciones "dudosas" aun cuando pasen por threshold LBPH.
BASE_MIN_PROBABILITY = 0.22
MIN_IMAGES_PER_USER = 15

# ===== DETECTOR HAAR =====
CASCADE_FILENAME = "haarcascade_frontalface_default.xml"


def resolve_cascade_path():
    """Resuelve ruta del Haar cascade sin depender de cv2.data."""
    candidates = []

    # 1) Override explicito por variable de entorno
    env_path = os.getenv("PRERMI_HAAR_CASCADE")
    if env_path:
        candidates.append(env_path)

    # 2) OpenCV moderno: cv2.data.haarcascades (si existe)
    cv2_data = getattr(cv2, "data", None)
    if cv2_data is not None:
        haar_dir = getattr(cv2_data, "haarcascades", None)
        if haar_dir:
            candidates.append(os.path.join(haar_dir, CASCADE_FILENAME))

    # 3) Ubicaciones tipicas en Linux (apt/pip/source)
    candidates.extend([
        f"/usr/share/opencv4/haarcascades/{CASCADE_FILENAME}",
        f"/usr/share/opencv/haarcascades/{CASCADE_FILENAME}",
        f"/usr/local/share/opencv4/haarcascades/{CASCADE_FILENAME}",
        f"/usr/local/share/opencv/haarcascades/{CASCADE_FILENAME}",
    ])

    # 4) Relative a cv2 (site-packages)
    cv2_file = getattr(cv2, "__file__", "")
    if cv2_file:
        cv2_dir = os.path.dirname(cv2_file)
        candidates.extend([
            os.path.join(cv2_dir, "data", CASCADE_FILENAME),
            os.path.join(cv2_dir, "haarcascades", CASCADE_FILENAME),
        ])

    # 5) Junto al script (permite empaquetar el XML con el proyecto)
    script_dir = os.path.dirname(os.path.abspath(__file__))
    candidates.append(os.path.join(script_dir, CASCADE_FILENAME))

    seen = set()
    for path in candidates:
        if not path:
            continue
        norm = os.path.normpath(path)
        if norm in seen:
            continue
        seen.add(norm)
        if os.path.isfile(norm):
            return norm

    return ""


CASCADE_PATH = resolve_cascade_path()
_cascade = cv2.CascadeClassifier(CASCADE_PATH) if CASCADE_PATH else cv2.CascadeClassifier()


# ===== UTILIDADES DE IMAGEN =====

def adjust_gamma(image, gamma=0.5):
    """Correccion gamma para aclarar imagenes oscuras. gamma < 1 = mas brillante."""
    table = np.array([(i / 255.0) ** gamma * 255
                      for i in range(256)], dtype="uint8")
    return cv2.LUT(image, table)


def preprocess_to_gray(img):
    """Preprocesa imagen BGR y retorna gris mejorado para reconocimiento."""
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    mean_brightness = np.mean(gray)

    # Correccion gamma agresiva para imagenes oscuras (tipico de ESP32-S3 CAM)
    if mean_brightness < 80:
        gamma_val = 0.35 if mean_brightness < 40 else 0.55
        img = adjust_gamma(img, gamma=gamma_val)
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    # CLAHE: mejora contraste local sin saturar
    clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8, 8))
    gray = clahe.apply(gray)

    # Reduccion de ruido (importante para alta ganancia de camara)
    gray = cv2.fastNlMeansDenoising(gray, None, h=12,
                                    templateWindowSize=7, searchWindowSize=21)
    return gray


def resolve_dynamic_thresholds(now_hour=None):
    if now_hour is None:
        now_hour = datetime.now().hour

    is_day = 7 <= now_hour < 19
    match_threshold = NIGHT_MATCH_THRESHOLD * DAY_STRICT_FACTOR if is_day else NIGHT_MATCH_THRESHOLD
    min_probability = BASE_MIN_PROBABILITY * 1.05 if is_day else BASE_MIN_PROBABILITY
    return is_day, round(match_threshold, 2), round(min_probability, 4)


def quality_gate(gray, roi):
    """Filtro de calidad previo: brillo, contraste y enfoque."""
    brightness = float(np.mean(gray))
    contrast = float(np.std(gray))
    sharpness = float(cv2.Laplacian(roi, cv2.CV_64F).var())

    if brightness < 25 or brightness > 235:
        return False, "low_quality_brightness", brightness, contrast, sharpness
    if contrast < 12:
        return False, "low_quality_contrast", brightness, contrast, sharpness
    if sharpness < 25:
        return False, "low_quality_blur", brightness, contrast, sharpness

    return True, "ok", brightness, contrast, sharpness


def detect_face_roi(gray):
    """
    Detecta el rostro mas prominente con Haar Cascade.
    Retorna la region recortada (ROI) redimensionada a 200x200, o None si no detecta.
    Intenta con parametros normales, luego permisivos, luego imagen escalada x2.
    """
    if _cascade.empty():
        return None

    for (scale, neighbors, min_size) in [
        (1.10, 5, (50, 50)),
        (1.05, 3, (35, 35)),
    ]:
        faces = _cascade.detectMultiScale(
            gray, scaleFactor=scale, minNeighbors=neighbors,
            minSize=min_size, flags=cv2.CASCADE_SCALE_IMAGE
        )
        if len(faces) > 0:
            x, y, w, h = max(faces, key=lambda f: f[2] * f[3])
            roi = gray[y:y + h, x:x + w]
            return cv2.resize(roi, (200, 200))

    # Ultimo intento: escalar imagen x2 y buscar de nuevo
    scaled = cv2.resize(gray, None, fx=2.0, fy=2.0,
                        interpolation=cv2.INTER_CUBIC)
    faces = _cascade.detectMultiScale(
        scaled, scaleFactor=1.05, minNeighbors=3,
        minSize=(40, 40), flags=cv2.CASCADE_SCALE_IMAGE
    )
    if len(faces) > 0:
        x, y, w, h = max(faces, key=lambda f: f[2] * f[3])
        # Coordenadas originales (dividir por factor de escala 2)
        roi = gray[y // 2:(y + h) // 2, x // 2:(x + w) // 2]
        if roi.size > 0:
            return cv2.resize(roi, (200, 200))

    # Fallback: recorte centrado (evita rechazo total por fallo puntual de Haar)
    h, w = gray.shape[:2]
    side = int(min(h, w) * 0.72)
    if side >= 120:
        x0 = max(0, (w - side) // 2)
        y0 = max(0, (h - side) // 2)
        center_roi = gray[y0:y0 + side, x0:x0 + side]
        if center_roi.size > 0:
            return cv2.resize(center_roi, (200, 200))

    return None


def build_variants(roi):
    """Genera variantes de una ROI para hacer prediccion mas robusta."""
    variants = [roi]
    variants.append(cv2.equalizeHist(roi))
    variants.append(cv2.GaussianBlur(roi, (3, 3), 0))
    return variants


def aggregate_predictions(recognizer, roi):
    """
    Ejecuta varias predicciones sobre variantes de la misma ROI.
    Retorna label, confidence, probability y votes para el label ganador.
    """
    label_votes = {}
    label_best_conf = {}

    for variant in build_variants(roi):
        label, conf = recognizer.predict(variant)
        label = int(label)
        conf = float(conf)
        label_votes[label] = label_votes.get(label, 0) + 1
        if label not in label_best_conf or conf < label_best_conf[label]:
            label_best_conf[label] = conf

    winner_label = min(
        label_votes.keys(),
        key=lambda l: (-label_votes[l], label_best_conf[l])
    )
    winner_conf = label_best_conf[winner_label]
    probability = round(max(0.0, 1.0 - winner_conf / 200.0), 4)

    return winner_label, winner_conf, probability, label_votes[winner_label]


def run_server_consensus(recognizer, roi, attempts=3):
    """
    Ejecuta consenso del lado servidor con la misma captura.
    Realiza 3 comparaciones y decide por mayoria de usuario + mejor confianza.
    """
    base_variants = build_variants(roi)
    if len(base_variants) == 0:
        base_variants = [roi]

    attempt_results = []
    for idx in range(attempts):
        variant = base_variants[idx % len(base_variants)]
        label, confidence, probability, _ = aggregate_predictions(recognizer, variant)
        attempt_results.append({
            "label": int(label),
            "confidence": float(confidence),
            "probability": float(probability),
        })

    label_votes = {}
    for result in attempt_results:
        label = result["label"]
        label_votes[label] = label_votes.get(label, 0) + 1

    winner_label = min(
        label_votes.keys(),
        key=lambda l: (
            -label_votes[l],
            min(r["confidence"] for r in attempt_results if r["label"] == l),
        ),
    )

    winner_attempts = [r for r in attempt_results if r["label"] == winner_label]
    winner_conf = min(r["confidence"] for r in winner_attempts)
    winner_prob = max(r["probability"] for r in winner_attempts)
    winner_votes = label_votes[winner_label]

    return int(winner_label), float(winner_conf), float(winner_prob), int(winner_votes), attempt_results


def extract_user_id(filename):
    """Extrae user_id de nombres tipo face_<id>.jpg o face_<id>_*.jpg"""
    m = re.search(r'face_(\d+)(?:_|\.)', filename, re.IGNORECASE)
    return int(m.group(1)) if m else None


def extract_user_id_from_path(root_path, filename):
    """Extrae user_id desde carpeta (preferido) o desde nombre de archivo."""
    # Formato esperado principal: uploads/rostros/<user_id>/foto.jpg
    folder_name = os.path.basename(root_path)
    if folder_name.isdigit():
        return int(folder_name)

    # Fallback 1: extraer ID numerico desde carpeta (ej: user_12, id-45)
    folder_match = re.search(r'(\d+)', folder_name)
    if folder_match:
        return int(folder_match.group(1))

    # Fallback 2: si hay subcarpetas, buscar un segmento con ID en el path relativo
    try:
        rel = os.path.relpath(root_path, ROSTROS_PATH)
        parts = [p for p in rel.split(os.sep) if p not in ('.', '')]
        for part in reversed(parts):
            if part.isdigit():
                return int(part)
            part_match = re.search(r'(\d+)', part)
            if part_match:
                return int(part_match.group(1))
    except Exception:
        pass

    # Fallback historico: face_<id>.jpg
    return extract_user_id(filename)


# ===== CARGA DE ENTRENAMIENTO =====

def load_training_data():
    """
    Carga todos los archivos face_<id>.jpg para entrenamiento LBPH.
    Busca primero en uploads/rostros/, luego en uploads/capturas_cam/
    para soportar ambas ubicaciones de archivos registrados.
    """
    faces, labels = [], []
    per_user_counts = {}

    # Prioridad: uploads/rostros/<user_id>/ (estructura oficial).
    # capturas_cam queda como fallback historico.
    search_dirs = [ROSTROS_PATH, CAPTURAS_PATH]

    found_dirs = [d for d in search_dirs if os.path.isdir(d)]
    if not found_dirs:
        return [], [], {}

    raw_samples = []

    for search_dir in found_dirs:
        for root, _, filenames in os.walk(search_dir):
            for filename in sorted(filenames):
                if not filename.lower().endswith((".jpg", ".jpeg", ".png", ".webp")):
                    continue

                user_id = extract_user_id_from_path(root, filename)
                if user_id is None:
                    continue

                path = os.path.join(root, filename)
                img = cv2.imread(path)
                if img is None:
                    continue

                gray = preprocess_to_gray(img)
                roi = detect_face_roi(gray)
                if roi is None:
                    roi = cv2.resize(gray, (200, 200))

                ok, _, _, _, sharpness = quality_gate(gray, roi)
                if not ok and sharpness < 30:
                    continue

                raw_samples.append((user_id, roi))
                per_user_counts[user_id] = per_user_counts.get(user_id, 0) + 1

        # No cortar aqui: combinar muestras de todas las ubicaciones disponibles
        # evita quedar corto cuando rostros/ y capturas_cam tienen datos parciales.

    valid_users = {uid for uid, c in per_user_counts.items() if c >= MIN_IMAGES_PER_USER}
    if not valid_users:
        return [], [], per_user_counts

    for user_id, roi in raw_samples:
        if user_id not in valid_users:
            continue
        for variant in build_variants(roi):
            faces.append(variant)
            labels.append(user_id)

    return faces, labels, per_user_counts


# ===== MAIN =====

def main():
    if _cascade.empty():
        print(json.dumps({
            "success": False,
            "message": "Haar cascade not found",
            "cascade_file": CASCADE_PATH,
        }))
        sys.exit(1)

    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "message": "No image path provided"}))
        sys.exit(1)

    image_path = sys.argv[1]

    if not os.path.isfile(image_path):
        print(json.dumps({"success": False, "message": "Image file not found"}))
        sys.exit(1)

    # --- Leer imagen de entrada ---
    img = cv2.imread(image_path)
    if img is None:
        print(json.dumps({"success": False, "message": "Cannot read image"}))
        sys.exit(1)

    gray_input = preprocess_to_gray(img)
    roi_input = detect_face_roi(gray_input)

    if roi_input is None:
        print(json.dumps({
            "success": False,
            "message": "No face detected in captured image"
        }))
        sys.exit(0)

    # --- Filtro de calidad previo ---
    quality_ok, quality_reason, brightness, contrast, sharpness = quality_gate(gray_input, roi_input)
    quality_warning = None
    if not quality_ok:
        # Rechazar solo imagenes extremadamente degradadas.
        extreme_quality_fail = (
            brightness < 15 or brightness > 245 or sharpness < 10
        )
        if extreme_quality_fail:
            print(json.dumps({
                "success": False,
                "message": "Image quality rejected",
                "quality_reason": quality_reason,
                "brightness": round(brightness, 2),
                "contrast": round(contrast, 2),
                "sharpness": round(sharpness, 2)
            }))
            sys.exit(0)
        quality_warning = quality_reason

    # --- Cargar datos de entrenamiento ---
    train_faces, train_labels, user_counts = load_training_data()

    if len(train_faces) == 0:
        print(json.dumps({
            "success": False,
            "message": "No registered faces found with minimum training set",
            "min_images_per_user": MIN_IMAGES_PER_USER,
            "counts": user_counts
        }))
        sys.exit(0)

    is_day, dynamic_threshold, dynamic_min_probability = resolve_dynamic_thresholds()

    # --- Entrenar reconocedor LBPH ---
    recognizer = cv2.face.LBPHFaceRecognizer_create(
        radius=2, neighbors=16, grid_x=8, grid_y=8
    )
    recognizer.train(train_faces, np.array(train_labels, dtype=np.int32))

    # --- Predecir con consenso de 3 comparaciones del lado servidor ---
    label, confidence, probability, votes, attempts_data = run_server_consensus(
        recognizer, roi_input, attempts=3
    )

    # Aceptar si supera threshold principal o si pasa por decision estable por votos.
    vote_stable_match = votes >= 2 and confidence <= (dynamic_threshold + 6.0)

    if (confidence <= dynamic_threshold and probability >= dynamic_min_probability) or vote_stable_match:
        print(json.dumps({
            "success": True,
            "user_id": int(label),
            "confidence": round(float(confidence), 2),
            "probability": probability,
            "votes": votes,
            "attempts": 3,
            "attempts_data": [
                {
                    "user_id": int(item["label"]),
                    "confidence": round(float(item["confidence"]), 2),
                    "probability": round(float(item["probability"]), 4)
                }
                for item in attempts_data
            ],
            "is_day": is_day,
            "threshold": dynamic_threshold,
            "min_probability": dynamic_min_probability,
            "quality_warning": quality_warning,
            "brightness": round(brightness, 2),
            "contrast": round(contrast, 2),
            "sharpness": round(sharpness, 2)
        }))
    else:
        print(json.dumps({
            "success": False,
            "message": "Face not recognized",
            "user_id": 0,
            "confidence": round(float(confidence), 2),
            "probability": probability,
            "votes": votes,
            "attempts": 3,
            "attempts_data": [
                {
                    "user_id": int(item["label"]),
                    "confidence": round(float(item["confidence"]), 2),
                    "probability": round(float(item["probability"]), 4)
                }
                for item in attempts_data
            ],
            "threshold": dynamic_threshold,
            "min_probability": dynamic_min_probability,
            "is_day": is_day,
            "brightness": round(brightness, 2),
            "contrast": round(contrast, 2),
            "sharpness": round(sharpness, 2)
        }))


if __name__ == "__main__":
    main()
