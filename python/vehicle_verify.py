#!/usr/bin/env python3
"""
vehicle_verify.py
Validador inicial de deteccion vehicular para PRERMI.

Entrada:
    python vehicle_verify.py /ruta/a/imagen.jpg

Salida:
    Un JSON en stdout con:
    {
      "success": true,
      "categoria": "accidente|camion_recolector|vehiculo_empresa|sin_match",
      "confianza": 0.0-1.0,
      "match_id": int|null,
      "detalle": "..."
    }

Version v1:
- Accidente: heuristica simple basada en densidad de bordes.
- Vehiculo empresa/camion: matching ORB contra catalogo activo en DB.
"""

import json
import os
import sys
from typing import Any, Dict, List, Optional, Tuple

try:
    import cv2
except Exception:
    cv2 = None

try:
    import numpy as np
except Exception:
    np = None

try:
    import pymysql
except Exception:
    pymysql = None


def load_db_config() -> Dict[str, str]:
    base_path = os.getenv("PRERMI_BASE_PATH", "/var/www/html/PRERMI")
    cfg_path = os.path.join(base_path, "config", "db_config.php")

    cfg = {
        "host": "127.0.0.1",
        "name": "",
        "user": "",
        "pass": "",
    }

    if not os.path.isfile(cfg_path):
        return cfg

    try:
        with open(cfg_path, "r", encoding="utf-8", errors="ignore") as f:
            text = f.read()

        # Parser simple para variables PHP tipo: $DB_HOST = 'localhost';
        for key_php, key_out in [
            ("DB_HOST", "host"),
            ("DB_NAME", "name"),
            ("DB_USER", "user"),
            ("DB_PASS", "pass"),
        ]:
            marker = "$" + key_php
            idx = text.find(marker)
            if idx == -1:
                continue
            seg = text[idx: idx + 200]
            q1 = seg.find("'")
            if q1 == -1:
                continue
            q2 = seg.find("'", q1 + 1)
            if q2 == -1:
                continue
            cfg[key_out] = seg[q1 + 1:q2]
    except Exception:
        pass

    return cfg


def connect_db(cfg: Dict[str, str]):
    if pymysql is None:
        return None
    if not cfg.get("name") or not cfg.get("user"):
        return None

    try:
        return pymysql.connect(
            host=cfg.get("host", "127.0.0.1"),
            user=cfg["user"],
            password=cfg.get("pass", ""),
            database=cfg["name"],
            charset="utf8mb4",
            cursorclass=pymysql.cursors.DictCursor,
            connect_timeout=4,
        )
    except Exception:
        return None


def load_catalog(conn) -> List[Dict[str, Any]]:
    if conn is None:
        return []

    sql = (
        "SELECT id, tipo_vehiculo, etiqueta, marca, modelo, anio, color, placa_referencia, bbox_json, ruta_archivo "
        "FROM vehiculos_catalogo "
        "WHERE estado = 'activo' AND tipo_vehiculo IN ('vehiculo_empresa', 'camion_recolector')"
    )

    try:
        with conn.cursor() as cur:
            cur.execute(sql)
            return cur.fetchall()
    except Exception:
        return []


def accident_score(image_bgr) -> float:
    """Heuristica inicial: densidad de bordes + variacion local."""
    if cv2 is None or np is None:
        return 0.0

    gray = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2GRAY)
    edges = cv2.Canny(gray, 80, 180)
    edge_density = float(np.mean(edges > 0))

    lap_var = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    lap_norm = min(1.0, lap_var / 1200.0)

    score = 0.65 * edge_density + 0.35 * lap_norm
    return max(0.0, min(1.0, score))


def normalize_size(image_bgr, max_side: int = 1280):
    h, w = image_bgr.shape[:2]
    longest = max(h, w)
    if longest <= max_side:
        return image_bgr
    ratio = float(max_side) / float(longest)
    new_w = max(1, int(w * ratio))
    new_h = max(1, int(h * ratio))
    return cv2.resize(image_bgr, (new_w, new_h), interpolation=cv2.INTER_AREA)


def enhance_low_light(image_bgr):
    lab = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2LAB)
    l, a, b = cv2.split(lab)
    clahe = cv2.createCLAHE(clipLimit=2.8, tileGridSize=(8, 8))
    l2 = clahe.apply(l)
    merged = cv2.merge((l2, a, b))
    enhanced = cv2.cvtColor(merged, cv2.COLOR_LAB2BGR)
    return cv2.fastNlMeansDenoisingColored(enhanced, None, 4, 4, 7, 21)


