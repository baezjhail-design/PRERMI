"""
face_verify.py - Verificacion facial tolerante para ESP32-S3 CAM
Usa OpenCV + dlib directamente (sin face_recognition) para manejar
imagenes de baja calidad, poca iluminacion y resolucion reducida.
"""
import sys
import os
import json
import cv2
import dlib
import numpy as np

# ===== RUTAS =====
BASE_PATH = r"D:\xampp\htdocs\PRERMI"
ROSTROS_PATH = os.path.join(BASE_PATH, "uploads", "rostros")
MODELS_PATH = r"C:\Users\Jhail Baez\AppData\Local\Programs\Python\Python312\Lib\site-packages\face_recognition_models\models"

SHAPE_PREDICTOR_PATH = os.path.join(MODELS_PATH, "shape_predictor_68_face_landmarks.dat")
FACE_REC_MODEL_PATH = os.path.join(MODELS_PATH, "dlib_face_recognition_resnet_model_v1.dat")

# ===== TOLERANCIA =====
# 0.55 = equilibrio entre seguridad y tolerancia para ESP32 CAM con low-light.
# Matches reales tipicamente tienen distancia 0.25-0.50, falsos positivos > 0.60.
MATCH_TOLERANCE = 0.55

# ===== INICIALIZAR MODELOS DLIB =====
hog_detector = dlib.get_frontal_face_detector()
shape_predictor = dlib.shape_predictor(SHAPE_PREDICTOR_PATH)
face_rec_model = dlib.face_recognition_model_v1(FACE_REC_MODEL_PATH)

# Haar Cascade de OpenCV como detector primario (mas tolerante)
CASCADE_PATH = cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
haar_cascade = cv2.CascadeClassifier(CASCADE_PATH)


def adjust_gamma(image, gamma=0.4):
    """Correccion gamma para aclarar imagenes muy oscuras. gamma < 1 = mas brillante."""
    table = np.array([(i / 255.0) ** gamma * 255 for i in range(256)]).astype("uint8")
    return cv2.LUT(image, table)


def preprocess_image(img):
    """Mejora calidad de imagen para camaras ESP32 de baja resolucion y poca luz."""
    if img is None:
        return None

    # Detectar si la imagen es muy oscura (promedio de brillo bajo)
    gray_check = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    mean_brightness = np.mean(gray_check)

    # Si la imagen es oscura (brillo promedio < 80), aplicar correccion gamma agresiva
    if mean_brightness < 80:
        gamma_value = 0.3 if mean_brightness < 40 else 0.5
        img = adjust_gamma(img, gamma=gamma_value)

    # Convertir a gris para deteccion
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    # Reducir ruido preservando bordes
    denoised = cv2.fastNlMeansDenoising(gray, None, h=15, templateWindowSize=7, searchWindowSize=21)

    # CLAHE agresivo para rescatar detalles en sombras
    clahe = cv2.createCLAHE(clipLimit=4.0, tileGridSize=(8, 8))
    enhanced = clahe.apply(denoised)

    # Reconstruir imagen color mejorada
    lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)
    l, a, b = cv2.split(lab)
    l_enhanced = clahe.apply(l)
    enhanced_color = cv2.merge([l_enhanced, a, b])
    enhanced_color = cv2.cvtColor(enhanced_color, cv2.COLOR_LAB2BGR)

    # Denoising en color tambien para reducir ruido de alta ganancia
    enhanced_color = cv2.fastNlMeansDenoisingColored(enhanced_color, None, 10, 10, 7, 21)

    return enhanced_color, enhanced


