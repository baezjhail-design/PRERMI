<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Programa de Reabastecimiento Energético, Residuos y Monitoreo Inteligente (PRERMI). Solución industrial para convertir residuos en energía con trazabilidad y analítica en tiempo real.">
    <title>PRERMI | Feria Proindustria 2026</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-deep: #f6fbff;
            --bg-mid: #e7f7ff;
            --panel: #ffffff;
            --panel-soft: rgba(255, 255, 255, 0.86);
            --line: #b9d9ee;
            --txt: #13263f;
            --muted: #4d6e8f;
            --blue: #38bdf8;
            --cyan: #06b6d4;
            --purple: #7c3aed;
            --green: #22c55e;
            --grad-main: linear-gradient(130deg, #38bdf8 0%, #06b6d4 44%, #7c3aed 100%);
            --grad-soft: linear-gradient(130deg, rgba(56, 189, 248, 0.2) 0%, rgba(6, 182, 212, 0.17) 50%, rgba(124, 58, 237, 0.12) 100%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--txt);
            font-family: Manrope, sans-serif;
            background:
                radial-gradient(1200px 560px at 92% -10%, rgba(56, 189, 248, 0.22), transparent 60%),
                radial-gradient(900px 540px at -8% 14%, rgba(124, 58, 237, 0.13), transparent 56%),
                linear-gradient(180deg, var(--bg-deep) 0%, #f0faff 100%);
            overflow-x: hidden;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            opacity: 0.2;
            background-image:
                linear-gradient(to right, rgba(7, 94, 166, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(7, 94, 166, 0.08) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(circle at center, #000 34%, transparent 92%);
        }

        .navbar-prermi {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 50;
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.86);
            border-bottom: 1px solid rgba(56, 189, 248, 0.35);
        }

        .brand-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: #0f2440;
            text-decoration: none;
        }

        .brand-wrap img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(56, 189, 248, 0.25);
        }

        .contact-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .contact-mini-logo {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            border: 1px solid rgba(27, 93, 146, 0.35);
            box-shadow: 0 6px 14px rgba(12, 106, 216, 0.2);
            object-fit: cover;
        }

        .brand-name {
            font-family: Sora, sans-serif;
            font-weight: 700;
            letter-spacing: 0.4px;
            font-size: 1.05rem;
            line-height: 1.05;
        }

        .brand-sub {
            display: block;
            color: #4b6f95;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .nav-link {
            color: #365f85 !important;
            font-weight: 700;
        }

        .nav-link:hover {
            color: #7c3aed !important;
        }

        .btn-main,
        .btn-alt,
        .btn-outline-soft {
            border: 0;
            border-radius: 12px;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.72rem 1.05rem;
            transition: transform 0.24s ease, box-shadow 0.24s ease;
        }

        .btn-main {
            background: var(--grad-main);
            color: #fff;
        }

        .btn-main:hover {
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 14px 28px rgba(56, 189, 248, 0.25);
        }

        .btn-alt {
            background: linear-gradient(130deg, #22c55e 0%, #06b6d4 100%);
            color: #fff;
        }

        .btn-alt:hover {
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 14px 28px rgba(34, 197, 94, 0.24);
        }

        .btn-outline-soft {
            border: 1px solid #3e6491;
            color: #153457;
            background: rgba(255, 255, 255, 0.72);
        }

        .btn-outline-soft:hover {
            color: #fff;
            transform: translateY(-2px);
            border-color: #5aa4de;
        }

        .hero {
            position: relative;
            z-index: 1;
            padding: 7.4rem 0 3.6rem;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.46rem 0.78rem;
            border-radius: 999px;
            border: 1px solid #9dc9e6;
            background: rgba(255, 255, 255, 0.82);
            font-weight: 800;
            font-size: 0.84rem;
        }

        .hero-title {
            margin-top: 1.05rem;
            font-family: Sora, sans-serif;
            font-size: clamp(2.05rem, 5.1vw, 3.65rem);
            line-height: 1.06;
            max-width: 880px;
            letter-spacing: -0.02em;
        }

        .hero-lead {
            margin-top: 1rem;
            color: var(--muted);
            max-width: 760px;
            font-size: 1.05rem;
        }

        .hero-actions {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }

        .hero-art {
            margin-top: 1.6rem;
            border-radius: 22px;
            border: 1px solid var(--line);
            background: var(--grad-soft);
            padding: 0.6rem;
            box-shadow: 0 26px 50px rgba(0, 0, 0, 0.32);
        }

        .hero-art img {
            width: 100%;
            border-radius: 16px;
            display: block;
        }

        .quick-stats {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .quick-card {
            background: var(--panel-soft);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 0.75rem;
            min-height: 96px;
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.08);
        }

        .quick-card .lbl {
            color: var(--muted);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        .quick-card .num {
            margin-top: 0.2rem;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .section {
            position: relative;
            z-index: 1;
            padding: 2.8rem 0;
        }

        .section-title {
            font-family: Sora, sans-serif;
            font-size: clamp(1.45rem, 4vw, 2.3rem);
            margin-bottom: 0.5rem;
        }

        .section-sub {
            color: var(--muted);
            max-width: 780px;
        }

        .about-grid {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 0.9rem;
        }

        .about-card {
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.84);
            padding: 1rem;
            height: 100%;
        }

        .about-card h3 {
            font-family: Sora, sans-serif;
            font-size: 1.08rem;
            margin-bottom: 0.7rem;
        }

        .about-card p,
        .about-card li {
            color: #214668;
            margin: 0;
        }

        .about-points {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 0.55rem;
        }

        .about-points li i {
            margin-right: 0.35rem;
            color: var(--cyan);
        }

        .access-grid {
            margin-top: 1.1rem;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .access-card {
            border-radius: 18px;
            padding: 1rem;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.82);
            height: 100%;
        }

        .access-actions {
            margin-top: 0.8rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .access-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-grid;
            place-items: center;
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
            color: #fff;
        }

        .access-admin .access-icon { background: linear-gradient(130deg, #7c3aed, #38bdf8); }
        .access-user .access-icon { background: linear-gradient(130deg, #22c55e, #06b6d4); }

        .metric-grid {
            margin-top: 1.2rem;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .metric-card {
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.85);
            padding: 0.95rem;
        }

        .metric-card h3 {
            margin: 0.7rem 0 0;
            font-size: 1.6rem;
            font-weight: 800;
        }

        .metric-card p {
            margin: 0;
            color: var(--muted);
            font-size: 0.85rem;
        }

        .icon-box {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-grid;
            place-items: center;
            background: rgba(56, 189, 248, 0.16);
            color: #1574b5;
        }

        .charts-grid {
            margin-top: 1rem;
        }

        .panel-card {
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.88);
            padding: 0.95rem;
            height: 100%;
        }

        .panel-card h3 {
            font-family: Sora, sans-serif;
            font-size: 1rem;
            margin-bottom: 0.8rem;
        }

        .canvas-wrap {
            min-height: 250px;
            position: relative;
        }

        .illustration-row {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .illustration-card {
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.85);
            padding: 0.6rem;
        }

        .illustration-card img {
            width: 100%;
            border-radius: 12px;
            display: block;
            object-fit: cover;
            height: 230px;
        }

        .real-photo-grid {
            margin-top: 1.1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.9rem;
        }

        .real-photo-card {
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.88);
            padding: 0.45rem;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .real-photo-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 12px;
            display: block;
        }

        .img-focus-top { object-position: center 18%; }
        .img-focus-center { object-position: center center; }
        .img-focus-bottom { object-position: center 78%; }
        .img-focus-left { object-position: 28% center; }
        .img-focus-right { object-position: 72% center; }

        .real-photo-grid.media-distributed {
            grid-template-columns: repeat(12, minmax(0, 1fr));
            grid-auto-rows: 120px;
            gap: 0.8rem;
        }

        .real-photo-grid.media-distributed .real-photo-card {
            height: 100%;
            padding: 0.4rem;
        }

        .real-photo-grid.media-distributed .real-photo-card img {
            height: 100%;
            min-height: 140px;
        }

        .real-photo-card.span-4 { grid-column: span 4; }
        .real-photo-card.span-5 { grid-column: span 5; }
        .real-photo-card.span-6 { grid-column: span 6; }
        .real-photo-card.span-7 { grid-column: span 7; }
        .real-photo-card.span-8 { grid-column: span 8; }
        .real-photo-card.row-2 { grid-row: span 2; }

        .contact-card {
            margin-top: 1rem;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: linear-gradient(130deg, rgba(56, 189, 248, 0.13) 0%, rgba(6, 182, 212, 0.12) 50%, rgba(124, 58, 237, 0.13) 100%);
            padding: 1.25rem;
        }

        .purpose-grid {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .purpose-card {
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.86);
            padding: 1rem;
            height: 100%;
        }

        .purpose-card h3 {
            margin: 0.2rem 0 0.65rem;
            font-family: Sora, sans-serif;
            font-size: 1.05rem;
        }

        .purpose-card p {
            margin: 0;
            color: #214668;
        }

        .purpose-tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.25rem 0.6rem;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #0f4f82;
            background: rgba(56, 189, 248, 0.16);
            border: 1px solid rgba(27, 93, 146, 0.24);
        }

        .contact-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.55rem;
        }

        .contact-list li {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            color: #17395f;
            font-weight: 700;
        }

        .contact-list a {
            color: #1b5d92;
            text-decoration: none;
        }

        .contact-list a:hover {
            color: #fff;
            text-decoration: underline;
        }

        .footer {
            border-top: 1px solid var(--line);
            color: var(--muted);
            padding: 1.4rem 0 2.2rem;
            margin-top: 0.6rem;
            position: relative;
            z-index: 1;
        }

        .contact-title-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .contact-logo {
            width: 52px;
            height: 52px;
            border-radius: 999px;
            border: 2px solid rgba(27, 93, 146, 0.2);
            background: #fff;
            object-fit: cover;
            box-shadow: 0 10px 24px rgba(12, 106, 216, 0.18);
        }

        .footer a {
            color: #1b5d92;
            text-decoration: none;
        }

        .fade-up {
            opacity: 0;
            transform: translateY(20px);
            animation: rise 0.7s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        @keyframes rise {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 992px) {
            .quick-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .access-grid,
            .metric-grid,
            .purpose-grid,
            .illustration-row,
            .real-photo-grid,
            .about-grid {
                grid-template-columns: 1fr;
            }

            .real-photo-grid.media-distributed {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-auto-rows: 170px;
            }

            .real-photo-card.span-4,
            .real-photo-card.span-5,
            .real-photo-card.span-6,
            .real-photo-card.span-7,
            .real-photo-card.span-8,
            .real-photo-card.row-2 {
                grid-column: auto;
                grid-row: auto;
            }
        }

        @media (max-width: 576px) {
            .hero {
                padding-top: 6.8rem;
            }

            .quick-stats {
                grid-template-columns: 1fr;
            }

            .contact-logo {
                width: 46px;
                height: 46px;
            }
        }

        /* ── PROCESOS DEL SISTEMA ── */
        .flow-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.38rem 0.85rem;
            border-radius: 999px;
            background: linear-gradient(130deg, #7c3aed, #06b6d4);
            color: #fff;
            font-weight: 800;
            font-size: 0.8rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 0.6rem;
        }

        .flow-diagram {
            margin-top: 1.5rem;
        }

        .flow-row {
            display: grid;
            grid-template-columns: 1fr 340px 1fr;
            align-items: center;
            gap: 0.9rem;
        }

        .flow-cell-side {
            display: flex;
        }

        .flow-cell-left { justify-content: flex-end; }
        .flow-cell-right { justify-content: flex-start; }

        .flow-cell-center {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .flow-node {
            width: 100%;
            border-radius: 14px;
            padding: 0.9rem 1.2rem;
            text-align: center;
            font-family: Sora, sans-serif;
            font-weight: 700;
            font-size: 0.92rem;
            color: #fff;
            line-height: 1.4;
        }

        .flow-node-pill {
            border-radius: 999px;
            padding: 0.75rem 1.6rem;
            background: linear-gradient(130deg, #7c3aed 0%, #38bdf8 100%);
        }

        .flow-node-teal {
            background: linear-gradient(130deg, #06b6d4 0%, #38bdf8 100%);
        }

        .flow-node-green {
            background: linear-gradient(130deg, #22c55e 0%, #06b6d4 100%);
        }

        .flow-node-purple {
            background: linear-gradient(130deg, #7c3aed 0%, #4f46e5 100%);
        }

        .flow-node-web {
            background: linear-gradient(130deg, #22c55e 0%, #2dd4bf 100%);
        }

        .flow-node-db {
            background: rgba(56, 189, 248, 0.12);
            border: 1.5px solid rgba(56, 189, 248, 0.5);
            color: #0f4f82;
            border-radius: 14px;
        }

        .flow-connector {
            padding: 3px 0;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .flow-arrow-line {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .flow-arrow-line .stem {
            width: 2px;
            height: 28px;
            background: linear-gradient(to bottom, #38bdf8, #7c3aed);
        }

        .flow-arrow-line .head {
            width: 0; height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 9px solid #7c3aed;
        }

        .flow-callout {
            max-width: 200px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 0.65rem 0.8rem;
            font-size: 0.79rem;
            color: #214668;
            line-height: 1.5;
        }

        .flow-callout strong {
            display: block;
            font-family: Sora, sans-serif;
            font-size: 0.8rem;
            color: #7c3aed;
            margin-bottom: 0.25rem;
        }

        .flow-contact-callout {
            max-width: 200px;
            background: var(--grad-soft);
            border: 1px solid rgba(56, 189, 248, 0.4);
            border-radius: 12px;
            padding: 0.65rem 0.8rem;
            font-size: 0.8rem;
            color: #17395f;
            font-weight: 700;
            line-height: 1.75;
        }

        @media (max-width: 768px) {
            .flow-row { grid-template-columns: 1fr; }
            .flow-cell-side { display: none; }
        }

        /* ── GUÍA DE USO ── */
        .guide-wrap {
            margin-top: 1.5rem;
            border-radius: 20px;
            border: 1px solid var(--line);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.88);
        }

        .guide-tab-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid var(--line);
        }

        .gtab-btn {
            background: none;
            border: none;
            padding: 1rem 1.2rem;
            font-family: Sora, sans-serif;
            font-weight: 700;
            font-size: 0.93rem;
            color: var(--muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-bottom: 3px solid transparent;
            transition: background 0.2s, color 0.2s;
        }

        .gtab-btn:hover { background: rgba(56, 189, 248, 0.06); }

        .gtab-btn.gtab-user-active {
            color: #15803d;
            background: rgba(34, 197, 94, 0.08);
            border-bottom-color: #22c55e;
        }

        .gtab-btn.gtab-admin-active {
            color: #6d28d9;
            background: rgba(124, 58, 237, 0.08);
            border-bottom-color: #7c3aed;
        }

        .guide-pane { display: none; padding: 1.3rem; }
        .guide-pane-show { display: block !important; }

        .guide-steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 0.85rem;
        }

        .gstep {
            border-radius: 14px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 0.9rem;
        }

        .gstep-num {
            width: 28px; height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center; justify-content: center;
            font-family: Sora, sans-serif;
            font-weight: 800;
            font-size: 0.78rem;
            color: #fff;
            margin-bottom: 0.45rem;
        }

        .gstep-num-user  { background: linear-gradient(130deg, #22c55e, #06b6d4); }
        .gstep-num-admin { background: linear-gradient(130deg, #7c3aed, #38bdf8); }

        .gstep-icon {
            font-size: 1.2rem;
            margin-bottom: 0.4rem;
            display: block;
        }

        .gstep h4 {
            font-family: Sora, sans-serif;
            font-size: 0.86rem;
            font-weight: 700;
            margin: 0 0 0.3rem;
        }

        .gstep p {
            color: var(--muted);
            font-size: 0.79rem;
            margin: 0;
            line-height: 1.5;
        }

        .gstep p code {
            font-size: 0.74rem;
            background: rgba(56, 189, 248, 0.12);
            color: #0f4f82;
            padding: 0.1rem 0.3rem;
            border-radius: 4px;
        }

        /* ── Dark mode overrides for index ── */
        [data-theme="dark"] {
            --bg-deep: #0f172a;
            --bg-mid:  #1e293b;
            --panel:   #1e293b;
            --panel-soft: rgba(30,41,59,0.92);
            --line:    #334155;
            --txt:     #e2e8f0;
            --muted:   #94a3b8;
        }
        [data-theme="dark"] body {
            background: radial-gradient(1200px 560px at 92% -10%,rgba(14,30,60,.4),transparent 60%),
                radial-gradient(900px 540px at -8% 14%,rgba(30,15,60,.3),transparent 56%),
                linear-gradient(180deg,#0f172a 0%,#0c1a30 100%) !important;
        }
        [data-theme="dark"] .navbar-prermi {
            background: rgba(10,20,40,0.92) !important;
            border-bottom-color: rgba(56,189,248,.18) !important;
        }
        [data-theme="dark"] .nav-link { color: #94a3b8 !important; }
        [data-theme="dark"] .nav-link:hover { color: #38bdf8 !important; }
        [data-theme="dark"] .brand-name { color: #e2e8f0 !important; }
        [data-theme="dark"] .brand-sub  { color: #94a3b8 !important; }
        [data-theme="dark"] .btn-theme-index { background: rgba(255,255,255,.12) !important; color: #e2e8f0 !important; border-color: rgba(255,255,255,.22) !important; }
        [data-theme="dark"] .bg-grid { opacity: 0.07; }
        .btn-theme-index {
            background: rgba(0,0,0,.07);
            color: #1e3a5f;
            border: 1.5px solid rgba(0,0,0,.15);
            padding: .33rem .7rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: .9rem;
            transition: background .2s, color .2s;
            line-height: 1.2;
        }
        .btn-theme-index:hover { background: rgba(0,0,0,.14); }

        /* ── MEJORAS VISUALES GENERALES ── */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(56,189,248,.35), rgba(124,58,237,.35), transparent);
            margin: 0;
            position: relative;
            z-index: 1;
        }
        .grad-text {
            background: linear-gradient(90deg, #38bdf8 0%, #06b6d4 45%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .grad-text-green {
            background: linear-gradient(90deg, #10b981 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        /* Hero mejorado */
        .hero-kpi-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .7rem;
            margin-top: 1.4rem;
        }
        .hkpi {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            border-radius: 16px;
            padding: .85rem 1rem;
            border: 1px solid rgba(56,189,248,.3);
            background: rgba(255,255,255,.72);
            backdrop-filter: blur(6px);
            box-shadow: 0 8px 22px rgba(56,189,248,.08);
            transition: transform .22s ease, box-shadow .22s ease;
        }
        .hkpi:hover { transform: translateY(-3px); box-shadow: 0 16px 36px rgba(56,189,248,.14); }
        .hkpi .hkpi-val {
            font-size: 1.45rem;
            font-weight: 900;
            font-family: Sora, sans-serif;
            line-height: 1.1;
        }
        .hkpi .hkpi-lbl {
            font-size: .73rem;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-top: .2rem;
        }
        /* Value cards (sobre nosotros) */
        .value-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px,1fr));
            gap: .9rem;
            margin-top: 1.3rem;
        }
        .value-card {
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.88);
            padding: 1.3rem 1.1rem 1.1rem;
            transition: transform .22s ease, box-shadow .22s ease;
            position: relative;
            overflow: hidden;
        }
        .value-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--vc-grad);
            border-radius: 18px 18px 0 0;
        }
        .value-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(0,0,0,.1); }
        .value-card .vc-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: var(--vc-grad);
            display: inline-grid;
            place-items: center;
            font-size: 1.1rem;
            color: #fff;
            margin-bottom: .8rem;
        }
        .value-card h4 { font-family: Sora, sans-serif; font-size: .98rem; margin: 0 0 .4rem; }
        .value-card p  { margin: 0; color: var(--muted); font-size: .83rem; line-height: 1.6; }
        /* Impacto mejorado */
        .impact-num-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: .9rem;
            margin-top: 1.3rem;
        }
        .impact-card {
            border-radius: 18px;
            padding: 1.5rem;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .impact-card::after {
            content: '';
            position: absolute;
            width: 120px; height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
            bottom: -30px; right: -30px;
        }
        .impact-card .ic-icon { font-size: 1.5rem; margin-bottom: .5rem; opacity: .9; }
        .impact-card .ic-num  { font-size: 2.2rem; font-weight: 900; font-family: Sora, sans-serif; line-height: 1; }
        .impact-card .ic-lbl  { font-size: .78rem; margin-top: .35rem; opacity: .82; }
        .ic-blue   { background: linear-gradient(135deg, #0e7490, #38bdf8); }
        .ic-green  { background: linear-gradient(135deg, #065f46, #10b981); }
        .ic-purple { background: linear-gradient(135deg, #5b21b6, #7c3aed); }
        .ic-orange { background: linear-gradient(135deg, #9a3412, #f97316); }
        /* Pilares (propósito) */
        .pillar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
            gap: 1rem;
            margin-top: 1.3rem;
        }
        .pillar-card {
            border-radius: 18px;
            padding: 1.4rem;
            position: relative;
            overflow: hidden;
            color: #fff;
        }
        .pillar-card h3 { font-family: Sora, sans-serif; font-size: 1.05rem; margin: .7rem 0 .5rem; }
        .pillar-card p  { margin: 0; font-size: .84rem; opacity: .88; line-height: 1.6; }
        .pillar-card .p-icon { font-size: 1.8rem; }
        .pc-1 { background: linear-gradient(135deg, #0c4a6e, #0284c7); }
        .pc-2 { background: linear-gradient(135deg, #14532d, #15803d); }
        .pc-3 { background: linear-gradient(135deg, #4c1d95, #7c3aed); }
        .pc-4 { background: linear-gradient(135deg, #7c2d12, #ea580c); }
        /* Hero badge mejorado */
        .hero-tag {
            border: 1.5px solid rgba(56,189,248,.6);
            background: linear-gradient(130deg, rgba(56,189,248,.15), rgba(124,58,237,.1));
            color: #0f3a6e;
        }
        /* Acceso mejorado */
        .access-card {
            transition: transform .22s ease, box-shadow .22s ease;
        }
        .access-card:hover { transform: translateY(-4px); box-shadow: 0 20px 44px rgba(0,0,0,.1); }
        [data-theme="dark"] .value-card { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .value-card p { color: #94a3b8; }
        [data-theme="dark"] .hkpi { background: rgba(30,41,59,.75); border-color: #334155; }
        [data-theme="dark"] .hkpi .hkpi-lbl { color: #94a3b8; }
        @media (max-width: 768px) {
            .hero-kpi-strip { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .hero-kpi-strip { grid-template-columns: 1fr 1fr; }
        }

        .section-jump-wrap {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 80;
        }
        .section-jump-btn {
            border: 0;
            border-radius: 999px;
            background: linear-gradient(130deg, #0ea5e9, #7c3aed);
            color: #fff;
            font-weight: 800;
            font-size: .82rem;
            padding: .62rem .95rem;
            box-shadow: 0 12px 28px rgba(14,165,233,.28);
        }
        .section-jump-menu {
            margin-top: .55rem;
            min-width: 230px;
            background: rgba(255,255,255,.96);
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: 0 16px 34px rgba(15,23,42,.18);
            overflow: hidden;
            transform: translateX(120%);
            opacity: 0;
            pointer-events: none;
            transition: transform .26s ease, opacity .26s ease;
        }
        .section-jump-menu.show {
            transform: translateX(0);
            opacity: 1;
            pointer-events: auto;
        }
        .section-jump-menu a {
            display: block;
            text-decoration: none;
            color: #1e3a5f;
            font-size: .81rem;
            font-weight: 700;
            padding: .58rem .78rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .section-jump-menu a:last-child { border-bottom: 0; }
        .section-jump-menu a:hover { background: #eff6ff; }

        .concept-grid {
            margin-top: 1.15rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: .9rem;
        }
        .concept-card {
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.9);
            padding: 1rem;
        }
        .concept-card h4 {
            font-family: Sora, sans-serif;
            font-size: .95rem;
            margin: 0 0 .35rem;
        }
        .concept-card p {
            margin: 0;
            color: var(--muted);
            font-size: .82rem;
            line-height: 1.58;
        }

        .mini-flow {
            margin-top: 1rem;
            border-radius: 16px;
            border: 1px dashed #8ec8e8;
            background: rgba(255,255,255,.86);
            padding: .95rem;
        }
        .mini-flow-row {
            display: grid;
            grid-template-columns: 1fr 40px 1fr 40px;
            gap: .6rem;
            align-items: center;
        }
        .mini-step {
            border-radius: 12px;
            border: 1px solid #c6e6f8;
            background: #fff;
            padding: .65rem;
            text-align: center;
            font-size: .76rem;
            color: #325474;
            font-weight: 700;
        }
        .mini-step i {
            display: block;
            font-size: 1rem;
            margin-bottom: .25rem;
            color: #0ea5e9;
        }
        .mini-arrow {
            text-align: center;
            color: #7c3aed;
            font-size: 1.05rem;
            font-weight: 900;
        }

        /* ── Chart canvas — dejar que Chart.js controle alturas ── */
        .textil-chart-box canvas {
            display: block;
            width: 100% !important;
        }

        /* ── TCB (Textil Chart Box) componentes interactivos ── */
        .tcb-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: .65rem;
        }
        .tcb-title {
            font-family: Sora, sans-serif;
            font-size: .95rem;
            font-weight: 700;
            color: var(--txt);
            display: flex;
            align-items: center;
            gap: .45rem;
        }
        .tcb-controls {
            display: inline-flex;
            gap: .25rem;
            background: rgba(56,189,248,.1);
            border-radius: 9px;
            padding: .22rem;
        }
        .tcb-toggle {
            border: none;
            background: transparent;
            border-radius: 7px;
            font-family: Sora, sans-serif;
            font-size: .72rem;
            font-weight: 700;
            color: var(--muted);
            padding: .24rem .72rem;
            cursor: pointer;
            transition: background .22s, color .22s, transform .14s;
            white-space: nowrap;
            line-height: 1.3;
        }
        .tcb-toggle.active {
            background: var(--grad-main);
            color: #fff;
            box-shadow: 0 4px 10px rgba(56,189,248,.28);
        }
        .tcb-toggle:not(.active):hover {
            background: rgba(56,189,248,.2);
            color: var(--txt);
            transform: translateY(-1px);
        }
        .tcb-kpis {
            display: flex;
            gap: .85rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
            padding: .45rem .7rem;
            border-radius: 12px;
            background: rgba(56,189,248,.06);
            border: 1px solid rgba(56,189,248,.16);
        }
        .tcb-kpi {
            display: flex;
            flex-direction: column;
            gap: .05rem;
        }
        .tcb-kpi-val {
            font-family: Sora, sans-serif;
            font-weight: 900;
            font-size: .95rem;
            line-height: 1.1;
        }
        .tcb-kpi-lbl {
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--muted);
        }
        [data-theme="dark"] .tcb-controls { background: rgba(56,189,248,.14); }
        [data-theme="dark"] .tcb-kpis { background: rgba(56,189,248,.07); border-color: rgba(56,189,248,.22); }
        [data-theme="dark"] .tcb-title { color: #e2e8f0; }

        .bio-basic-grid {
            margin-top: 1.15rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: .85rem;
        }
        .bio-basic-card {
            border-radius: 14px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.9);
            padding: 1rem;
        }
        .bio-basic-card .step-badge {
            display: inline-block;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .08em;
            color: #fff;
            background: linear-gradient(130deg,#059669,#0ea5e9);
            border-radius: 999px;
            padding: .18rem .55rem;
            margin-bottom: .4rem;
        }
        .bio-basic-card h4 { margin: .1rem 0 .35rem; font-family: Sora, sans-serif; font-size: .9rem; }
        .bio-basic-card p  { margin: 0; color: var(--muted); font-size: .81rem; line-height: 1.55; }

        .calc-basic {
            margin-top: 1.2rem;
            border-radius: 16px;
            border: 1px solid #0ea5e9;
            background: linear-gradient(130deg, rgba(14,165,233,.08), rgba(16,185,129,.08));
            padding: 1rem;
        }
        .calc-basic h4 { font-family: Sora, sans-serif; font-size: .97rem; margin: 0 0 .6rem; }
        .calc-step-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: .75rem;
        }
        .calc-step {
            border-radius: 12px;
            background: rgba(255,255,255,.92);
            border: 1px solid #c7e6f5;
            padding: .8rem;
        }
        .calc-step strong { display: block; color: #0f4f82; font-size: .8rem; margin-bottom: .2rem; }
        .calc-step p { margin: 0; color: #355776; font-size: .78rem; line-height: 1.52; }

        [data-theme="dark"] .section-jump-menu { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .section-jump-menu a { color: #e2e8f0; border-bottom-color: #334155; }
        [data-theme="dark"] .section-jump-menu a:hover { background: #0f172a; }
        [data-theme="dark"] .concept-card,
        [data-theme="dark"] .bio-basic-card,
        [data-theme="dark"] .calc-step { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .concept-card p,
        [data-theme="dark"] .bio-basic-card p,
        [data-theme="dark"] .calc-step p { color: #94a3b8; }
        [data-theme="dark"] .calc-step strong { color: #67e8f9; }
        [data-theme="dark"] .mini-flow { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .mini-step { background: #0f172a; border-color: #334155; color: #94a3b8; }

        /* ══ BIOENERGIA ══ */
        .bio-section { position:relative;z-index:1;padding:2.8rem 0; }
        .bio-badge {
            display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .9rem;border-radius:999px;
            background:linear-gradient(130deg,#065f46,#059669);color:#fff;
            font-weight:800;font-size:.77rem;letter-spacing:.07em;text-transform:uppercase;margin-bottom:.7rem;
        }
        .bio-unique-grid {
            margin-top:1.2rem;display:grid;
            grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.9rem;
        }
        .bio-unique-card {
            border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.88);padding:1rem;
            position:relative;padding-top:1.4rem;
        }
        .bio-num {
            position:absolute;top:-11px;left:14px;
            background:linear-gradient(130deg,#065f46,#10b981);color:#fff;
            font-weight:800;font-size:.74rem;border-radius:999px;padding:.18rem .65rem;
        }
        .bio-unique-card h4 { margin:.3rem 0 .3rem;font-family:Sora,sans-serif;font-size:.9rem; }
        .bio-unique-card p  { margin:0;color:#214668;font-size:.81rem;line-height:1.55; }
        .bio-formula-box {
            margin-top:1.4rem;background:rgba(15,23,42,.88);border-radius:16px;
            padding:1.3rem;border:1px solid rgba(16,185,129,.3);color:#e2e8f0;
        }
        .bio-formula-box h4 { font-family:Sora,sans-serif;color:#10b981;margin:0 0 .9rem;font-size:.97rem; }
        .bio-formula-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:.7rem; }
        .bio-formula-item { background:rgba(255,255,255,.08);border-radius:10px;padding:.8rem; }
        .bio-formula-item .f-title { color:#06b6d4;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem; }
        .bio-formula-item code { background:rgba(6,182,212,.15);color:#67e8f9;padding:.12rem .4rem;border-radius:5px;font-size:.77rem;display:block;margin-bottom:.35rem; }
        .bio-formula-item p { margin:0;font-size:.79rem;color:#cbd5e1;line-height:1.5; }
        .bio-sce-grid { margin-top:1.4rem;display:grid;grid-template-columns:repeat(3,1fr);gap:.85rem; }
        .bio-sce-card { border-radius:14px;padding:1.1rem;text-align:center;color:#fff; }
        .bio-sce-card.c1 { background:linear-gradient(135deg,#1e293b,#334155); }
        .bio-sce-card.c2 { background:linear-gradient(135deg,#065f46,#059669); }
        .bio-sce-card.c3 { background:linear-gradient(135deg,#5b21b6,#7c3aed); }
        .bio-sce-card .sc-tag  { font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;background:rgba(255,255,255,.15);border-radius:999px;padding:.18rem .6rem;display:inline-block;margin-bottom:.5rem; }
        .bio-sce-card .sc-kwh  { font-size:1.7rem;font-weight:900;line-height:1.1; }
        .bio-sce-card .sc-rd   { font-size:.97rem;font-weight:700;opacity:.88; }
        .bio-sce-card .sc-anual{ font-size:.72rem;opacity:.6;margin-top:.3rem; }

        /* ══ SECTOR TEXTIL ══ */
        .textil-section { position:relative;z-index:1;padding:2.8rem 0; }
        .textil-badge {
            display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .9rem;border-radius:999px;
            background:linear-gradient(130deg,#7c3aed,#0e7490);color:#fff;
            font-weight:800;font-size:.77rem;letter-spacing:.07em;text-transform:uppercase;margin-bottom:.7rem;
        }
        .textil-heat-grid {
            margin-top:1.1rem;display:grid;
            grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:.85rem;
        }
        .textil-heat-card { border-radius:14px;border:1px solid var(--line);background:rgba(255,255,255,.88);padding:.9rem;text-align:center; }
        .textil-heat-card .heat-icon { font-size:1.7rem;margin-bottom:.35rem; }
        .textil-heat-card h5 { font-family:Sora,sans-serif;font-size:.85rem;margin:0 0 .3rem; }
        .heat-temp { background:linear-gradient(130deg,#ef4444,#f97316);color:#fff;border-radius:999px;display:inline-block;font-weight:800;font-size:.77rem;padding:.16rem .6rem;margin:.25rem 0; }
        .textil-heat-card p { margin:0;color:var(--muted);font-size:.78rem; }
        .textil-roi-grid { margin-top:1.4rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:1rem; }
        .textil-roi-card { border-radius:16px;padding:1.2rem;color:#fff;text-align:center; }
        .textil-roi-card.sm { background:linear-gradient(135deg,#0e7490,#06b6d4); }
        .textil-roi-card.md { background:linear-gradient(135deg,#5b21b6,#7c3aed); }
        .textil-roi-card.lg { background:linear-gradient(135deg,#065f46,#10b981); }
        .textil-roi-card .roi-size { font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;opacity:.75;margin-bottom:.4rem; }
        .textil-roi-card .roi-kwh  { font-size:1.55rem;font-weight:900; }
        .textil-roi-card .roi-rd   { font-size:1rem;font-weight:700;opacity:.88; }
        .textil-roi-card .roi-meta { font-size:.74rem;opacity:.68;margin-top:.35rem; }
        .textil-chart-box { background:rgba(255,255,255,.90);border:1px solid var(--line);border-radius:18px;padding:1.3rem;margin-top:1.4rem; }
        .textil-chart-box h4 { font-family:Sora,sans-serif;font-size:.97rem;margin:0 0 .9rem;color:var(--txt); }
        .textil-chart-box {
            position: relative;
            overflow: hidden;
            box-shadow: 0 14px 36px rgba(17, 93, 160, 0.12);
        }
        .textil-chart-box::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: linear-gradient(90deg, #06b6d4 0%, #10b981 44%, #7c3aed 100%);
        }
        .textil-chart-note {
            margin-top: .9rem;
            border-radius: 12px;
            border: 1px dashed rgba(27, 93, 146, 0.45);
            background: rgba(255, 255, 255, 0.72);
            color: #214668;
            font-size: .79rem;
            padding: .65rem .75rem;
            line-height: 1.55;
        }
        [data-theme="dark"] .bio-unique-card { background:#1e293b; }
        [data-theme="dark"] .bio-unique-card p { color:#94a3b8; }
        [data-theme="dark"] .textil-heat-card { background:#1e293b; }
        [data-theme="dark"] .textil-heat-card p { color:#94a3b8; }
        [data-theme="dark"] .textil-chart-box {
            background: linear-gradient(170deg, rgba(30,41,59,.96), rgba(15,23,42,.95));
            color:#e2e8f0;
            border-color: rgba(59,130,246,.35);
            box-shadow: 0 16px 34px rgba(1, 12, 30, 0.55);
        }
        [data-theme="dark"] .textil-chart-box h4 { color:#e2e8f0; }
        [data-theme="dark"] .textil-chart-note {
            background: rgba(15, 23, 42, 0.92);
            border-color: rgba(56, 189, 248, 0.5);
            color: #cbd5e1;
        }
        [data-theme="dark"] .panel-card,
        [data-theme="dark"] .about-card,
        [data-theme="dark"] .access-card,
        [data-theme="dark"] .metric-card,
        [data-theme="dark"] .illustration-card,
        [data-theme="dark"] .purpose-card,
        [data-theme="dark"] .contact-card,
        [data-theme="dark"] .bio-unique-card,
        [data-theme="dark"] .textil-heat-card,
        [data-theme="dark"] .concept-card {
            border-color: rgba(56, 189, 248, 0.25);
            box-shadow: 0 12px 26px rgba(1, 12, 30, 0.4);
        }
        [data-theme="dark"] .dropdown-menu {
            background: #0f172a;
            border-color: rgba(56, 189, 248, 0.28);
        }
        [data-theme="dark"] .dropdown-item {
            color: #cbd5e1;
        }
        [data-theme="dark"] .dropdown-item:hover,
        [data-theme="dark"] .dropdown-item:focus {
            background: rgba(56, 189, 248, 0.16);
            color: #e2e8f0;
        }
        @media(max-width:768px) { .bio-sce-grid { grid-template-columns:1fr; } }
    </style>
    <script>(function(){var t=localStorage.getItem('prermi_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
    <div class="bg-grid"></div>

    <nav class="navbar navbar-expand-lg navbar-prermi">
        <div class="container">
            <a class="brand-wrap" href="#inicio">
                <img src="/PRERMI/uploads/LOGO/LOGO%20OFICIAL%20PRERMI.png" alt="Logo PRERMI" style="height:44px;width:auto;background:#fff;border-radius:8px;padding:3px 8px;box-shadow:0 2px 8px rgba(0,0,0,.2);">
                <span class="brand-name">PRERMI<span class="brand-sub">Feria Proindustria</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Menú">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navSecciones" role="button" data-bs-toggle="dropdown" aria-expanded="false">Secciones</a>
                        <ul class="dropdown-menu" aria-labelledby="navSecciones">
                            <li><a class="dropdown-item" href="#inicio">🏠 Inicio</a></li>
                            <li><a class="dropdown-item" href="#sobre-nosotros">🧩 Proyecto PRERMI</a></li>
                            <li><a class="dropdown-item" href="#impacto">📈 Impacto</a></li>
                            <li><a class="dropdown-item" href="#analitica">📊 Analítica</a></li>
                            <li><a class="dropdown-item" href="#contenedores-inteligentes">🗑️ Contenedores Inteligentes</a></li>
                            <li><a class="dropdown-item" href="#monitoreo-inteligente">🛰️ Monitoreo Inteligente</a></li>
                            <li><a class="dropdown-item" href="#bioenergia">🌿 Sistema BIOMASA + Peltier</a></li>
                            <li><a class="dropdown-item" href="#textil">🧵 Sector Textil</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="web/register.php">Registrarse</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administración</a>
                        <ul class="dropdown-menu" aria-labelledby="navAdmin">
                            <li><a class="dropdown-item" href="web/admin/registerA.php"><i class="fa-solid fa-user-shield"></i> Registrarse como Admin</a></li>
                            <li><a class="dropdown-item" href="web/admin/loginA.php"><i class="fa-solid fa-lock"></i> Iniciar sesión Admin</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link contact-nav-link" href="#contacto">
                            <img src="web/assets/img/image.png" alt="Logo Instituto Tecnológico México" class="contact-mini-logo">
                            Contacto
                        </a>
                    </li>
                    <li class="nav-item ms-lg-1"><a href="web/register.php" class="btn-outline-soft">Crear Cuenta</a></li>
                    <li class="nav-item ms-lg-2"><a href="web/login.php" class="btn-main">Entrar</a></li>
                    <li class="nav-item ms-lg-2">
                        <button id="btnTheme" class="btn-theme-index" onclick="toggleTheme()" title="Cambiar tema"><i class="fas fa-moon"></i></button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero" id="inicio">
        <div class="container">
            <span class="hero-tag fade-up"><i class="fa-solid fa-award"></i> Presentado en Feria Proindustria 2026 &mdash; Instituto Tecnológico México</span>
            <h1 class="hero-title fade-up delay-1">
                <span class="grad-text">Programa de Reabastecimiento Energético,</span><br>Residuos y Monitoreo Inteligente <span class="grad-text-green">(PRERMI)</span>
            </h1>
            <p class="hero-lead fade-up delay-2">
                PRERMI automatiza la recolección de residuos, los convierte en energía mediante biogás + celdas Peltier y entrega control total vía plataforma web. <strong>Una inversión que se paga sola.</strong>
            </p>
            <p class="section-sub fade-up delay-2" style="max-width:900px;margin-top:.45rem;">
                <strong>Programa de Reabastecimiento Energético, Residuos y Monitoreo Inteligente (PRERMI):</strong> solución integral para convertir residuos en valor energético, reducir costos operativos y digitalizar toda la gestión ambiental de tu empresa.
            </p>
            <div class="hero-actions fade-up delay-3">
                <a class="btn-main" href="#bioenergia"><i class="fa-solid fa-fire-flame-curved"></i> Ver tecnología</a>
                <a class="btn-alt" href="#contacto"><i class="fa-solid fa-handshake"></i> Agendar demostración</a>
                <a class="btn-outline-soft" href="web/login.php"><i class="fa-solid fa-gauge-high"></i> Acceder a la plataforma</a>
            </div>

            <div class="hero-kpi-strip fade-up delay-3">
                <div class="hkpi">
                    <div class="hkpi-val" style="color:#38bdf8;">RD$ 2.9M</div>
                    <div class="hkpi-lbl">Ahorro operativo / año</div>
                </div>
                <div class="hkpi">
                    <div class="hkpi-val" style="color:#10b981;">14.8 meses</div>
                    <div class="hkpi-lbl">ROI estimado</div>
                </div>
                <div class="hkpi">
                    <div class="hkpi-val" style="color:#7c3aed;">41%</div>
                    <div class="hkpi-lbl">Reducción de huella</div>
                </div>
                <div class="hkpi">
                    <div class="hkpi-val" style="color:#f97316;">100%</div>
                    <div class="hkpi-lbl">Eventos trazables</div>
                </div>
            </div>
        </div>
    </header>

    <div class="section-divider"></div>

    <section class="section" id="sobre-nosotros">
        <div class="container">
            <span style="display:inline-flex;align-items:center;gap:.45rem;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#0e7490;background:rgba(6,182,212,.1);border:1px solid rgba(6,182,212,.28);border-radius:999px;padding:.3rem .85rem;margin-bottom:.7rem;">
                <i class="fa-solid fa-bolt"></i> ¿Por qué PRERMI?
            </span>
            <h2 class="section-title">Programa de Reabastecimiento Energético, Residuos y Monitoreo Inteligente (PRERMI)</h2>
            <p class="section-sub">De los residuos al panel de control: automatizamos cada paso, generamos energía y te damos los números que importan.</p>

            <div class="value-grid">
                <article class="value-card" style="--vc-grad: linear-gradient(130deg,#0e7490,#06b6d4);">
                    <div class="vc-icon"><i class="fa-solid fa-coins"></i></div>
                    <h4>Reduce tu factura eléctrica</h4>
                    <p>El biogás generado por tus residuos orgánicos alimenta un generador. Ahorra hasta <strong>RD$ 15,600/mes</strong> según el volumen operativo.</p>
                </article>
                <article class="value-card" style="--vc-grad: linear-gradient(130deg,#065f46,#10b981);">
                    <div class="vc-icon"><i class="fa-solid fa-recycle"></i></div>
                    <h4>Valoriza tus residuos</h4>
                    <p>Cada kilogramo depositado genera crédito energético. Convierte un costo de disposición en un <strong>activo productivo medible</strong>.</p>
                </article>
                <article class="value-card" style="--vc-grad: linear-gradient(130deg,#5b21b6,#7c3aed);">
                    <div class="vc-icon"><i class="fa-solid fa-microchip"></i></div>
                    <h4>Control IoT en tiempo real</h4>
                    <p>Temperatura, corriente, ventilación y energía generada — visibles desde cualquier dispositivo. <strong>Sin instalar software adicional.</strong></p>
                </article>
                <article class="value-card" style="--vc-grad: linear-gradient(130deg,#9a3412,#f97316);">
                    <div class="vc-icon"><i class="fa-solid fa-id-badge"></i></div>
                    <h4>Trazabilidad biométrica</h4>
                    <p>Reconocimiento facial por contenedor. Cada depósito queda vinculado al usuario, con historial, sanciones y reportes <strong>automáticos</strong>.</p>
                </article>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <section class="section" id="impacto">
        <div class="container">
            <h2 class="section-title">Números que <span class="grad-text">hablan por sí solos</span></h2>
            <p class="section-sub">Resultados reales a escala industrial. Estos son los indicadores que PRERMI entrega desde el primer mes de operación.</p>

            <div class="impact-num-grid">
                <article class="impact-card ic-blue">
                    <div class="ic-icon"><i class="fa-solid fa-bolt"></i></div>
                    <div class="ic-num counter" data-target="1240">0</div>
                    <div class="ic-lbl">kWh mensuales promedio recuperados</div>
                </article>
                <article class="impact-card ic-green">
                    <div class="ic-icon"><i class="fa-solid fa-coins"></i></div>
                    <div class="ic-num counter" data-target="80600">0</div>
                    <div class="ic-lbl">RD$ de valor económico mensual</div>
                </article>
                <article class="impact-card ic-purple">
                    <div class="ic-icon"><i class="fa-solid fa-recycle"></i></div>
                    <div class="ic-num counter" data-target="38">0</div>
                    <div class="ic-lbl">Toneladas/mes de residuos valorizados</div>
                </article>
                <article class="impact-card ic-orange">
                    <div class="ic-icon"><i class="fa-solid fa-user-check"></i></div>
                    <div class="ic-num counter" data-target="97">0</div>
                    <div class="ic-lbl">% precisión biométrica / usuario</div>
                </article>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <section class="section" id="analitica">
        <div class="container">
            <h2 class="section-title">Tu operación completa, <span class="grad-text">en una pantalla</span></h2>
            <p class="section-sub">Paneles ejecutivos de energía, costos y desempeño para decisiones inmediatas. Sin cálculos extra, sin hojas de cálculo.</p>

            <div class="row g-3 charts-grid">
                <div class="col-lg-8">
                    <div class="panel-card">
                        <h3><i class="fa-solid fa-arrow-trend-up"></i> Flujo económico acumulado (12 meses)</h3>
                        <div class="canvas-wrap"><canvas id="cashflowChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="panel-card">
                        <h3><i class="fa-solid fa-chart-pie"></i> Distribución del valor PRERMI</h3>
                        <div class="canvas-wrap"><canvas id="valueChart"></canvas></div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="panel-card">
                        <h3><i class="fa-solid fa-industry"></i> Rendimiento por nodo operativo</h3>
                        <div class="canvas-wrap"><canvas id="opsChart"></canvas></div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <div class="section-divider"></div>

    <section class="section" id="acceso">
        <div class="container">
            <h2 class="section-title">Accede ahora <span class="grad-text">desde cualquier dispositivo</span></h2>
            <p class="section-sub">No se requiere instalación. Entra con tu cuenta y ten control total desde el celular, tablet o computadora.</p>

            <div class="access-grid">
                <article class="access-card access-user">
                    <span class="access-icon"><i class="fa-solid fa-user"></i></span>
                    <h3 style="font-family: Sora, sans-serif;">Portal de Usuarios</h3>
                    <p style="color: var(--muted);">Consulta tus depósitos, créditos energéticos, historial y sanciones. Trazabilidad individual 100% digital.</p>
                    <a class="btn-main" href="web/login.php"><i class="fa-solid fa-right-to-bracket"></i> Ingresar como Usuario</a>
                </article>
                <article class="access-card access-admin">
                    <span class="access-icon"><i class="fa-solid fa-user-shield"></i></span>
                    <h3 style="font-family: Sora, sans-serif;">Panel Administrativo</h3>
                    <p style="color: var(--muted);">Monitorea sensores en tiempo real, gestiona usuarios, emite sanciones y controla el biorreactor BIOMASA remotamente.</p>
                    <div class="access-actions">
                        <a class="btn-alt" href="web/admin/loginA.php"><i class="fa-solid fa-lock"></i> Ingresar como Admin</a>
                        <a class="btn-outline-soft" href="web/admin/registerA.php"><i class="fa-solid fa-user-plus"></i> Registrar Admin</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="contenedores-inteligentes" style="padding-top:1.2rem;">
        <div class="container">
            <h2 class="section-title">Contenedores Inteligentes</h2>
            <p class="section-sub">Reciben residuos, validan usuario y registran todo automáticamente. Más control, menos pérdidas y mejor experiencia para el ciudadano o colaborador.</p>
            <div class="concept-grid">
                <article class="concept-card">
                    <h4><i class="fa-solid fa-face-smile" style="color:#0ea5e9;"></i> Identificación automática</h4>
                    <p>Reconocimiento facial para saber quién depositó, cuándo y cuánto. El registro queda listo para seguimiento y recompensas.</p>
                </article>
                <article class="concept-card">
                    <h4><i class="fa-solid fa-scale-balanced" style="color:#10b981;"></i> Pesaje en tiempo real</h4>
                    <p>El contenedor calcula los kg depositados y envía los datos al sistema sin procesos manuales.</p>
                </article>
                <article class="concept-card">
                    <h4><i class="fa-solid fa-shield-halved" style="color:#7c3aed;"></i> Trazabilidad y control</h4>
                    <p>Cada evento queda respaldado con fecha, usuario y contenedor para auditoría y control administrativo.</p>
                </article>
                <article class="concept-card">
                    <h4><i class="fa-solid fa-hand-holding-dollar" style="color:#f97316;"></i> Beneficio directo</h4>
                    <p>Convierte un proceso de descarte en una fuente medible de ahorro energético y valor económico.</p>
                </article>
            </div>

            <div class="mini-flow">
                <h4 style="font-family:Sora,sans-serif;font-size:.9rem;margin:0 0 .6rem;"><i class="fa-solid fa-diagram-project" style="color:#7c3aed;"></i> Flujo básico de Contenedores Inteligentes</h4>
                <div class="mini-flow-row">
                    <div class="mini-step"><i class="fa-solid fa-user-check"></i>Usuario se valida</div>
                    <div class="mini-arrow">→</div>
                    <div class="mini-step"><i class="fa-solid fa-weight-scale"></i>Se pesa el residuo</div>
                    <div class="mini-arrow">→</div>
                </div>
                <div class="mini-flow-row" style="margin-top:.55rem;">
                    <div class="mini-step"><i class="fa-solid fa-database"></i>Se registra en la nube</div>
                    <div class="mini-arrow">→</div>
                    <div class="mini-step"><i class="fa-solid fa-bolt"></i>Genera crédito energético</div>
                    <div class="mini-arrow">→</div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="monitoreo-inteligente" style="padding-top:0.8rem;">
        <div class="container">
            <h2 class="section-title">Monitoreo Inteligente</h2>
            <p class="section-sub">PRERMI te muestra el estado real de la operación: sensores, energía, alertas y rendimiento financiero en un solo panel web.</p>
            <div class="concept-grid">
                <article class="concept-card">
                    <h4><i class="fa-solid fa-satellite-dish" style="color:#06b6d4;"></i> Sensores 24/7</h4>
                    <p>Temperatura, corriente, ventilación y generación energética actualizados continuamente.</p>
                </article>
                <article class="concept-card">
                    <h4><i class="fa-solid fa-bell" style="color:#ef4444;"></i> Alertas inmediatas</h4>
                    <p>Si un valor sale de rango, el sistema notifica para actuar rápido y evitar pérdidas operativas.</p>
                </article>
                <article class="concept-card">
                    <h4><i class="fa-solid fa-chart-column" style="color:#10b981;"></i> Reportes automáticos</h4>
                    <p>Informes de ahorro, energía y cumplimiento listos para dirección, clientes e instituciones.</p>
                </article>
                <article class="concept-card">
                    <h4><i class="fa-solid fa-mobile-screen-button" style="color:#7c3aed;"></i> Acceso desde cualquier lugar</h4>
                    <p>La gerencia puede revisar indicadores clave desde celular o laptop sin depender de visitas presenciales.</p>
                </article>
            </div>

            <div class="real-photo-grid media-distributed" style="margin-top:1rem;">
                <article class="real-photo-card span-7 row-2">
                    <img src="/PRERMI/uploads/images/monitoring-ai-performance.jpg" class="img-focus-center" alt="Sala de control y analitica operativa" loading="lazy" onerror="this.onerror=null;this.src='/PRERMI/web/assets/img/illus-proindustria-stand.svg';">
                </article>
                <article class="real-photo-card span-5 row-2">
                    <img src="/PRERMI/uploads/images/facial-recognition.jpg" class="img-focus-center" alt="Biometria facial para validacion de usuario" loading="lazy" onerror="this.onerror=null;this.src='/PRERMI/web/assets/img/illus-iot-monitor.svg';">
                </article>
            </div>

            <div class="mini-flow">
                <h4 style="font-family:Sora,sans-serif;font-size:.9rem;margin:0 0 .6rem;"><i class="fa-solid fa-diagram-project" style="color:#7c3aed;"></i> Flujo básico de Monitoreo Inteligente</h4>
                <div class="mini-flow-row">
                    <div class="mini-step"><i class="fa-solid fa-satellite-dish"></i>Sensores IoT envían data</div>
                    <div class="mini-arrow">→</div>
                    <div class="mini-step"><i class="fa-solid fa-microchip"></i>PRERMI procesa eventos</div>
                    <div class="mini-arrow">→</div>
                </div>
                <div class="mini-flow-row" style="margin-top:.55rem;">
                    <div class="mini-step"><i class="fa-solid fa-bell"></i>Genera alertas</div>
                    <div class="mini-arrow">→</div>
                    <div class="mini-step"><i class="fa-solid fa-chart-line"></i>Dashboard y reportes</div>
                    <div class="mini-arrow">→</div>
                </div>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <section class="section" id="proposito">
        <div class="container">
            <h2 class="section-title">Por qué <span class="grad-text">invertir en PRERMI</span></h2>
            <p class="section-sub">Cuatro pilares que justifican la decisión desde el primer día.</p>

            <div class="pillar-grid">
                <article class="pillar-card pc-1">
                    <div class="p-icon">💰</div>
                    <h3>Retorno tangible y medido</h3>
                    <p>ROI documentado en menos de 15 meses. El sistema genera ingresos energéticos desde el ciclo 1, con panel de seguimiento incluido.</p>
                </article>
                <article class="pillar-card pc-2">
                    <div class="p-icon">🌱</div>
                    <h3>Sostenibilidad que vende</h3>
                    <p>Reduce tu huella de carbono un 41% y obtén evidencia verificable para certificaciones ambientales, clientes y licitaciones públicas.</p>
                </article>
                <article class="pillar-card pc-3">
                    <div class="p-icon">⚡</div>
                    <h3>Tecnología lista para operar</h3>
                    <p>Sistema instalado, calibrado y funcionando desde el primer día. Sin curva de aprendizaje larga. Soporte técnico incluido.</p>
                </article>
                <article class="pillar-card pc-4">
                    <div class="p-icon">📊</div>
                    <h3>Datos para tomar decisiones</h3>
                    <p>KPIs de energía, residuos y operación actualizados en tiempo real. Reportes exportables listos para tu dirección o consejo.</p>
                </article>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <!-- ═══════════ PROCESOS DEL SISTEMA ═══════════ -->
    <section class="section" id="procesos">
        <div class="container">
            <span class="flow-badge"><i class="fa-solid fa-diagram-project"></i> Arquitectura operativa integrada</span>
            <h2 class="section-title">Procesos del Sistema</h2>
            <p class="section-sub">Flujo integrado desde la recolección inteligente de residuos hasta el almacenamiento seguro de datos, pasando por la generación de energía renovable y el monitoreo vehicular en tiempo real.</p>

            <div class="flow-diagram">

                <!-- Fila 1: Contenedores Inteligentes -->
                <div class="flow-row">
                    <div class="flow-cell-side flow-cell-left">
                        <div class="flow-callout">
                            <strong>Contenedores Inteligentes</strong>
                            Sistema de depósitos de residuos orgánicos mediante el uso de contenedores con reconocimiento facial e información en tiempo real.
                        </div>
                    </div>
                    <div class="flow-cell-center">
                        <div class="flow-node flow-node-pill">
                            <i class="fa-solid fa-trash-can"></i>&nbsp; Contenedores Inteligentes
                        </div>
                    </div>
                    <div class="flow-cell-side flow-cell-right">
                        <div class="flow-contact-callout">
                            <i class="fa-brands fa-instagram" style="color:#7c3aed;"></i>&nbsp; @institutotecnologicomexico<br>
                            <i class="fa-solid fa-phone" style="color:#06b6d4;"></i>&nbsp; +1(809)575-4270
                        </div>
                    </div>
                </div>

                <!-- Flecha -->
                <div class="flow-row"><div class="flow-cell-side"></div><div class="flow-cell-center"><div class="flow-connector"><div class="flow-arrow-line"><div class="stem"></div><div class="head"></div></div></div></div><div class="flow-cell-side"></div></div>

                <!-- Fila 2: Conversión de Residuos en Energía -->
                <div class="flow-row">
                    <div class="flow-cell-side flow-cell-left">
                        <div class="flow-callout">
                            Los residuos orgánicos recolectados se procesan mediante tecnologías de biomasa.
                        </div>
                    </div>
                    <div class="flow-cell-center">
                        <div class="flow-node flow-node-teal">
                            <i class="fa-solid fa-fire-flame-curved"></i><br>
                            <strong>Conversión de Residuos en Energía</strong><br>
                            <small style="font-weight:600;opacity:0.9;">(Biomasa)</small>
                        </div>
                    </div>
                    <div class="flow-cell-side"></div>
                </div>

                <!-- Flecha -->
                <div class="flow-row"><div class="flow-cell-side"></div><div class="flow-cell-center"><div class="flow-connector"><div class="flow-arrow-line"><div class="stem"></div><div class="head"></div></div></div></div><div class="flow-cell-side"></div></div>

                <!-- Fila 3: Monitoreo de Vehículos -->
                <div class="flow-row">
                    <div class="flow-cell-side"></div>
                    <div class="flow-cell-center">
                        <div class="flow-node flow-node-green">
                            <i class="fa-solid fa-truck-fast"></i>&nbsp; Monitoreo de Vehículos Oficiales
                        </div>
                    </div>
                    <div class="flow-cell-side flow-cell-right">
                        <div class="flow-callout">
                            Los camiones recolectores y vehículos del sistema cuentan con IA y cámaras que verifican sus rutas en tiempo real.
                        </div>
                    </div>
                </div>

                <!-- Flecha -->
                <div class="flow-row"><div class="flow-cell-side"></div><div class="flow-cell-center"><div class="flow-connector"><div class="flow-arrow-line"><div class="stem"></div><div class="head"></div></div></div></div><div class="flow-cell-side"></div></div>

                <!-- Fila 4: Gestión del Tránsito -->
                <div class="flow-row">
                    <div class="flow-cell-side flow-cell-left">
                        <div class="flow-callout">
                            Integración de sistemas de control de tránsito y monitoreo en tiempo real.
                        </div>
                    </div>
                    <div class="flow-cell-center">
                        <div class="flow-node flow-node-purple">
                            <i class="fa-solid fa-traffic-light"></i><br>
                            <strong>Gestión del Tránsito en Emergencias</strong>
                        </div>
                    </div>
                    <div class="flow-cell-side"></div>
                </div>

                <!-- Flecha -->
                <div class="flow-row"><div class="flow-cell-side"></div><div class="flow-cell-center"><div class="flow-connector"><div class="flow-arrow-line"><div class="stem"></div><div class="head"></div></div></div></div><div class="flow-cell-side"></div></div>

                <!-- Fila 5: Gestión Web -->
                <div class="flow-row">
                    <div class="flow-cell-side"></div>
                    <div class="flow-cell-center">
                        <div class="flow-node flow-node-web">
                            <i class="fa-solid fa-desktop"></i>&nbsp; Gestión y control computarizado vía interfaz web
                        </div>
                    </div>
                    <div class="flow-cell-side"></div>
                </div>

                <!-- Flecha -->
                <div class="flow-row"><div class="flow-cell-side"></div><div class="flow-cell-center"><div class="flow-connector"><div class="flow-arrow-line"><div class="stem"></div><div class="head"></div></div></div></div><div class="flow-cell-side"></div></div>

                <!-- Fila 6: Base de datos -->
                <div class="flow-row">
                    <div class="flow-cell-side"></div>
                    <div class="flow-cell-center">
                        <div class="flow-node flow-node-db">
                            <i class="fa-solid fa-database"></i>&nbsp; Toda la información recolectada se almacena en una base de datos segura
                        </div>
                    </div>
                    <div class="flow-cell-side"></div>
                </div>

            </div><!-- /flow-diagram -->
        </div>
    </section>

    <!-- ═══════════ GUÍA DE USO ═══════════ -->
    <section class="section" id="guia">
        <div class="container">
            <h2 class="section-title">Guía de uso de la plataforma</h2>
            <p class="section-sub">Instrucciones didácticas paso a paso para navegar y aprovechar al máximo la interfaz PRERMI, tanto como usuario registrado como administrador del sistema.</p>

            <div class="guide-wrap">
                <!-- Pestañas -->
                <div class="guide-tab-row">
                    <button class="gtab-btn gtab-user-active" id="btnGuideUser" onclick="switchGuide('user')">
                        <i class="fa-solid fa-user"></i> Para Usuarios
                    </button>
                    <button class="gtab-btn" id="btnGuideAdmin" onclick="switchGuide('admin')">
                        <i class="fa-solid fa-user-shield"></i> Para Administradores
                    </button>
                </div>

                <!-- Panel Usuario -->
                <div class="guide-pane guide-pane-show" id="guide-user">
                    <div class="guide-steps-grid">
                        <div class="gstep">
                            <span class="gstep-num gstep-num-user">1</span>
                            <span class="gstep-icon" style="color:#22c55e;"><i class="fa-solid fa-user-plus"></i></span>
                            <h4>Crear tu cuenta</h4>
                            <p>Haz clic en "Registrarse" en la página de inicio. Completa el formulario con nombre, correo y contraseña, y sube tu foto facial para el reconocimiento biométrico.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-user">2</span>
                            <span class="gstep-icon" style="color:#06b6d4;"><i class="fa-solid fa-envelope-circle-check"></i></span>
                            <h4>Verificar tu correo</h4>
                            <p>Revisa tu bandeja de entrada y haz clic en el enlace de verificación que te enviamos. Sin este paso tu cuenta permanecerá inactiva.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-user">3</span>
                            <span class="gstep-icon" style="color:#38bdf8;"><i class="fa-solid fa-right-to-bracket"></i></span>
                            <h4>Iniciar sesión</h4>
                            <p>Ve a "Acceso Usuarios" e ingresa con tu correo y contraseña. También puedes autenticarte con tu foto facial si la registraste.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-user">4</span>
                            <span class="gstep-icon" style="color:#22c55e;"><i class="fa-solid fa-gauge-high"></i></span>
                            <h4>Tu panel principal</h4>
                            <p>En tu dashboard verás un resumen de tu actividad: depósitos recientes, créditos verdes acumulados y notificaciones pendientes.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-user">5</span>
                            <span class="gstep-icon" style="color:#06b6d4;"><i class="fa-solid fa-box-archive"></i></span>
                            <h4>Ver tus depósitos</h4>
                            <p>Navega a "Depósitos" para consultar el historial completo con fecha, peso y contenedor de cada residuo que has registrado en el sistema.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-user">6</span>
                            <span class="gstep-icon" style="color:#f59e0b;"><i class="fa-solid fa-triangle-exclamation"></i></span>
                            <h4>Revisar sanciones</h4>
                            <p>En la sección "Sanciones" puedes ver, detalle y responder cualquier multa asignada a tu cuenta. El sistema te notifica por correo electrónico automáticamente.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-user">7</span>
                            <span class="gstep-icon" style="color:#7c3aed;"><i class="fa-solid fa-id-card"></i></span>
                            <h4>Actualizar perfil</h4>
                            <p>Desde "Mi Perfil" modifica tus datos personales, cambia tu contraseña y actualiza la foto facial para el reconocimiento biométrico en los contenedores.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-user">8</span>
                            <span class="gstep-icon" style="color:#22c55e;"><i class="fa-solid fa-qrcode"></i></span>
                            <h4>Tarjeta digital</h4>
                            <p>Accede a tu tarjeta de identificación con código QR en la sección "Mi Tarjeta". Muéstrala en los contenedores inteligentes para registrar depósitos fácilmente.</p>
                        </div>
                    </div>
                </div>

                <!-- Panel Administrador -->
                <div class="guide-pane" id="guide-admin">
                    <div class="guide-steps-grid">
                        <div class="gstep">
                            <span class="gstep-num gstep-num-admin">1</span>
                            <span class="gstep-icon" style="color:#7c3aed;"><i class="fa-solid fa-shield-halved"></i></span>
                            <h4>Acceder al panel admin</h4>
                            <p>Haz clic en "Acceso Administradores" o ingresa directamente en <code>/web/admin/loginA.php</code> con tus credenciales de administrador.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-admin">2</span>
                            <span class="gstep-icon" style="color:#38bdf8;"><i class="fa-solid fa-user-check"></i></span>
                            <h4>Aprobación de cuenta</h4>
                            <p>Las cuentas admin nuevas requieren aprobación. Un super-administrador deberá validarla desde el módulo "Aprobación de Admins" antes del primer acceso.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-admin">3</span>
                            <span class="gstep-icon" style="color:#06b6d4;"><i class="fa-solid fa-gauge-high"></i></span>
                            <h4>Dashboard administrativo</h4>
                            <p>El dashboard principal muestra estadísticas globales, alertas del sistema, últimos depósitos registrados y estado de los sensores IoT en tiempo real.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-admin">4</span>
                            <span class="gstep-icon" style="color:#22c55e;"><i class="fa-solid fa-microchip"></i></span>
                            <h4>Monitorear sensores / SCADA</h4>
                            <p>Navega a "SCADA / Biomasa" para ver lecturas en tiempo real de temperatura, presión y caudal del biorreactor. Puedes enviar comandos de control directamente.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-admin">5</span>
                            <span class="gstep-icon" style="color:#38bdf8;"><i class="fa-solid fa-users-gear"></i></span>
                            <h4>Gestionar usuarios</h4>
                            <p>En "Usuarios" visualiza, edita, suspende o elimina cuentas del sistema. Consulta el historial individual de depósitos y créditos por cada usuario registrado.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-admin">6</span>
                            <span class="gstep-icon" style="color:#7c3aed;"><i class="fa-solid fa-camera"></i></span>
                            <h4>Capturas vehiculares</h4>
                            <p>En "Vehículos" revisa las capturas de las cámaras ESP32-CAM instaladas en camiones recolectores, con marca de tiempo y registro de ruta en cada toma.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-admin">7</span>
                            <span class="gstep-icon" style="color:#f59e0b;"><i class="fa-solid fa-gavel"></i></span>
                            <h4>Emitir sanciones</h4>
                            <p>Desde "Sanciones" crea y envía multas a usuarios infractores. El sistema notifica automáticamente al usuario por correo y registra el evento con trazabilidad completa.</p>
                        </div>
                        <div class="gstep">
                            <span class="gstep-num gstep-num-admin">8</span>
                            <span class="gstep-icon" style="color:#06b6d4;"><i class="fa-solid fa-file-chart-column"></i></span>
                            <h4>Generar reportes</h4>
                            <p>En "Reportes" exporta informes de operaciones, energía recuperada, residuos procesados por nodo y cumplimiento por rango de fechas personalizado.</p>
                        </div>
                    </div>
                </div>

            </div><!-- /guide-wrap -->
        </div>
    </section>

    <!-- ═══════════ SISTEMA HÍBRIDO BIOMASA + PELTIER ═══════════ -->
    <section class="bio-section" id="bioenergia">
        <div class="container">
            <span class="bio-badge"><i class="fa-solid fa-fire-flame-curved"></i> Primera vez en RD y el Caribe</span>
            <h2 class="section-title">Sistema Híbrido <span style="background:linear-gradient(90deg,#10b981,#06b6d4,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">BIOMASA + Peltier TEG</span></h2>
            <p class="section-sub">
                PRERMI integra por primera vez en República Dominicana y el Caribe la digestión anaerobia de residuos orgánicos con recuperación termoeléctrica mediante módulos Peltier TEG, trazabilidad biométrica por usuario y control SCADA web en tiempo real. 
                <strong>Ninguna plataforma industrial en el país combina estos siete pilares en un solo sistema.</strong>
            </p>

            <div class="real-photo-grid media-distributed">
                <article class="real-photo-card span-8 row-2">
                    <img src="/PRERMI/uploads/images/biomass-plant.jpg" class="img-focus-center" alt="Planta de biomasa para generacion energetica" loading="lazy" onerror="this.onerror=null;this.src='/PRERMI/web/assets/img/illus-proindustria-stand.svg';">
                </article>
                <article class="real-photo-card span-4 row-2">
                    <img src="/PRERMI/uploads/images/hero-industrial.jpg" class="img-focus-left" alt="Infraestructura industrial para conversion energetica" loading="lazy" onerror="this.onerror=null;this.src='/PRERMI/web/assets/img/illus-iot-monitor.svg';">
                </article>
            </div>

            <div class="bio-unique-grid">
                <article class="bio-unique-card">
                    <span class="bio-num">A</span>
                    <h4><i class="fa-solid fa-circle-info" style="color:#06b6d4;"></i> Qué es este sistema híbrido</h4>
                    <p>Es la unión de dos fuentes: BIOMASA (residuos orgánicos) + celdas Peltier TEG (calor residual). Así, la planta genera más energía con el mismo proceso.</p>
                </article>
                <article class="bio-unique-card">
                    <span class="bio-num">B</span>
                    <h4><i class="fa-solid fa-bolt" style="color:#10b981;"></i> Qué gana tu empresa</h4>
                    <p>Menor factura eléctrica, reducción de residuos, trazabilidad completa y tablero web con indicadores operativos y financieros en tiempo real.</p>
                </article>
                <article class="bio-unique-card">
                    <span class="bio-num">C</span>
                    <h4><i class="fa-solid fa-gears" style="color:#7c3aed;"></i> Cómo se implementa</h4>
                    <p>Instalación por etapas: contenedores inteligentes, reactor de biomasa, módulos TEG, sensores IoT y plataforma web para control y reportes.</p>
                </article>
                <article class="bio-unique-card">
                    <span class="bio-num">D</span>
                    <h4><i class="fa-solid fa-chart-line" style="color:#f97316;"></i> Resultado esperado</h4>
                    <p>Ahorro económico mensual medible, operación más eficiente y evidencia digital para auditorías ambientales y decisiones de inversión.</p>
                </article>
            </div>

            <div class="calc-basic">
                <h4><i class="fa-solid fa-calculator"></i> Cómo estima PRERMI el ahorro real (explicación básica)</h4>
                <div class="calc-step-grid">
                    <div class="calc-step">
                        <strong>1) Medimos residuos diarios</strong>
                        <p>El sistema registra cuántos kg de residuo orgánico entra cada día.</p>
                    </div>
                    <div class="calc-step">
                        <strong>2) Estimamos energía de biomasa</strong>
                        <p>Con el rendimiento del reactor, convertimos esos residuos en kWh de forma conservadora, base y optimista.</p>
                    </div>
                    <div class="calc-step">
                        <strong>3) Sumamos energía de celdas Peltier</strong>
                        <p>El calor residual aporta energía adicional sin usar combustible extra.</p>
                    </div>
                    <div class="calc-step">
                        <strong>4) Convertimos kWh en RD$</strong>
                        <p>Multiplicamos por la tarifa eléctrica para mostrar ahorro mensual y anual en dinero.</p>
                    </div>
                </div>
            </div>

            <!-- Escenarios de referencia -->
            <div class="bio-sce-grid">
                <div class="bio-sce-card c1">
                    <div class="sc-tag">🌿 Conservador</div>
                    <div class="sc-kwh">1,044 kWh/mes</div>
                    <div class="sc-rd">RD$ 14,616/mes</div>
                    <div class="sc-anual">200 kg/día · 0.06 m³/kg · 22% efic. · 40 módulos</div>
                </div>
                <div class="bio-sce-card c2">
                    <div class="sc-tag">⚡ Base (activo)</div>
                    <div class="sc-kwh">1,116 kWh/mes</div>
                    <div class="sc-rd">RD$ 15,624/mes</div>
                    <div class="sc-anual">200 kg/día · 0.08 m³/kg · 28% efic. · 40 módulos</div>
                </div>
                <div class="bio-sce-card c3">
                    <div class="sc-tag">🚀 Optimista</div>
                    <div class="sc-kwh">1,278 kWh/mes</div>
                    <div class="sc-rd">RD$ 17,892/mes</div>
                    <div class="sc-anual">200 kg/día · 0.12 m³/kg · 35% efic. · 40 módulos</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTOR TEXTIL ═══════════ -->
    <section class="textil-section" id="textil">
        <div class="container">
            <span class="textil-badge"><i class="fa-solid fa-fire"></i> Aplicación Industrial — Venta de Solución</span>
            <h2 class="section-title">Bioenergía Termoeléctrica para <span style="background:linear-gradient(90deg,#7c3aed,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Empresas Textiles</span></h2>
            <p class="section-sub">
                Las fábricas textiles generan enormes cantidades de calor residual en sus procesos. PRERMI ofrece la instalación de módulos Peltier TEG en los puntos de calor para convertir ese desperdicio térmico en electricidad — <strong>sin combustible adicional, sin piezas móviles, mantenimiento mínimo.</strong>
            </p>

            <div class="real-photo-grid media-distributed">
                <article class="real-photo-card span-6 row-2">
                    <img src="/PRERMI/uploads/images/textile-factory.jpg" class="img-focus-center" alt="Linea de produccion textil" loading="lazy" onerror="this.onerror=null;this.src='/PRERMI/web/assets/img/illus-proindustria-stand.svg';">
                </article>
                <article class="real-photo-card span-6">
                    <img src="/PRERMI/uploads/images/recycling-facility.jpg" class="img-focus-center" alt="Recoleccion y clasificacion de residuos" loading="lazy" onerror="this.onerror=null;this.src='/PRERMI/web/assets/img/illus-iot-monitor.svg';">
                </article>
                <article class="real-photo-card span-6">
                    <img src="/PRERMI/uploads/images/iot-monitoring.jpg" class="img-focus-center" alt="Control digital de procesos textiles" loading="lazy" onerror="this.onerror=null;this.src='/PRERMI/web/assets/img/illus-iot-monitor.svg';">
                </article>
            </div>

            <!-- Fuentes de calor residual textil -->
            <div class="textil-heat-grid">
                <article class="textil-heat-card">
                    <div class="heat-icon">🧵</div>
                    <h5>Teñido y acabado</h5>
                    <span class="heat-temp">80–95°C</span>
                    <p>Las tinas de teñido mantienen temperatura constante durante horas. Ideal para TEG de baja ΛT.</p>
                </article>
                <article class="textil-heat-card">
                    <div class="heat-icon">💨</div>
                    <h5>Vaporizadores</h5>
                    <span class="heat-temp">120–135°C</span>
                    <p>El escape de vapor en autoclaves y vaporizadores industriales genera ΛT altamente aprovechable con TEG de alta eficiencia.</p>
                </article>
                <article class="textil-heat-card">
                    <div class="heat-icon">🔥</div>
                    <h5>Calandrias y rodillos calientes</h5>
                    <span class="heat-temp">140–180°C</span>
                    <p>Superficie de rodillos de prensado con calor continuo. Los TEG pueden instalarse en los soportes metálicos laterales.</p>
                </article>
                <article class="textil-heat-card">
                    <div class="heat-icon">🏭</div>
                    <h5>Calderas industriales</h5>
                    <span class="heat-temp">150–200°C</span>
                    <p>El escape de gases de las calderas es la fuente con mayor potencial térmico. Sistemas de 200+ módulos pueden generar varios kW.</p>
                </article>
                <article class="textil-heat-card">
                    <div class="heat-icon">🧺</div>
                    <h5>Secado de telas</h5>
                    <span class="heat-temp">100–130°C</span>
                    <p>Las cámaras de secado y tenters liberan calor en sus paredes externas, perfectas para TEG de alta temperatura.</p>
                </article>
            </div>

            <!-- ROI por tamaño de planta -->
            <p style="margin-top:1.4rem;font-family:Sora,sans-serif;font-size:.97rem;font-weight:700;"><i class="fa-solid fa-chart-bar" style="color:#7c3aed;"></i> Proyección de ahorro según tamaño de planta</p>
            <div class="textil-roi-grid">
                <div class="textil-roi-card sm">
                    <div class="roi-size">🏭 Planta pequeña</div>
                    <div class="roi-kwh">18,432 kWh/año</div>
                    <div class="roi-rd">RD$ 258,048/año</div>
                    <div class="roi-meta">200 trabajadores · 400 módulos × 3W · 16h/día · ROI ~3.5 años</div>
                </div>
                <div class="textil-roi-card md">
                    <div class="roi-size">🏭🏭 Planta mediana</div>
                    <div class="roi-kwh">57,600 kWh/año</div>
                    <div class="roi-rd">RD$ 806,400/año</div>
                    <div class="roi-meta">800 trabajadores · 1,200 módulos × 5W · 20h/día · ROI ~2.9 años</div>
                </div>
                <div class="textil-roi-card lg">
                    <div class="roi-size">🏭🏭🏭 Planta grande</div>
                    <div class="roi-kwh">172,800 kWh/año</div>
                    <div class="roi-rd">RD$ 2,419,200/año</div>
                    <div class="roi-meta">2,000+ trabajadores · 3,000 módulos × 8W · 24h/día · ROI ~2.2 años</div>
                </div>
            </div>

            <!-- Gráficas de proyección -->
            <div class="row g-3" style="margin-top:1.4rem;">
                <div class="col-lg-7">
                    <div class="textil-chart-box">
                        <div class="tcb-header">
                            <span class="tcb-title">
                                <i class="fa-solid fa-arrow-trend-up" style="color:#7c3aed;"></i>
                                Proyección acumulada de ahorro — RD$
                            </span>
                            <div class="tcb-controls">
                                <button class="tcb-toggle active" id="btnSavAcum" onclick="setSavingsMode('acumulado',this)">Acumulado</button>
                                <button class="tcb-toggle" id="btnSavMens" onclick="setSavingsMode('mensual',this)">Por mes</button>
                            </div>
                        </div>
                        <div class="tcb-kpis">
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#06b6d4">RD$ 258K</span><span class="tcb-kpi-lbl">Pequeña/año</span></div>
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#7c3aed">RD$ 806K</span><span class="tcb-kpi-lbl">Mediana/año</span></div>
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#10b981">RD$ 2.4M</span><span class="tcb-kpi-lbl">Grande/año</span></div>
                        </div>
                        <canvas id="textilSavingsChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="textil-chart-box">
                        <div class="tcb-header">
                            <span class="tcb-title">
                                <i class="fa-solid fa-chart-pie" style="color:#06b6d4;"></i>
                                Distribución por fuente térmica
                            </span>
                        </div>
                        <canvas id="textilHeatChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-3" style="margin-top:.7rem;">
                <div class="col-lg-7">
                    <div class="textil-chart-box">
                        <div class="tcb-header">
                            <span class="tcb-title">
                                <i class="fa-solid fa-chart-column" style="color:#10b981;"></i>
                                Energía anual vs Inversión estimada
                            </span>
                        </div>
                        <div class="tcb-kpis">
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#10b981">18,432 kWh</span><span class="tcb-kpi-lbl">Pequeña/año</span></div>
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#10b981">57,600 kWh</span><span class="tcb-kpi-lbl">Mediana/año</span></div>
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#10b981">172,800 kWh</span><span class="tcb-kpi-lbl">Grande/año</span></div>
                        </div>
                        <canvas id="textilInvestmentChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="textil-chart-box">
                        <div class="tcb-header">
                            <span class="tcb-title">
                                <i class="fa-solid fa-gauge-high" style="color:#f97316;"></i>
                                Tiempo de recuperación (meses)
                            </span>
                        </div>
                        <div class="tcb-kpis">
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#f97316">~42 meses</span><span class="tcb-kpi-lbl">Pequeña</span></div>
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#f97316">~35 meses</span><span class="tcb-kpi-lbl">Mediana</span></div>
                            <div class="tcb-kpi"><span class="tcb-kpi-val" style="color:#10b981">~26 meses</span><span class="tcb-kpi-lbl">Grande</span></div>
                        </div>
                        <canvas id="textilPaybackChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="textil-chart-note">
                Cifras base usadas en las proyecciones: tarifa promedio <strong>RD$ 14.00/kWh</strong>; inversión estimada por planta
                (<strong>RD$ 900,000</strong> pequeña, <strong>RD$ 2,350,000</strong> mediana, <strong>RD$ 5,200,000</strong> grande);
                y retorno calculado con la fórmula <strong>payback = inversión / ahorro mensual</strong>.
            </div>

            <!-- Ventajas clave -->
            <div class="about-grid" style="margin-top:1.4rem;">
                <article class="about-card">
                    <h3><i class="fa-solid fa-lightbulb" style="color:#f59e0b;"></i> ¿Por qué elegir PRERMI TEG?</h3>
                    <ul class="about-points">
                        <li><i class="fa-solid fa-check"></i> <strong>Cero combustible:</strong> aprovecha calor que ya se está generando y desperdiciando.</li>
                        <li><i class="fa-solid fa-check"></i> <strong>Sin piezas móviles:</strong> módulos de estado sólido con vida útil de 20+ años.</li>
                        <li><i class="fa-solid fa-check"></i> <strong>Escalable:</strong> desde 50 hasta 5,000+ módulos según el tamaño de la planta.</li>
                        <li><i class="fa-solid fa-check"></i> <strong>Monitoreo IoT incluido:</strong> dashboard web con datos de generación en tiempo real.</li>
                        <li><i class="fa-solid fa-check"></i> <strong>ROI comprobado:</strong> 2.2 a 3.5 años de recuperación de inversión según tamaño.</li>
                    </ul>
                </article>
                <article class="about-card">
                    <h3><i class="fa-solid fa-industry" style="color:#7c3aed;"></i> Industrias objetivo en RD</h3>
                    <ul class="about-points">
                        <li><i class="fa-solid fa-check"></i> Zonas francas textiles de <strong>Santiago, Bonao, San Pedro de Macorís</strong></li>
                        <li><i class="fa-solid fa-check"></i> Plantas de fabricación de prendas con procesos de vapor</li>
                        <li><i class="fa-solid fa-check"></i> Industrias productoras de telas sintéticas y tejidos técnicos</li>
                        <li><i class="fa-solid fa-check"></i> Lavanderías industriales con calderas</li>
                        <li><i class="fa-solid fa-check"></i> Cualquier industria con procesos térmicos continuos ≥ 80°C</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="contacto">
        <div class="container">
            <div class="contact-title-wrap">
                <h2 class="section-title" style="margin-bottom: 0;">Contacto oficial para la Feria Proindustria</h2>
                <img src="web/assets/img/image.png" alt="Logo Instituto Tecnológico México" class="contact-logo">
            </div>
            <p class="section-sub">Agenda una demostración de PRERMI y recibe una propuesta adaptada a tu industria.</p>

            <div class="contact-card">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-7">
                        <h3 style="font-family: Sora, sans-serif; margin-bottom: 0.7rem;">Programa de Reabastecimiento Energético, Residuos y Monitoreo Inteligente (PRERMI)</h3>
                        <ul class="contact-list">
                            <li><i class="fa-brands fa-instagram"></i> Instagram: <a href="https://instagram.com/institutotecnologicomexico" target="_blank" rel="noopener">@institutotecnologicomexico</a></li>
                            <li><i class="fa-solid fa-phone"></i> Teléfono: <a href="tel:+18095754270">809-575-4270</a></li>
                            <li><i class="fa-solid fa-envelope"></i> Correo: <a href="mailto:institutotecnologicomexico@gmail.com">institutotecnologicomexico@gmail.com</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-5 d-flex justify-content-lg-end">
                        <div style="display: grid; gap: 0.55rem; width: 100%; max-width: 320px;">
                            <a class="btn-main" href="mailto:institutotecnologicomexico@gmail.com"><i class="fa-solid fa-paper-plane"></i> Enviar Correo</a>
                            <a class="btn-outline-soft" href="https://instagram.com/institutotecnologicomexico" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i> Ver Instagram</a>
                            <a class="btn-alt" href="tel:+18095754270"><i class="fa-solid fa-phone"></i> Llamar Ahora</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container d-flex flex-column gap-2">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                <span>2026 PRERMI | Programa de Reabastecimiento Energético, Residuos y Monitoreo Inteligente.</span>
                <span><a href="web/login.php">Plataforma</a> | <a href="web/admin/loginA.php">Admin</a> | <a href="api/">API</a></span>
            </div>
            <div style="font-size:.83rem;color:#436282;">
                Créditos técnicos: jóvenes del área técnica de 5toA de Mecatrónica, Instituto Tecnológico México.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function animateCounters() {
            const counters = document.querySelectorAll('.counter[data-target]');
            counters.forEach((el) => {
                const target = Number(el.dataset.target || 0);
                const duration = 1400;
                const start = performance.now();

                function step(now) {
                    const progress = Math.min((now - start) / duration, 1);
                    el.textContent = Math.floor(target * progress).toLocaleString('es-DO');
                    if (progress < 1) requestAnimationFrame(step);
                }

                requestAnimationFrame(step);
            });
        }

        function renderCharts() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const labelColor = isDark ? '#cbd5e1' : '#2f557a';
            const gridColor = isDark ? 'rgba(148, 163, 184, 0.22)' : 'rgba(90, 147, 194, 0.22)';
            const bgCanvas = isDark ? 'rgba(15, 23, 42, 0.65)' : 'rgba(255, 255, 255, 0.95)';

            const cashflowChart = document.getElementById('cashflowChart');
            if (cashflowChart) {
            const prevCashflow = Chart.getChart(cashflowChart);
            if (prevCashflow) prevCashflow.destroy();
            new Chart(cashflowChart, {
                type: 'line',
                data: {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    datasets: [
                        {
                            label: 'Ahorro acumulado (RD$)',
                            data: [120000, 265000, 430000, 605000, 790000, 980000, 1180000, 1390000, 1610000, 1840000, 2080000, 2330000],
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.2)',
                            fill: true,
                            tension: 0.28,
                            borderWidth: 2
                        },
                        {
                            label: 'Costo operativo (RD$)',
                            data: [190000, 188000, 186000, 184000, 183000, 182000, 181000, 180000, 179000, 178000, 177000, 176000],
                            borderColor: '#38bdf8',
                            backgroundColor: 'rgba(56, 189, 248, 0.12)',
                            fill: false,
                            tension: 0.2,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    backgroundColor: bgCanvas,
                    plugins: {
                        legend: { labels: { color: labelColor } }
                    },
                    scales: {
                        x: { ticks: { color: labelColor }, grid: { color: gridColor } },
                        y: {
                            ticks: {
                                color: labelColor,
                                callback: function(v) { return 'RD$ ' + Number(v).toLocaleString('es-DO'); }
                            },
                            grid: { color: gridColor }
                        }
                    }
                }
            });
            }

            const valueChart = document.getElementById('valueChart');
            if (valueChart) {
            const prevValue = Chart.getChart(valueChart);
            if (prevValue) prevValue.destroy();
            new Chart(valueChart, {
                type: 'doughnut',
                data: {
                    labels: ['Ahorro eléctrico', 'Créditos verdes', 'Costos evitados'],
                    datasets: [{
                        data: [48, 27, 25],
                        backgroundColor: ['#38bdf8', '#06b6d4', '#7c3aed'],
                        borderColor: '#0a1d38',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    backgroundColor: bgCanvas,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: labelColor, boxWidth: 14 } }
                    }
                }
            });
            }

            const opsChart = document.getElementById('opsChart');
            if (opsChart) {
            const prevOps = Chart.getChart(opsChart);
            if (prevOps) prevOps.destroy();
            new Chart(opsChart, {
                type: 'bar',
                data: {
                    labels: ['Planta Norte', 'Planta Este', 'Planta Sur', 'Centro Logístico'],
                    datasets: [
                        {
                            label: 'Residuos procesados (Ton)',
                            data: [42, 31, 38, 26],
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderRadius: 8
                        },
                        {
                            label: 'Energía recuperada (MWh)',
                            data: [13, 9, 11, 8],
                            backgroundColor: 'rgba(124, 58, 237, 0.7)',
                            borderRadius: 8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    backgroundColor: bgCanvas,
                    plugins: {
                        legend: { labels: { color: labelColor } }
                    },
                    scales: {
                        x: { ticks: { color: labelColor }, grid: { color: gridColor } },
                        y: { ticks: { color: labelColor }, grid: { color: gridColor } }
                    }
                }
            });
            }
        }

        function ensureChartJsReady(done) {
            if (typeof Chart !== 'undefined') {
                done();
                return;
            }
            const fallback = document.createElement('script');
            fallback.src = 'https://unpkg.com/chart.js@4.4.1/dist/chart.umd.min.js';
            fallback.onload = done;
            document.head.appendChild(fallback);
        }

        document.querySelectorAll('a[href^="#"]').forEach((a) => {
            a.addEventListener('click', (e) => {
                const id = a.getAttribute('href');
                const el = document.querySelector(id);
                if (!el) return;
                e.preventDefault();
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        function switchGuide(tab) {
            const paneUser  = document.getElementById('guide-user');
            const paneAdmin = document.getElementById('guide-admin');
            const btnUser   = document.getElementById('btnGuideUser');
            const btnAdmin  = document.getElementById('btnGuideAdmin');
            if (tab === 'user') {
                paneUser.classList.add('guide-pane-show');
                paneAdmin.classList.remove('guide-pane-show');
                btnUser.classList.add('gtab-user-active');
                btnAdmin.classList.remove('gtab-admin-active');
            } else {
                paneAdmin.classList.add('guide-pane-show');
                paneUser.classList.remove('guide-pane-show');
                btnAdmin.classList.add('gtab-admin-active');
                btnUser.classList.remove('gtab-user-active');
            }
        }

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateCounters();
                    obs.disconnect();
                }
            });
        }, { threshold: 0.35 });

        const impacto = document.getElementById('impacto');
        if (impacto) observer.observe(impacto);

        document.addEventListener('DOMContentLoaded', () => {
            ensureChartJsReady(() => {
                renderCharts();
                if (typeof renderTextilCharts === 'function') {
                    renderTextilCharts();
                }
            });
        });
    </script>
    <script>
    // ── Plugin: Texto central animado en doughnut ───────────────────────────────
    const prermiCenterTextPlugin = {
        id: 'prermiCenterText',
        afterDraw(chart) {
            if (chart.config.type !== 'doughnut') return;
            const { ctx, chartArea: { left, top, width, height } } = chart;
            const cx = left + width / 2;
            const cy = top + height / 2;
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const mainC = isDark ? '#e2e8f0' : '#13263f';
            const subC  = isDark ? '#94a3b8'  : '#4d6e8f';
            const active = chart.getActiveElements();
            ctx.save();
            if (active.length > 0) {
                const idx = active[0].index;
                const val = chart.data.datasets[0].data[idx];
                const lbl = chart.data.labels[idx];
                const col = chart.data.datasets[0].backgroundColor[idx];
                ctx.font = 'bold 1.25rem Sora, sans-serif';
                ctx.fillStyle = col;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(val + '%', cx, cy - 12);
                ctx.font = '600 0.67rem Manrope, sans-serif';
                ctx.fillStyle = subC;
                const words = lbl.split(' ');
                let line = '', y = cy + 9;
                for (const w of words) {
                    const test = line ? line + ' ' + w : w;
                    if (ctx.measureText(test).width > width * 0.4 && line) {
                        ctx.fillText(line, cx, y); line = w; y += 13;
                    } else { line = test; }
                }
                ctx.fillText(line, cx, y);
            } else {
                ctx.font = 'bold 1.05rem Sora, sans-serif';
                ctx.fillStyle = mainC;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('5 fuentes', cx, cy - 9);
                ctx.font = '600 0.67rem Manrope, sans-serif';
                ctx.fillStyle = subC;
                ctx.fillText('Toca un segmento', cx, cy + 9);
            }
            ctx.restore();
        }
    };
    Chart.register(prermiCenterTextPlugin);

    // ── Helper: destruir instancia previa ──────────────────────────────────────
    function destroyChartById(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return;
        const current = Chart.getChart(canvas);
        if (current) current.destroy();
    }

    // ── Helper: opciones de tema dinámicas ─────────────────────────────────────
    function _tcbTheme() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        return {
            isDark,
            axis:    isDark ? '#94a3b8'  : '#2f557a',
            grid:    isDark ? 'rgba(148,163,184,.18)' : 'rgba(90,147,194,.15)',
            border:  isDark ? '#0f172a'  : '#ffffff',
            bgCanvas: isDark ? 'rgba(15,23,42,.65)' : 'rgba(255,255,255,.95)',
            ttBg:    isDark ? 'rgba(8,18,38,.97)'    : 'rgba(255,255,255,.98)',
            ttBdr:   isDark ? 'rgba(56,189,248,.45)' : 'rgba(56,189,248,.5)',
            ttTitle: isDark ? '#e2e8f0'  : '#0f2440',
            ttBody:  isDark ? '#94a3b8'  : '#4d6e8f',
        };
    }

    // ── Estado del toggle de ahorro ────────────────────────────────────────────
    let _savingsMode = 'acumulado';
    function setSavingsMode(mode, btn) {
        _savingsMode = mode;
        document.querySelectorAll('#btnSavAcum,#btnSavMens').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        destroyChartById('textilSavingsChart');
        _renderSavingsChart();
    }

    // ── Constantes de negocio ──────────────────────────────────────────────────
    const TDATA = {
        meses: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
        ahorroAnual:  { pequena: 258048,  mediana: 806400,   grande: 2419200 },
        inversion:    { pequena: 900000,  mediana: 2350000,  grande: 5200000 },
        energiaAnual: { pequena: 18432,   mediana: 57600,    grande: 172800  },
        get ahorroMensual() {
            return {
                pequena: this.ahorroAnual.pequena / 12,
                mediana: this.ahorroAnual.mediana / 12,
                grande:  this.ahorroAnual.grande  / 12,
            };
        },
        get payback() {
            const am = this.ahorroMensual;
            return {
                pequena: +(this.inversion.pequena / am.pequena).toFixed(1),
                mediana: +(this.inversion.mediana / am.mediana).toFixed(1),
                grande:  +(this.inversion.grande  / am.grande ).toFixed(1),
            };
        }
    };

    // ── Opciones compartidas de tooltip ───────────────────────────────────────
    function _tooltipOpts(T, extraCallbacks) {
        return {
            backgroundColor: T.ttBg,
            borderColor: T.ttBdr, borderWidth: 1,
            titleColor: T.ttTitle, bodyColor: T.ttBody,
            padding: 13, cornerRadius: 12, caretSize: 7,
            titleFont: { family: 'Sora, sans-serif', weight: '700', size: 12 },
            bodyFont: { family: 'Manrope, sans-serif', size: 11 },
            callbacks: extraCallbacks,
        };
    }

    // ── GRÁFICA 1: Proyección de ahorro acumulado / mensual ────────────────────
    function _renderSavingsChart() {
        const canvas = document.getElementById('textilSavingsChart');
        if (!canvas) return;
        const T = _tcbTheme();
        const am = TDATA.ahorroMensual;
        const isAcum = _savingsMode === 'acumulado';

        const mkDataset = (label, val, color, alpha) => ({
            label,
            data: TDATA.meses.map((_,i) => +(isAcum ? val*(i+1) : val).toFixed(0)),
            borderColor: color,
            backgroundColor: `rgba(${alpha},.1)`,
            fill: true, tension: .42, borderWidth: 2.5,
            pointRadius: 4, pointHoverRadius: 9,
            pointBackgroundColor: color,
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: color,
            pointHoverBorderWidth: 2.5,
        });

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: TDATA.meses,
                datasets: [
                    mkDataset('Planta Pequeña — 200 trab.', am.pequena, '#06b6d4', '6,182,212'),
                    mkDataset('Planta Mediana — 800 trab.', am.mediana, '#7c3aed', '124,58,237'),
                    mkDataset('Planta Grande — 2000+ trab.',am.grande,  '#10b981', '16,185,129'),
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: true, aspectRatio: 2.1,
                animation: { duration: 950, easing: 'easeInOutQuart' },
                interaction: { mode: 'index', intersect: false },
                backgroundColor: T.bgCanvas,
                plugins: {
                    legend: { labels: { color: T.axis, font: { size: 11 }, usePointStyle: true, pointStyleWidth: 12, boxHeight: 8 } },
                    tooltip: _tooltipOpts(T, {
                        title: ctx => ctx[0].label + (isAcum ? ' — acumulado' : ' — mensual'),
                        label: ctx => ` ${ctx.dataset.label}: RD$ ${ctx.parsed.y.toLocaleString('es-DO')}`,
                    }),
                },
                scales: {
                    x: { ticks: { color: T.axis, font: { size: 11 } }, grid: { color: T.grid }, border: { color: T.grid } },
                    y: {
                        ticks: { color: T.axis, font: { size: 10 }, callback: v => 'RD$ ' + (v/1000).toFixed(0) + 'K' },
                        grid: { color: T.grid }, border: { color: T.grid },
                        title: { display: true, text: isAcum ? 'RD$ acumulado' : 'RD$ por mes', color: T.axis, font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ── GRÁFICA 2: Distribución por fuente térmica (Doughnut) ──────────────────
    function _renderHeatChart() {
        const canvas = document.getElementById('textilHeatChart');
        if (!canvas) return;
        const T = _tcbTheme();
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Biomasa orgánica','Textiles — teñido/secado','Calderas industriales','Vaporizadores','Otros procesos'],
                datasets: [{
                    data: [28, 24, 21, 15, 12],
                    backgroundColor: ['#10b981','#06b6d4','#7c3aed','#f97316','#38bdf8'],
                    hoverBackgroundColor: ['#34d399','#22d3ee','#a78bfa','#fb923c','#7dd3fc'],
                    borderColor: T.border, borderWidth: 2.5,
                    hoverOffset: 16,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true, aspectRatio: 1.55,
                cutout: '62%',
                animation: { animateRotate: true, animateScale: true, duration: 1100, easing: 'easeInOutBack' },
                backgroundColor: T.bgCanvas,
                plugins: {
                    prermiCenterText: {},
                    legend: { position: 'bottom', labels: { color: T.axis, boxWidth: 11, font: { size: 10.5 }, usePointStyle: true, pointStyleWidth: 11, padding: 9 } },
                    tooltip: _tooltipOpts(T, {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed}% del potencial térmico`
                    }),
                }
            }
        });
    }

    // ── GRÁFICA 3: Energía anual vs Inversión ──────────────────────────────────
    function _renderInvestmentChart() {
        const canvas = document.getElementById('textilInvestmentChart');
        if (!canvas) return;
        const T = _tcbTheme();
        const D = TDATA;
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: ['Planta pequeña','Planta mediana','Planta grande'],
                datasets: [
                    {
                        label: 'Energía anual (kWh)',
                        data: [D.energiaAnual.pequena, D.energiaAnual.mediana, D.energiaAnual.grande],
                        yAxisID: 'yKwh',
                        backgroundColor: ['rgba(16,185,129,.68)','rgba(16,185,129,.82)','rgba(16,185,129,.97)'],
                        hoverBackgroundColor: '#10b981',
                        borderRadius: { topLeft: 10, topRight: 10 },
                        borderSkipped: false,
                        barPercentage: 0.48,
                    },
                    {
                        label: 'Inversión estimada (RD$)',
                        data: [D.inversion.pequena, D.inversion.mediana, D.inversion.grande],
                        yAxisID: 'yRD',
                        backgroundColor: ['rgba(124,58,237,.6)','rgba(124,58,237,.76)','rgba(124,58,237,.94)'],
                        hoverBackgroundColor: '#7c3aed',
                        borderRadius: { topLeft: 10, topRight: 10 },
                        borderSkipped: false,
                        barPercentage: 0.48,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: true, aspectRatio: 2.1,
                animation: { duration: 950, easing: 'easeOutQuart' },
                interaction: { mode: 'index', intersect: false },
                backgroundColor: T.bgCanvas,
                plugins: {
                    legend: { labels: { color: T.axis, font: { size: 11 }, usePointStyle: true, pointStyleWidth: 12, boxHeight: 8 } },
                    tooltip: _tooltipOpts(T, {
                        label: ctx => ctx.datasetIndex === 0
                            ? ` Energía: ${ctx.parsed.y.toLocaleString('es-DO')} kWh/año`
                            : ` Inversión: RD$ ${ctx.parsed.y.toLocaleString('es-DO')}`,
                    }),
                },
                scales: {
                    x: { ticks: { color: T.axis }, grid: { color: T.grid }, border: { color: T.grid } },
                    yKwh: {
                        type: 'linear', position: 'left',
                        ticks: { color: '#10b981', font: { size: 10 }, callback: v => v.toLocaleString('es-DO') + ' kWh' },
                        grid: { color: T.grid }, border: { color: T.grid }
                    },
                    yRD: {
                        type: 'linear', position: 'right',
                        ticks: { color: '#7c3aed', font: { size: 10 }, callback: v => 'RD$ ' + (v/1000).toFixed(0) + 'K' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    // ── GRÁFICA 4: Tiempo de recuperación (línea) ──────────────────────────────
    function _renderPaybackChart() {
        const canvas = document.getElementById('textilPaybackChart');
        if (!canvas) return;
        const T = _tcbTheme();
        const pb = TDATA.payback;
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: ['Planta pequeña','Planta mediana','Planta grande'],
                datasets: [
                    {
                        label: 'Meses para recuperar inversión',
                        data: [pb.pequena, pb.mediana, pb.grande],
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249,115,22,.12)',
                        pointBackgroundColor: '#f97316',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#f97316',
                        pointHoverBorderWidth: 2.5,
                        pointRadius: 8, pointHoverRadius: 13,
                        pointStyle: 'circle',
                        fill: true, tension: .3, borderWidth: 2.5,
                    },
                    {
                        label: 'Rentabilidad plena — referencia',
                        data: [pb.pequena + 1, pb.mediana + 1, pb.grande + 1],
                        borderColor: '#10b981',
                        borderDash: [7, 5],
                        backgroundColor: 'transparent',
                        pointBackgroundColor: '#10b981',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#10b981',
                        pointHoverBorderWidth: 2,
                        pointRadius: 4, pointHoverRadius: 8,
                        fill: false, tension: .2, borderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: true, aspectRatio: 1.55,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                interaction: { mode: 'index', intersect: false },
                backgroundColor: T.bgCanvas,
                plugins: {
                    legend: { labels: { color: T.axis, font: { size: 11 }, usePointStyle: true, pointStyleWidth: 12, boxHeight: 8 } },
                    tooltip: _tooltipOpts(T, {
                        label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)} meses`,
                        afterBody: ctx => {
                            if (ctx[0] && ctx[0].datasetIndex === 0) {
                                const idx = ctx[0].dataIndex;
                                const anios = (ctx[0].parsed.y / 12).toFixed(1);
                                return [`  → Equivale a ${anios} años de retorno`];
                            }
                            return [];
                        }
                    }),
                },
                scales: {
                    x: { ticks: { color: T.axis }, grid: { color: T.grid }, border: { color: T.grid } },
                    y: {
                        ticks: { color: '#f97316', font: { size: 10 }, callback: v => v + ' meses' },
                        grid: { color: T.grid }, border: { color: T.grid },
                        title: { display: true, text: 'Plazo de recuperación', color: T.axis, font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ── Punto de entrada principal ─────────────────────────────────────────────
    function renderTextilCharts() {
        destroyChartById('textilSavingsChart');
        destroyChartById('textilHeatChart');
        destroyChartById('textilInvestmentChart');
        destroyChartById('textilPaybackChart');
        _renderSavingsChart();
        _renderHeatChart();
        _renderInvestmentChart();
        _renderPaybackChart();
    }

    const themeObserver = new MutationObserver((changes) => {
        for (const change of changes) {
            if (change.type === 'attributes' && change.attributeName === 'data-theme') {
                ensureChartJsReady(() => {
                    renderCharts();
                    renderTextilCharts();
                });
                break;
            }
        }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    </script>
    <script src="web/assets/js/theme.js"></script>
</body>
</html>
