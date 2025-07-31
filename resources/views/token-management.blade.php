<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OAuth Token Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
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
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 10px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .token-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .token-table th, .token-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .token-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .token-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-expired {
            color: #ffc107;
            font-weight: bold;
        }
        .status-revoked {
            color: #dc3545;
            font-weight: bold;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        .no-tokens {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            padding: 20px;
            background: #e9ecef;
            border-radius: 8px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>OAuth Token Management</h1>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number" id="total-tokens">-</div>
                <div class="stat-label">Total Tokens</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="active-tokens">-</div>
                <div class="stat-label">Active Tokens</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="expired-tokens">-</div>
                <div class="stat-label">Expired Tokens</div>
            </div>
        </div>

        <div class="filters">
            <h3>Filters</h3>
            <div class="filter-group">
                <label for="client_id_filter">Client ID:</label>
                <input type="text" id="client_id_filter" placeholder="Filter by client ID">
            </div>
            <div class="filter-group">
                <label for="user_id_filter">User ID:</label>
                <input type="text" id="user_id_filter" placeholder="Filter by user ID">
            </div>
            <div class="filter-group">
                <label for="status_filter">Status:</label>
                <select id="status_filter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="revoked">Revoked</option>
                </select>
            </div>
            <button class="btn" onclick="loadTokens()">Apply Filters</button>
            <button class="btn btn-success" onclick="cleanupExpired()">Cleanup Expired</button>
        </div>

        <div id="tokens-container">
            <div class="no-tokens">Loading tokens...</div>
        </div>
    </div>

    <script>
        // Load tokens on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTokens();
        });

        function loadTokens() {
            const clientId = document.getElementById('client_id_filter').value;
            const userId = document.getElementById('user_id_filter').value;
            const status = document.getElementById('status_filter').value;

            let url = '/oauth/tokens?';
            if (clientId) url += `client_id=${encodeURIComponent(clientId)}&`;
            if (userId) url += `user_id=${encodeURIComponent(userId)}&`;
            if (status) url += `status=${encodeURIComponent(status)}&`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTokens(data.tokens);
                        updateStats(data.tokens);
                    } else {
                        document.getElementById('tokens-container').innerHTML = 
                            '<div class="no-tokens">Error loading tokens</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('tokens-container').innerHTML = 
                        '<div class="no-tokens">Error loading tokens: ' + error.message + '</div>';
                });
        }

        function displayTokens(tokens) {
            const container = document.getElementById('tokens-container');
            
            if (tokens.length === 0) {
                container.innerHTML = '<div class="no-tokens">No tokens found</div>';
                return;
            }

            let html = `
                <table class="token-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Client ID</th>
                            <th>Status</th>
                            <th>Expires At</th>
                            <th>Last Used</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            tokens.forEach(token => {
                const statusClass = `status-${token.status}`;
                const expiresAt = token.expires_at ? new Date(token.expires_at).toLocaleString() : 'Never';
                const lastUsed = token.last_used_at ? new Date(token.last_used_at).toLocaleString() : 'Never';
                const created = new Date(token.created_at).toLocaleString();

                html += `
                    <tr>
                        <td>${token.id}</td>
                        <td>${token.user_id || 'N/A'}</td>
                        <td>${token.client_id}</td>
                        <td><span class="${statusClass}">${token.status}</span></td>
                        <td>${expiresAt}</td>
                        <td>${lastUsed}</td>
                        <td>${created}</td>
                        <td class="actions">
                            <button class="btn btn-sm" onclick="viewToken(${token.id})">View</button>
                            ${token.status === 'active' ? 
                                `<button class="btn btn-sm btn-danger" onclick="revokeToken(${token.id})">Revoke</button>` : 
                                ''
                            }
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function updateStats(tokens) {
            const total = tokens.length;
            const active = tokens.filter(t => t.status === 'active').length;
            const expired = tokens.filter(t => t.status === 'expired').length;

            document.getElementById('total-tokens').textContent = total;
            document.getElementById('active-tokens').textContent = active;
            document.getElementById('expired-tokens').textContent = expired;
        }

        function viewToken(id) {
            fetch(`/oauth/tokens/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(JSON.stringify(data.token, null, 2));
                    } else {
                        alert('Error loading token details');
                    }
                })
                .catch(error => {
                    alert('Error loading token details: ' + error.message);
                });
        }

        function revokeToken(id) {
            if (!confirm('Are you sure you want to revoke this token?')) {
                return;
            }

            fetch(`/oauth/tokens/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Token revoked successfully');
                    loadTokens();
                } else {
                    alert('Error revoking token');
                }
            })
            .catch(error => {
                alert('Error revoking token: ' + error.message);
            });
        }

        function cleanupExpired() {
            if (!confirm('Are you sure you want to cleanup expired tokens?')) {
                return;
            }

            fetch('/oauth/tokens/cleanup', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Cleanup completed: ${data.count} tokens marked as expired`);
                    loadTokens();
                } else {
                    alert('Error during cleanup');
                }
            })
            .catch(error => {
                alert('Error during cleanup: ' + error.message);
            });
        }
    </script>
</body>
</html> 