def sharpen(image_bgr):
    kernel = np.array([[0, -1, 0], [-1, 5, -1], [0, -1, 0]], dtype=np.float32)
    return cv2.filter2D(image_bgr, -1, kernel)


def build_query_variants(image_bgr):
    base = normalize_size(image_bgr)
    h, w = base.shape[:2]
    min_side = min(h, w)
    if min_side < 420:
        # Upscale small scenes so distant/small vehicles produce more keypoints.
        up_scale = min(2.8, 420.0 / float(max(1, min_side)))
        up_w = max(1, int(round(w * up_scale)))
        up_h = max(1, int(round(h * up_scale)))
        base = cv2.resize(base, (up_w, up_h), interpolation=cv2.INTER_CUBIC)

    bright = enhance_low_light(base)
    sharp = sharpen(bright)

    variants = [
        ("orig", base),
        ("lowlight", bright),
        ("lowlight_sharp", sharp),
    ]

    rotated = []
    for tag, img in variants:
        rotated.append((tag + "_r90", cv2.rotate(img, cv2.ROTATE_90_CLOCKWISE)))
        rotated.append((tag + "_r270", cv2.rotate(img, cv2.ROTATE_90_COUNTERCLOCKWISE)))

    return variants + rotated


def build_query_regions(image_bgr):
    regions = [("full", image_bgr)]
    h, w = image_bgr.shape[:2]

    if h >= 80 and w >= 80:
        # Central crop keeps focus when object is near center.
        x1 = int(round(w * 0.1))
        y1 = int(round(h * 0.1))
        x2 = int(round(w * 0.9))
        y2 = int(round(h * 0.9))
        center = image_bgr[y1:y2, x1:x2]
        if center.size > 0:
            regions.append(("center", center))

        # Vehicles are commonly near the lower half in desk/floor camera setups.
        lower = image_bgr[int(round(h * 0.45)):h, 0:w]
        if lower.size > 0:
            regions.append(("lower", lower))

        # Quadrant crops to catch small vehicles near any corner
        for qtag, qy1, qy2, qx1, qx2 in [
            ("quad_tl", 0, h // 2, 0, w // 2),
            ("quad_tr", 0, h // 2, w // 2, w),
            ("quad_bl", h // 2, h, 0, w // 2),
            ("quad_br", h // 2, h, w // 2, w),
        ]:
            quad = image_bgr[qy1:qy2, qx1:qx2]
            if quad.size > 0:
                regions.append((qtag, quad))

    if cv2 is None or np is None:
        return regions

    # Add contour-based candidates to catch small vehicles in large scenes.
    gray = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2GRAY)
    gray = cv2.GaussianBlur(gray, (5, 5), 0)
    edges = cv2.Canny(gray, 35, 120)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (3, 3))
    edges = cv2.dilate(edges, kernel, iterations=1)

    contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    if not contours:
        return regions

    img_area = float(max(1, h * w))
    candidates = []
    for cnt in contours:
        x, y, bw, bh = cv2.boundingRect(cnt)
        area = float(max(1, bw * bh))
        area_ratio = area / img_area
        if area_ratio < 0.0005 or area_ratio > 0.45:
            continue

        ratio = float(bw) / float(max(1, bh))
        if ratio < 0.35 or ratio > 5.0:
            continue

        pad_x = int(round(bw * 0.18))
        pad_y = int(round(bh * 0.18))
        x1 = max(0, x - pad_x)
        y1 = max(0, y - pad_y)
        x2 = min(w, x + bw + pad_x)
        y2 = min(h, y + bh + pad_y)

        crop = image_bgr[y1:y2, x1:x2]
        if crop.size == 0:
            continue

        edge_density = float(np.mean(edges[y1:y2, x1:x2] > 0))
        score = (area_ratio * 0.65) + (edge_density * 0.35)
        candidates.append((score, crop))

    candidates.sort(key=lambda x: x[0], reverse=True)
    for idx, (_, crop) in enumerate(candidates[:4]):
        regions.append((f"cand{idx+1}", crop))

    return regions


def clamp(v: float, lo: float, hi: float) -> float:
    return max(lo, min(hi, v))


def crop_by_bbox_ratio(image_bgr, bbox: Dict[str, float]):
    h, w = image_bgr.shape[:2]
    if h <= 2 or w <= 2:
        return image_bgr

    x = clamp(float(bbox.get("x", 0.0)), 0.0, 1.0)
    y = clamp(float(bbox.get("y", 0.0)), 0.0, 1.0)
    bw = clamp(float(bbox.get("w", 1.0)), 0.01, 1.0)
    bh = clamp(float(bbox.get("h", 1.0)), 0.01, 1.0)

    x2 = clamp(x + bw, 0.01, 1.0)
    y2 = clamp(y + bh, 0.01, 1.0)

    px1 = int(round(x * w))
    py1 = int(round(y * h))
    px2 = int(round(x2 * w))
    py2 = int(round(y2 * h))

    px1 = max(0, min(px1, w - 1))
    py1 = max(0, min(py1, h - 1))
    px2 = max(px1 + 1, min(px2, w))
    py2 = max(py1 + 1, min(py2, h))

    return image_bgr[py1:py2, px1:px2]


def detect_vehicle_roi_auto(image_bgr) -> Tuple[Optional[Dict[str, float]], Optional[Any]]:
    if cv2 is None or np is None:
        return None, None

    h, w = image_bgr.shape[:2]
    if h < 40 or w < 40:
        return None, None

    gray = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2GRAY)
    gray = cv2.GaussianBlur(gray, (5, 5), 0)
    edges = cv2.Canny(gray, 50, 150)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (5, 5))
    edges = cv2.dilate(edges, kernel, iterations=1)
    edges = cv2.morphologyEx(edges, cv2.MORPH_CLOSE, kernel, iterations=2)

    contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    if not contours:
        return None, None

    center_x = w / 2.0
    center_y = h / 2.0
    img_area = float(w * h)
    best = None
    best_score = 0.0

    for cnt in contours:
        x, y, bw, bh = cv2.boundingRect(cnt)
        area = float(bw * bh)
        if area < img_area * 0.003:
            continue

        ratio = float(bw) / float(max(1, bh))
        if ratio < 0.5 or ratio > 4.2:
            continue

        c_x = x + (bw / 2.0)
        c_y = y + (bh / 2.0)
        dist = ((c_x - center_x) ** 2 + (c_y - center_y) ** 2) ** 0.5
        max_dist = ((center_x ** 2 + center_y ** 2) ** 0.5)
        center_bonus = 1.0 - min(1.0, dist / max_dist)
        area_ratio = area / img_area
        score = (area_ratio * 0.75) + (center_bonus * 0.25)

        if score > best_score:
            best_score = score
            best = (x, y, bw, bh)

    if best is None:
        return None, None

    x, y, bw, bh = best
    pad_x = int(bw * 0.08)
    pad_y = int(bh * 0.08)
    x1 = max(0, x - pad_x)
    y1 = max(0, y - pad_y)
    x2 = min(w, x + bw + pad_x)
    y2 = min(h, y + bh + pad_y)
    roi = image_bgr[y1:y2, x1:x2]

    bbox = {
        "x": round(float(x1) / float(w), 4),
        "y": round(float(y1) / float(h), 4),
        "w": round(float(x2 - x1) / float(w), 4),
        "h": round(float(y2 - y1) / float(h), 4),
    }
    return bbox, roi


