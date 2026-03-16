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
import cv2
import numpy as np

# ===== RUTAS =====
BASE_PATH = os.getenv('PRERMI_BASE_PATH', '/var/www/html/PRERMI')
ROSTROS_PATH    = os.path.join(BASE_PATH, "uploads", "rostros")
CAPTURAS_PATH   = os.path.join(BASE_PATH, "uploads", "capturas_cam")

# ===== TOLERANCIA LBPH =====
# LBPH retorna "confianza" donde valores MAS BAJOS = mejor match.
# < 80: match excelente, < 140: aceptable para ESP32 CAM con 1 imagen de entrenamiento.
# Nota: con mas imagenes de entrenamiento por usuario se puede bajar a 100-110.
MATCH_THRESHOLD = 140.0

# ===== DETECTOR HAAR =====
CASCADE_PATH = cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
_cascade = cv2.CascadeClassifier(CASCADE_PATH)


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


def detect_face_roi(gray):
    """
    Detecta el rostro mas prominente con Haar Cascade.
    Retorna la region recortada (ROI) redimensionada a 200x200, o None si no detecta.
    Intenta con parametros normales, luego permisivos, luego imagen escalada x2.
    """
    for (scale, neighbors, min_size) in [
        (1.10, 5, (50, 50)),
        (1.05, 3, (35, 35)),
    ]:
        faces = _cascade.detectMultiScale(
            gray, scaleFactor=scale, minNeighbors=neighbors,
            minSize=min_size, flags=cv2.CASCADE_SCALE_IMAGE
        )
        if len(faces) > 0:
            x, y, w, h = faces[0]
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
        x, y, w, h = faces[0]
        # Coordenadas originales (dividir por factor de escala 2)
        roi = gray[y // 2:(y + h) // 2, x // 2:(x + w) // 2]
        if roi.size > 0:
            return cv2.resize(roi, (200, 200))

    return None


def extract_user_id(filename):
    """Extrae user_id entero del nombre face_<id>.jpg"""
    m = re.search(r'face_(\d+)', filename, re.IGNORECASE)
    return int(m.group(1)) if m else None


# ===== CARGA DE ENTRENAMIENTO =====

def load_training_data():
    """
    Carga todos los archivos face_<id>.jpg para entrenamiento LBPH.
    Busca primero en uploads/rostros/, luego en uploads/capturas_cam/
    para soportar ambas ubicaciones de archivos registrados.
    """
    faces, labels = [], []

    # Buscar en rostros/ y en capturas_cam/ (fallback si rostros/ esta vacio)
    search_dirs = [ROSTROS_PATH, CAPTURAS_PATH]

    found_dirs = [d for d in search_dirs if os.path.isdir(d)]
    if not found_dirs:
        return faces, labels

    for search_dir in found_dirs:
        for filename in sorted(os.listdir(search_dir)):
            if not filename.lower().endswith(".jpg"):
                continue
            user_id = extract_user_id(filename)
            if user_id is None:
                continue  # Ignorar capturas con timestamp, solo face_<id>.jpg

            img = cv2.imread(os.path.join(search_dir, filename))
            if img is None:
                continue

            gray = preprocess_to_gray(img)
            roi = detect_face_roi(gray)

            if roi is None:
                roi = cv2.resize(gray, (200, 200))

            faces.append(roi)
            labels.append(user_id)

        # Si ya encontramos rostros en el primer directorio, no seguir al siguiente
        if len(faces) > 0:
            break

    return faces, labels


# ===== MAIN =====

def main():
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

    # --- Cargar datos de entrenamiento ---
    train_faces, train_labels = load_training_data()

    if len(train_faces) == 0:
        print(json.dumps({
            "success": False,
            "message": "No registered faces found in " + ROSTROS_PATH
        }))
        sys.exit(0)

    # --- Entrenar reconocedor LBPH ---
    recognizer = cv2.face.LBPHFaceRecognizer_create(
        radius=2, neighbors=16, grid_x=8, grid_y=8
    )
    recognizer.train(train_faces, np.array(train_labels, dtype=np.int32))

    # --- Predecir ---
    label, confidence = recognizer.predict(roi_input)

    # confidence en LBPH: 0 = perfecto, >100 = muy diferente
    # probability aproximada: 1 - (confidence/200), minimo 0
    probability = round(max(0.0, 1.0 - confidence / 200.0), 4)

    if confidence <= MATCH_THRESHOLD:
        print(json.dumps({
            "success": True,
            "user_id": int(label),
            "confidence": round(float(confidence), 2),
            "probability": probability
        }))
    else:
        print(json.dumps({
            "success": False,
            "message": "Face not recognized",
            "user_id": 0,
            "confidence": round(float(confidence), 2),
            "probability": probability
        }))


if __name__ == "__main__":
    main()