def detect_faces_multi(img, gray):
    """Detecta rostros con multiples metodos. Prioriza HOG (mas preciso) sobre Haar."""
    faces = []

    # 1) HOG detector de dlib: deteccion mas precisa de rostros reales
    hog_faces = hog_detector(img, 1)
    if len(hog_faces) > 0:
        return list(hog_faces), "hog"

    # 2) Haar Cascade: mas tolerante, pero validamos con landmarks de dlib
    haar_faces = haar_cascade.detectMultiScale(
        gray,
        scaleFactor=1.05,
        minNeighbors=4,
        minSize=(40, 40),
        flags=cv2.CASCADE_SCALE_IMAGE
    )
    for (x, y, w, h) in haar_faces:
        rect = dlib.rectangle(x, y, x + w, y + h)
        # Validar que dlib puede encontrar landmarks (confirma que es un rostro real)
        shape = shape_predictor(img, rect)
        # Verificar que los landmarks no estan todos agrupados (rostro falso)
        coords = np.array([[shape.part(i).x, shape.part(i).y] for i in range(68)])
        spread = np.std(coords, axis=0).mean()
        if spread > 5:  # landmarks distribuidos = rostro valido
            faces.append(rect)

    if len(faces) > 0:
        return faces, "haar"

    # 3) Escalar imagen x2 y reintentar HOG (para rostros muy pequenos)
    scaled = cv2.resize(img, None, fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC)

    hog_faces_scaled = hog_detector(scaled, 1)
    for f in hog_faces_scaled:
        faces.append(dlib.rectangle(
            f.left() // 2, f.top() // 2,
            f.right() // 2, f.bottom() // 2
        ))

    if len(faces) > 0:
        return faces, "hog_scaled"

    # 4) Ultimo recurso: Haar escalado con validacion
    scaled_gray = cv2.resize(gray, None, fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC)
    haar_faces_scaled = haar_cascade.detectMultiScale(
        scaled_gray,
        scaleFactor=1.05,
        minNeighbors=4,
        minSize=(50, 50),
        flags=cv2.CASCADE_SCALE_IMAGE
    )
    for (x, y, w, h) in haar_faces_scaled:
        rect_orig = dlib.rectangle(x // 2, y // 2, (x + w) // 2, (y + h) // 2)
        faces.append(rect_orig)

    if len(faces) > 0:
        return faces, "haar_scaled"

    return [], "none"


def get_face_encoding(img, face_rect, num_jitters=3):
    """Obtiene el encoding de 128 dimensiones de un rostro detectado."""
    shape = shape_predictor(img, face_rect)
    encoding = face_rec_model.compute_face_descriptor(img, shape, num_jitters=num_jitters)
    return np.array(encoding)


def face_distance(known_encoding, unknown_encoding):
    """Calcula distancia euclidiana entre dos encodings."""
    return np.linalg.norm(known_encoding - unknown_encoding)


def load_registered_faces():
    """Carga todos los rostros registrados y sus encodings."""
    known_encodings = []
    known_ids = []

    if not os.path.isdir(ROSTROS_PATH):
        return known_encodings, known_ids

    for filename in os.listdir(ROSTROS_PATH):
        if not filename.lower().endswith(".jpg"):
            continue

        filepath = os.path.join(ROSTROS_PATH, filename)

        try:
            img = cv2.imread(filepath)
            if img is None:
                continue

            enhanced_color, enhanced_gray = preprocess_image(img)
            faces, method = detect_faces_multi(enhanced_color, enhanced_gray)

            if len(faces) == 0:
                # Intentar con imagen original sin preprocesar
                gray_orig = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
                faces, method = detect_faces_multi(img, gray_orig)

            if len(faces) > 0:
                encoding = get_face_encoding(enhanced_color, faces[0], num_jitters=5)
                known_encodings.append(encoding)

                # Extraer user_id: formato face_{user_id}.jpg
                user_id = int(filename.split("_")[1].split(".")[0])
                known_ids.append(user_id)
        except Exception:
            continue

    return known_encodings, known_ids


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "No image path"}))
        return

    input_path = sys.argv[1]

    if not os.path.exists(input_path):
        print(json.dumps({"success": False, "error": "Image not found"}))
        return

    # Cargar y preprocesar imagen del ESP32
    img = cv2.imread(input_path)
    if img is None:
        print(json.dumps({"success": False, "error": "Cannot read image"}))
        return

    enhanced_color, enhanced_gray = preprocess_image(img)

    # Detectar rostro con multiples metodos
    faces, detection_method = detect_faces_multi(enhanced_color, enhanced_gray)

    if len(faces) == 0:
        # Ultimo intento: imagen original sin mejoras
        gray_orig = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        faces, detection_method = detect_faces_multi(img, gray_orig)

    if len(faces) == 0:
        print(json.dumps({"success": False, "error": "No face detected", "method": "none"}))
        return

    # Obtener encoding del rostro detectado (3 jitters: balance velocidad/precision)
    input_encoding = get_face_encoding(enhanced_color, faces[0], num_jitters=3)

    # Cargar rostros registrados
    known_encodings, known_ids = load_registered_faces()

    if len(known_encodings) == 0:
        print(json.dumps({"success": False, "error": "No registered faces"}))
        return

    # Comparar contra todos los registrados
    best_match_index = None
    best_distance = 1.0

    for i, known_enc in enumerate(known_encodings):
        dist = face_distance(known_enc, input_encoding)
        if dist < MATCH_TOLERANCE and dist < best_distance:
            best_distance = dist
            best_match_index = i

    if best_match_index is not None:
        user_id = known_ids[best_match_index]
        probability = float(1 - best_distance)

        print(json.dumps({
            "success": True,
            "user_id": user_id,
            "probability": round(probability, 4),
            "distance": round(best_distance, 4),
            "detection_method": detection_method
        }))
    else:
        print(json.dumps({
            "success": False,
            "error": "No match",
            "detection_method": detection_method,
            "best_distance": round(best_distance, 4) if best_distance < 1.0 else None
        }))


if __name__ == "__main__":
    main()