def orb_descriptors(image_bgr):
    if cv2 is None:
        return None
    gray = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2GRAY)
    orb = cv2.ORB_create(
        nfeatures=1400,
        scaleFactor=1.2,
        nlevels=8,
        edgeThreshold=15,
        fastThreshold=10,
    )
    _, des = orb.detectAndCompute(gray, None)
    return des


def akaze_descriptors(image_bgr):
    if cv2 is None:
        return None
    gray = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2GRAY)
    akaze = cv2.AKAZE_create()
    _, des = akaze.detectAndCompute(gray, None)
    return des


def descriptor_sets(image_bgr):
    sets = []
    orb = orb_descriptors(image_bgr)
    if orb is not None and len(orb) >= 8:
        sets.append(("orb", orb, cv2.NORM_HAMMING))

    akz = akaze_descriptors(image_bgr)
    if akz is not None and len(akz) >= 8:
        sets.append(("akaze", akz, cv2.NORM_HAMMING))

    return sets


def color_hist_score(img_a, img_b) -> float:
    if cv2 is None:
        return 0.0

    try:
        hsv_a = cv2.cvtColor(img_a, cv2.COLOR_BGR2HSV)
        hsv_b = cv2.cvtColor(img_b, cv2.COLOR_BGR2HSV)
    except Exception:
        return 0.0

    hist_a = cv2.calcHist([hsv_a], [0, 1], None, [30, 32], [0, 180, 0, 256])
    hist_b = cv2.calcHist([hsv_b], [0, 1], None, [30, 32], [0, 180, 0, 256])
    cv2.normalize(hist_a, hist_a, 0, 1, cv2.NORM_MINMAX)
    cv2.normalize(hist_b, hist_b, 0, 1, cv2.NORM_MINMAX)

    # Bhattacharyya: 0=igual, 1=diferente
    dist = float(cv2.compareHist(hist_a, hist_b, cv2.HISTCMP_BHATTACHARYYA))
    return clamp(1.0 - dist, 0.0, 1.0)


