<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iron Gym — API Reference</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.17.14/swagger-ui.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            background: #f4f6f9;
        }

        .ig-topbar {
            background: #1A1A2E;
            color: #fff;
            padding: 0 1.5rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,.35);
        }

        .ig-topbar-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: .02em;
        }

        .ig-topbar-brand svg {
            flex-shrink: 0;
        }

        .ig-topbar-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .ig-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .9rem;
            border-radius: 6px;
            font-size: .85rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: opacity .15s;
        }

        .ig-btn:hover { opacity: .85; }

        .ig-btn-outline {
            background: transparent;
            border: 1px solid rgba(255,255,255,.35);
            color: #fff;
        }

        .ig-btn-accent {
            background: #E85D04;
            color: #fff;
        }

        #swagger-wrapper {
            padding: 1rem 0 2rem;
        }

        /* Swagger UI overrides */
        .swagger-ui .topbar { display: none; }

        .swagger-ui .info .title {
            font-family: 'Inter', system-ui, sans-serif;
        }

        .swagger-ui .opblock.opblock-get .opblock-summary-method { background: #0D7EEA; }
        .swagger-ui .opblock.opblock-post .opblock-summary-method { background: #18A06B; }
        .swagger-ui .opblock.opblock-delete .opblock-summary-method { background: #E85D04; }

        .swagger-ui .btn.authorize { border-color: #E85D04; color: #E85D04; }
        .swagger-ui .btn.authorize svg { fill: #E85D04; }
        .swagger-ui .btn.authorize:hover { background: rgba(232,93,4,.07); }

        .swagger-ui select,
        .swagger-ui input[type=text],
        .swagger-ui textarea {
            font-family: 'Inter', system-ui, sans-serif;
        }
    </style>
</head>
<body>

<header class="ig-topbar">
    <div class="ig-topbar-brand">
        <svg width="26" height="26" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="2" y="13" width="6" height="6" rx="2" fill="#E85D04"/>
            <rect x="24" y="13" width="6" height="6" rx="2" fill="#E85D04"/>
            <rect x="7" y="11" width="4" height="10" rx="2" fill="#E85D04"/>
            <rect x="21" y="11" width="4" height="10" rx="2" fill="#E85D04"/>
            <rect x="11" y="15" width="10" height="2" rx="1" fill="#fff"/>
        </svg>
        <span>Iron Gym — API Reference</span>
    </div>

    <div class="ig-topbar-actions">
        <a href="{{ route('backoffice.settings.api-docs.yaml') }}"
           class="ig-btn ig-btn-outline"
           download="iron-gym-openapi.yaml">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 2a1 1 0 011 1v9.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V3a1 1 0 011-1z"/>
                <path d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
            </svg>
            Scarica YAML
        </a>
        <a href="{{ route('backoffice.settings.index') }}" class="ig-btn ig-btn-outline">
            ← Impostazioni
        </a>
    </div>
</header>

<div id="swagger-wrapper">
    <div id="swagger-ui"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.17.14/swagger-ui-bundle.min.js"></script>
<script>
    SwaggerUIBundle({
        url: '{{ route('backoffice.settings.api-docs.yaml') }}',
        dom_id: '#swagger-ui',
        presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
        layout: 'BaseLayout',
        deepLinking: true,
        defaultModelsExpandDepth: 1,
        defaultModelExpandDepth: 2,
        docExpansion: 'list',
        filter: true,
        tryItOutEnabled: false,
        persistAuthorization: true,
    });
</script>

</body>
</html>
