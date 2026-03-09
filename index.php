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
        }

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
            .about-grid {
                grid-template-columns: 1fr;
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
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <nav class="navbar navbar-expand-lg navbar-prermi">
        <div class="container">
            <a class="brand-wrap" href="#inicio">
                <img src="web/assets/img/logo-prermi-main.svg" alt="Logo PRERMI">
                <span class="brand-name">PRERMI<span class="brand-sub">Feria Proindustria</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="#sobre-nosotros">Sobre nosotros</a></li>
                    <li class="nav-item"><a class="nav-link" href="web/register.php">Registrarse</a></li>
                    <li class="nav-item"><a class="nav-link" href="#impacto">Impacto</a></li>
                    <li class="nav-item"><a class="nav-link" href="#analitica">Analítica</a></li>
                    <li class="nav-item"><a class="nav-link" href="#acceso">Acceso</a></li>
                    <li class="nav-item">
                        <a class="nav-link contact-nav-link" href="#contacto">
                            <img src="web/assets/img/image.png" alt="Logo Instituto Tecnológico México" class="contact-mini-logo">
                            Contacto
                        </a>
                    </li>
                    <li class="nav-item ms-lg-1"><a href="web/register.php" class="btn-outline-soft">Crear Cuenta</a></li>
                    <li class="nav-item ms-lg-2"><a href="web/login.php" class="btn-main">Entrar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero" id="inicio">
        <div class="container">
            <span class="hero-tag fade-up"><i class="fa-solid fa-cubes-stacked"></i> Solución oficial para Feria Proindustria</span>
            <h1 class="hero-title fade-up delay-1">Programa de Reabastecimiento Energético, Residuos y Monitoreo Inteligente (PRERMI)</h1>
            <p class="hero-lead fade-up delay-2">
                Fusionamos la operación de campo, el monitoreo IoT, la trazabilidad por usuario y la inteligencia de datos
                para convertir residuos en energía útil, medible y escalable en tiempo real.
            </p>
            <div class="hero-actions fade-up delay-3">
                <a class="btn-outline-soft" href="web/register.php"><i class="fa-solid fa-user-plus"></i> Registrarse</a>
                <a class="btn-main" href="web/login.php"><i class="fa-solid fa-gauge-high"></i> Acceso Usuarios</a>
                <a class="btn-alt" href="web/admin/loginA.php"><i class="fa-solid fa-shield-halved"></i> Acceso Administradores</a>
                <a class="btn-outline-soft" href="#contacto"><i class="fa-solid fa-handshake"></i> Agendar Reunión</a>
            </div>

            <div class="hero-art fade-up delay-3">
                <img src="web/assets/img/illus-hero-energy.svg" alt="Ilustración de energía circular PRERMI">
            </div>

            <div class="quick-stats">
                <div class="quick-card">
                    <div class="lbl">Ahorro operativo anual</div>
                    <div class="num">RD$ 2.9M</div>
                </div>
                <div class="quick-card">
                    <div class="lbl">ROI estimado</div>
                    <div class="num">14.8 meses</div>
                </div>
                <div class="quick-card">
                    <div class="lbl">Reducción huella</div>
                    <div class="num">41%</div>
                </div>
                <div class="quick-card">
                    <div class="lbl">Eventos trazables</div>
                    <div class="num">100%</div>
                </div>
            </div>
        </div>
    </header>

    <section class="section" id="sobre-nosotros">
        <div class="container">
            <h2 class="section-title">SOBRE NOSOTROS</h2>
            <p class="section-sub">PRERMI es una iniciativa que transforma residuos en energía útil mediante tecnología aplicada, trazabilidad digital y monitoreo continuo para apoyar operaciones industriales sostenibles.</p>

            <div class="about-grid">
                <article class="about-card">
                    <h3><i class="fa-solid fa-circle-info"></i> ¿Qué es PRERMI?</h3>
                    <p>
                        El <strong>Programa de Reabastecimiento Energético, Residuos y Monitoreo Inteligente (PRERMI)</strong>
                        integra sensores IoT, analítica de datos y control operativo para convertir residuos en valor energético.
                        Su enfoque combina eficiencia, sostenibilidad y evidencia técnica para la toma de decisiones en tiempo real.
                    </p>
                </article>

                <article class="about-card">
                    <h3><i class="fa-solid fa-layer-group"></i> Rasgos Generales Integrados</h3>
                    <ul class="about-points">
                        <li><i class="fa-solid fa-check"></i> Monitoreo de depósitos y eventos con trazabilidad por usuario.</li>
                        <li><i class="fa-solid fa-check"></i> Visualización de indicadores de energía, costos y rendimiento.</li>
                        <li><i class="fa-solid fa-check"></i> Control administrativo para seguimiento operativo y cumplimiento.</li>
                        <li><i class="fa-solid fa-check"></i> Plataforma unificada para usuarios y administradores.</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="impacto">
        <div class="container">
            <h2 class="section-title">Información puntual para decisiones industriales</h2>
            <p class="section-sub">PRERMI presenta indicadores accionables para operaciones, sostenibilidad, cumplimiento y retorno económico.</p>

            <div class="metric-grid">
                <article class="metric-card">
                    <span class="icon-box"><i class="fa-solid fa-bolt"></i></span>
                    <h3 class="counter" data-target="1240">0</h3>
                    <p>kWh mensuales promedio recuperados</p>
                </article>
                <article class="metric-card">
                    <span class="icon-box"><i class="fa-solid fa-coins"></i></span>
                    <h3 class="counter" data-target="80600">0</h3>
                    <p>RD$ de valor económico mensual</p>
                </article>
                <article class="metric-card">
                    <span class="icon-box"><i class="fa-solid fa-recycle"></i></span>
                    <h3 class="counter" data-target="38">0</h3>
                    <p>Toneladas/mes de residuos valorizados</p>
                </article>
                <article class="metric-card">
                    <span class="icon-box"><i class="fa-solid fa-user-check"></i></span>
                    <h3 class="counter" data-target="97">0</h3>
                    <p>% de validaciones biométrica/usuario correctas</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="analitica">
        <div class="container">
            <h2 class="section-title">Analítica integrada: energía, costos y desempeño</h2>
            <p class="section-sub">Panel combinado para presentar en la feria: lectura ejecutiva y trazabilidad operativa en una sola vista.</p>

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

            <div class="illustration-row">
                <article class="illustration-card">
                    <img src="web/assets/img/illus-iot-monitor.svg" alt="Ilustración monitoreo IoT y biometría">
                </article>
                <article class="illustration-card">
                    <img src="web/assets/img/illus-proindustria-stand.svg" alt="Ilustración de stand para la Feria Proindustria">
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="acceso">
        <div class="container">
            <h2 class="section-title">Acceso de plataforma combinado</h2>
            <p class="section-sub">Integración del flujo de acceso de usuarios y administradores en una misma interfaz inicial.</p>

            <div class="access-grid">
                <article class="access-card access-user">
                    <span class="access-icon"><i class="fa-solid fa-user"></i></span>
                    <h3 style="font-family: Sora, sans-serif;">Portal de Usuarios</h3>
                    <p style="color: var(--muted);">Consulta depósitos, sanciones, historial y trazabilidad individual de forma segura.</p>
                    <a class="btn-main" href="web/login.php"><i class="fa-solid fa-right-to-bracket"></i> Ingresar como Usuario</a>
                </article>
                <article class="access-card access-admin">
                    <span class="access-icon"><i class="fa-solid fa-user-shield"></i></span>
                    <h3 style="font-family: Sora, sans-serif;">Panel Administrativo</h3>
                    <p style="color: var(--muted);">Monitorea capturas, depósitos registrados, sanciones y control operativo en tiempo real.</p>
                    <a class="btn-alt" href="web/admin/loginA.php"><i class="fa-solid fa-lock"></i> Ingresar como Admin</a>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="proposito">
        <div class="container">
            <h2 class="section-title">Propósito estratégico PRERMI</h2>
            <p class="section-sub">Marco institucional para la evolución de ciudades limpias, seguras y sostenibles.</p>

            <div class="purpose-grid">
                <article class="purpose-card">
                    <span class="purpose-tag">🌱</span>
                    <h3><i class="fa-solid fa-bullseye"></i> Misión</h3>
                    <p>Transformar la gestión urbana mediante la tecnología, promoviendo el reciclaje, la energía renovable y el monitoreo eficiente de los servicios públicos para crear ciudades más limpias, seguras y sostenibles.</p>
                </article>
                <article class="purpose-card">
                    <span class="purpose-tag">🔭</span>
                    <h3><i class="fa-solid fa-eye"></i> Visión</h3>
                    <p>Convertirse en el sistema urbano más innovador y sostenible del país, donde los residuos se conviertan en energía, el tránsito sea más eficiente y los ciudadanos participen activamente en la protección del medio ambiente.</p>
                </article>
                <article class="purpose-card">
                    <span class="purpose-tag">🤝</span>
                    <h3><i class="fa-solid fa-gem"></i> Valores Clave</h3>
                    <p>Sostenibilidad, innovación, transparencia, eficiencia, responsabilidad social, seguridad, integridad, colaboración y compromiso tecnológico.</p>
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
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>2026 PRERMI | Programa de Reabastecimiento Energético, Residuos y Monitoreo Inteligente.</span>
            <span><a href="web/login.php">Plataforma</a> | <a href="web/admin/loginA.php">Admin</a> | <a href="api/">API</a></span>
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
            const labelColor = '#2f557a';
            const gridColor = 'rgba(90, 147, 194, 0.22)';

            new Chart(document.getElementById('cashflowChart'), {
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

            new Chart(document.getElementById('valueChart'), {
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
                    plugins: {
                        legend: { position: 'bottom', labels: { color: labelColor, boxWidth: 14 } }
                    }
                }
            });

            new Chart(document.getElementById('opsChart'), {
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
                            label: 'Energia recuperada (MWh)',
                            data: [13, 9, 11, 8],
                            backgroundColor: 'rgba(124, 58, 237, 0.7)',
                            borderRadius: 8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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

        document.querySelectorAll('a[href^="#"]').forEach((a) => {
            a.addEventListener('click', (e) => {
                const id = a.getAttribute('href');
                const el = document.querySelector(id);
                if (!el) return;
                e.preventDefault();
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

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

        renderCharts();
    </script>
</body>
</html>