def template_match_multiscale(scene_bgr, template_bgr) -> float:
    if cv2 is None:
        return 0.0

    try:
        scene_gray = cv2.cvtColor(scene_bgr, cv2.COLOR_BGR2GRAY)
        tpl_gray = cv2.cvtColor(template_bgr, cv2.COLOR_BGR2GRAY)
    except Exception:
        return 0.0

    if scene_gray.size == 0 or tpl_gray.size == 0:
        return 0.0

    scene_edges = cv2.Canny(scene_gray, 55, 170)
    tpl_edges = cv2.Canny(tpl_gray, 55, 170)
    h_s, w_s = scene_edges.shape[:2]
    h_t, w_t = tpl_edges.shape[:2]

    if h_s < 20 or w_s < 20 or h_t < 12 or w_t < 12:
        return 0.0

    scales = [0.04, 0.06, 0.08, 0.10, 0.13, 0.16, 0.20, 0.25, 0.32, 0.40, 0.50, 0.63, 0.80, 1.00]
    best = 0.0

    scene_blur = cv2.GaussianBlur(scene_gray, (5, 5), 0)
    tpl_blur = cv2.GaussianBlur(tpl_gray, (5, 5), 0)

    for s in scales:
        nw = max(10, int(round(w_t * s)))
        nh = max(10, int(round(h_t * s)))
        if nw >= w_s or nh >= h_s:
            continue

        tpl_resized = cv2.resize(tpl_edges, (nw, nh), interpolation=cv2.INTER_AREA)
        tpl_resized_blur = cv2.resize(tpl_blur, (nw, nh), interpolation=cv2.INTER_AREA)
        try:
            res_edges = cv2.matchTemplate(scene_edges, tpl_resized, cv2.TM_CCOEFF_NORMED)
            _, max_edges, _, _ = cv2.minMaxLoc(res_edges)

            res_blur = cv2.matchTemplate(scene_blur, tpl_resized_blur, cv2.TM_CCOEFF_NORMED)
            _, max_blur, _, _ = cv2.minMaxLoc(res_blur)

            score = (float(max_edges) * 0.62) + (float(max_blur) * 0.38)
            if score > best:
                best = score
        except Exception:
            continue

    return clamp(best, 0.0, 1.0)


def dominant_color_score(img_a, img_b) -> float:
    """Compare dominant HSV hue — most effective for solid-color toy vehicles (Hot Wheels, LEGO, RC)."""
    if cv2 is None or np is None:
        return 0.0

    try:
        def get_dominant_hue(img):
            h, w = img.shape[:2]
            if h > 200 or w > 200:
                scale = min(200.0 / h, 200.0 / w)
                img_s = cv2.resize(img, (max(1, int(w * scale)), max(1, int(h * scale))), interpolation=cv2.INTER_AREA)
            else:
                img_s = img
            hsv = cv2.cvtColor(img_s, cv2.COLOR_BGR2HSV)
            s_ch = hsv[:, :, 1].astype(np.float32)
            v_ch = hsv[:, :, 2].astype(np.float32)
            mask = (s_ch > 70) & (v_ch > 50)
            coverage = float(mask.sum()) / float(max(1, mask.size))
            if coverage < 0.04 or int(mask.sum()) < 8:
                return None, 0.0
            hues = hsv[:, :, 0][mask].astype(np.float32)
            # Circular mean for hue (0-180 range → double angles trick)
            angles = np.deg2rad(hues * 2.0)
            mean_h = float(np.rad2deg(np.arctan2(float(np.mean(np.sin(angles))),
                                                  float(np.mean(np.cos(angles)))) / 2.0)) % 180.0
            return mean_h, coverage

        h_a, cov_a = get_dominant_hue(img_a)
        h_b, cov_b = get_dominant_hue(img_b)
        if h_a is None or h_b is None:
            return 0.0

        # Circular hue distance (0-90°)
        diff = abs(h_a - h_b)
        if diff > 90.0:
            diff = 180.0 - diff
        # Within 22° is a solid match for a solid-color toy
        hue_sim = max(0.0, 1.0 - (diff / 22.0))
        # Require at least some color coverage in both images
        cov_factor = min(1.0, min(cov_a, cov_b) / 0.05)
        return float(hue_sim * cov_factor)
    except Exception:
        return 0.0


