<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magazine AI System API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        
        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }
        
        body {
            margin: 0;
            background: #fafafa;
        }
        
        .swagger-ui .topbar {
            background-color: #2c3e50;
        }
        
        .swagger-ui .info .title {
            color: #2c3e50;
        }
        
        .swagger-ui .opblock.opblock-get .opblock-summary-method {
            background-color: #3498db;
        }
        
        .swagger-ui .opblock.opblock-post .opblock-summary-method {
            background-color: #2ecc71;
        }
        
        .swagger-ui .opblock.opblock-put .opblock-summary-method {
            background-color: #f39c12;
        }
        
        .swagger-ui .opblock.opblock-delete .opblock-summary-method {
            background-color: #e74c3c;
        }
        
        .swagger-ui .opblock.opblock-patch .opblock-summary-method {
            background-color: #9b59b6;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{{ asset('docs/swagger.yaml') }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                defaultModelRendering: 'model',
                displayRequestDuration: true,
                docExpansion: 'list',
                filter: true,
                showExtensions: true,
                showCommonExtensions: true,
                withCredentials: true
            });
            
            window.ui = ui;
        };
    </script>
</body>
</html> 