<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MercadoLibre OAuth Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .response {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 12px;
        }
        .response.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .response.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
        }
        .tab.active {
            background-color: white;
            border-bottom: 1px solid white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>MercadoLibre OAuth Token Exchange</h1>
        
        <div class="tabs">
            <div class="tab active" onclick="switchTab('service')">Service Method</div>
            <div class="tab" onclick="switchTab('direct')">Direct cURL Method</div>
        </div>

        <div id="service-tab" class="tab-content active">
            <form id="oauth-form-service" action="{{ route('oauth.exchange-token') }}" method="POST">
                @csrf
                <button type="submit" class="btn">Exchange Token (Service)</button>
            </form>
        </div>

        <div id="direct-tab" class="tab-content">
            <form id="oauth-form-direct" action="{{ route('oauth.exchange-token-direct') }}" method="POST">
                @csrf
                <button type="submit" class="btn">Exchange Token (Direct cURL)</button>
            </form>
        </div>

        <div id="response" class="response" style="display: none;"></div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // document.getElementById('oauth-form-service').addEventListener('submit', function(e) {
        //     e.preventDefault();
        //     submitForm('/oauth/exchange-token', this);
        // });

        // document.getElementById('oauth-form-direct').addEventListener('submit', function(e) {
        //     e.preventDefault();
        //     submitForm('/oauth/exchange-token-direct', this);
        // });

        function submitForm(endpoint, form) {
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                const responseDiv = document.getElementById('response');
                responseDiv.style.display = 'block';
                responseDiv.className = 'response success';
                responseDiv.textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                const responseDiv = document.getElementById('response');
                responseDiv.style.display = 'block';
                responseDiv.className = 'response error';
                responseDiv.textContent = 'Error: ' + error.message;
            });
        }
    </script>
</body>
</html> 