def resolve_absolute_image_path(ruta_archivo: str) -> Optional[str]:
    base_path = os.getenv("PRERMI_BASE_PATH", "/var/www/html/PRERMI")
    if not ruta_archivo:
        return None

    cleaned = ruta_archivo.strip()
    if cleaned.startswith("/PRERMI/"):
        cleaned = cleaned[len("/PRERMI/"):]
    cleaned = cleaned.lstrip("/")

    abs_path = os.path.join(base_path, cleaned)
    if os.path.isfile(abs_path):
        return abs_path
    return None


def match_against_catalog(query_img, catalog_rows: List[Dict[str, Any]]) -> Tuple[Optional[Dict[str, Any]], float, str]:
    if cv2 is None:
        return None, 0.0, "cv2_not_available"

    query_regions = build_query_regions(query_img)
    query_descs = []
    for rtag, region_img in query_regions:
        query_variants = build_query_variants(region_img)
        for vtag, qimg in query_variants:
            for dname, dset, dnorm in descriptor_sets(qimg):
                query_descs.append((rtag + ":" + vtag + ":" + dname, dset, dnorm, qimg))

    if not query_descs:
        return None, 0.0, "sin_descriptores_query"

    best_item = None
    best_score = 0.0
    best_variant = ""

    for row in catalog_rows:
        img_path = resolve_absolute_image_path(row.get("ruta_archivo", ""))
        if not img_path:
            continue

        cat_img = cv2.imread(img_path)
        if cat_img is None:
            continue

        cat_bbox_raw = row.get("bbox_json")
        if cat_bbox_raw:
            try:
                cat_bbox = cat_bbox_raw if isinstance(cat_bbox_raw, dict) else json.loads(str(cat_bbox_raw))
                if isinstance(cat_bbox, dict):
                    cat_img = crop_by_bbox_ratio(cat_img, cat_bbox)
            except Exception:
                pass

        cat_img = normalize_size(cat_img)
        cat_sets = descriptor_sets(cat_img)
        feat_best = 0.0
        feat_best_variant = ""

        if cat_sets:
            for qtag, query_des, query_norm, _qimg in query_descs:
                dname = qtag.split(":")[-1]
                cat_des = None
                cat_norm = None
                for cname, cset, cnorm in cat_sets:
                    if cname == dname:
                        cat_des = cset
                        cat_norm = cnorm
                        break

                if cat_des is None or cat_norm is None or query_norm != cat_norm:
                    continue

                bf = cv2.BFMatcher(cat_norm, crossCheck=False)
                try:
                    knn = bf.knnMatch(query_des, cat_des, k=2)
                except Exception:
                    continue

                good = []
                for pair in knn:
                    if len(pair) < 2:
                        continue
                    m, n = pair
                    if m.distance < 0.80 * n.distance:
                        good.append(m)

                if len(good) < 6:
                    continue

                denom = float(max(1, min(len(query_des), len(cat_des))))
                ratio = float(len(good)) / denom
                avg_dist = sum(m.distance for m in good) / float(len(good))
                dist_score = 1.0 - min(1.0, avg_dist / 90.0)
                score = (ratio * 0.72) + (dist_score * 0.28)

                if score > feat_best:
                    feat_best = score
                    feat_best_variant = qtag

        tpl_raw_best = 0.0
        tpl_variant = ""
        for rtag, region_img in query_regions[:9]:
            tpl_raw = template_match_multiscale(region_img, cat_img)
            if tpl_raw > tpl_raw_best:
                tpl_raw_best = tpl_raw
                tpl_variant = rtag

        hist_best = 0.0
        hist_variant = ""
        for rtag, region_img in query_regions[:10]:
            hscore = color_hist_score(region_img, cat_img)
            if hscore > hist_best:
                hist_best = hscore
                hist_variant = rtag

        # Dominant color — especially effective for solid-color toy vehicles
        dom_color_best = 0.0
        dom_color_variant = ""
        for rtag, region_img in query_regions[:10]:
            ds = dominant_color_score(region_img, cat_img)
            if ds > dom_color_best:
                dom_color_best = ds
                dom_color_variant = rtag

        # Convert raw scores to calibrated 0..1 confidence.
        # Lower baselines improve sensitivity for small/distant toy vehicles.
        tpl_score = clamp((tpl_raw_best - 0.18) / 0.54, 0.0, 1.0)
        hist_score = clamp((hist_best - 0.20) / 0.55, 0.0, 1.0)
        dom_score = clamp(dom_color_best, 0.0, 1.0)

        combined = max(feat_best, tpl_score * 0.58, hist_score * 0.35, dom_score * 0.45)

        if combined == feat_best and feat_best > 0:
            variant = "feat:" + feat_best_variant
        elif combined == (tpl_score * 0.58) and tpl_score > 0:
            variant = "template:" + tpl_variant
        elif combined == (dom_score * 0.45) and dom_score > 0:
            variant = "domcolor:" + dom_color_variant
        else:
            variant = "hist:" + hist_variant

        if combined > best_score:
            best_score = combined
            best_item = row
            best_variant = variant

    return best_item, best_score, best_variant


def main() -> None:
    if len(sys.argv) < 2:
        print(json.dumps({
            "success": False,
            "error": "usage: vehicle_verify.py /path/to/image.jpg"
        }))
        return

    image_path = sys.argv[1]
    if cv2 is None or np is None:
        print(json.dumps({
            "success": False,
            "error": "opencv/numpy no disponibles"
        }))
        return

    img = cv2.imread(image_path)
    if img is None:
        print(json.dumps({
            "success": False,
            "error": "imagen invalida"
        }))
        return

    # 1) Accidente binario
    acc_score = accident_score(img)
    if acc_score >= 0.72:
        print(json.dumps({
            "success": True,
            "categoria": "accidente",
            "confianza": round(acc_score, 4),
            "match_id": None,
            "detalle": "detector_accidente_v1"
        }, ensure_ascii=False))
        return

    # 2) Matching por catalogo de vehiculo empresa/camion
    cfg = load_db_config()
    conn = connect_db(cfg)
    try:
        catalog = load_catalog(conn)
    finally:
        try:
            if conn:
                conn.close()
        except Exception:
            pass

    auto_bbox, auto_roi = detect_vehicle_roi_auto(img)

    candidates = [("full", img)]
    if auto_roi is not None:
        candidates.append(("auto_roi", auto_roi))

    best_item = None
    best_score = 0.0
    best_variant = ""
    best_source = "full"

    for source_tag, query_input in candidates:
        item, score, variant = match_against_catalog(query_input, catalog)
        if score > best_score:
            best_item = item
            best_score = score
            best_variant = variant
            best_source = source_tag

    if best_item and best_score >= 0.07:
        print(json.dumps({
            "success": True,
            "categoria": best_item.get("tipo_vehiculo", "vehiculo_empresa"),
            "confianza": round(float(best_score), 4),
            "match_id": int(best_item.get("id", 0)) if best_item.get("id") is not None else None,
            "detalle": best_item.get("etiqueta", "match_catalogo"),
            "variant": best_variant,
            "query_source": best_source,
            "focus_box": auto_bbox,
            "marca": best_item.get("marca"),
            "modelo": best_item.get("modelo"),
            "anio": best_item.get("anio"),
            "color": best_item.get("color"),
            "placa_referencia": best_item.get("placa_referencia"),
        }, ensure_ascii=False))
        return

    print(json.dumps({
        "success": True,
        "categoria": "sin_match",
        "confianza": round(max(best_score, acc_score), 4),
        "match_id": None,
        "detalle": "sin coincidencias confiables",
        "focus_box": auto_bbox,
    }, ensure_ascii=False))


if __name__ == "__main__":
    try:
        main()
    except Exception as ex:
        print(json.dumps({
            "success": False,
            "error": "exception",
            "detail": str(ex)
        }, ensure_ascii=False))
