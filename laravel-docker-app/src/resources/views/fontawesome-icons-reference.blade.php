<!DOCTYPE html>
<html>
<head>
    <title>FontAwesome Icons Reference</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        h2 {
            color: #666;
            margin-top: 40px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }
        .icon-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .icon-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .icon-item i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
            color: #495057;
        }
        .icon-item code {
            font-size: 11px;
            background: #fff;
            padding: 2px 4px;
            border-radius: 2px;
            display: block;
            word-break: break-all;
        }
        .copied {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>FontAwesome Icons Reference for RIMS Tool</h1>
        <p>Click on any icon to copy its class name to clipboard</p>
        
        <div class="copied" id="copied">Copied to clipboard!</div>
        
        <h2>Commonly Used Icons for Hotel/Membership Tables</h2>
        <div class="icon-grid">
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-hotel')">
                <i class="fa-solid fa-hotel"></i>
                <code>fa-solid fa-hotel</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-star')">
                <i class="fa-solid fa-star"></i>
                <code>fa-solid fa-star</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-crown')">
                <i class="fa-solid fa-crown"></i>
                <code>fa-solid fa-crown</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-gem')">
                <i class="fa-solid fa-gem"></i>
                <code>fa-solid fa-gem</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-award')">
                <i class="fa-solid fa-award"></i>
                <code>fa-solid fa-award</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-medal')">
                <i class="fa-solid fa-medal"></i>
                <code>fa-solid fa-medal</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-trophy')">
                <i class="fa-solid fa-trophy"></i>
                <code>fa-solid fa-trophy</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-id-card')">
                <i class="fa-solid fa-id-card"></i>
                <code>fa-solid fa-id-card</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-users')">
                <i class="fa-solid fa-users"></i>
                <code>fa-solid fa-users</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-user-tie')">
                <i class="fa-solid fa-user-tie"></i>
                <code>fa-solid fa-user-tie</code>
            </div>
        </div>
        
        <h2>Table & Database Icons</h2>
        <div class="icon-grid">
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-table')">
                <i class="fa-solid fa-table"></i>
                <code>fa-solid fa-table</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-database')">
                <i class="fa-solid fa-database"></i>
                <code>fa-solid fa-database</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-list')">
                <i class="fa-solid fa-list"></i>
                <code>fa-solid fa-list</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-server')">
                <i class="fa-solid fa-server"></i>
                <code>fa-solid fa-server</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-folder')">
                <i class="fa-solid fa-folder"></i>
                <code>fa-solid fa-folder</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-file')">
                <i class="fa-solid fa-file"></i>
                <code>fa-solid fa-file</code>
            </div>
        </div>
        
        <h2>Business & Commerce Icons</h2>
        <div class="icon-grid">
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-briefcase')">
                <i class="fa-solid fa-briefcase"></i>
                <code>fa-solid fa-briefcase</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-building')">
                <i class="fa-solid fa-building"></i>
                <code>fa-solid fa-building</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-credit-card')">
                <i class="fa-solid fa-credit-card"></i>
                <code>fa-solid fa-credit-card</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-money-bill')">
                <i class="fa-solid fa-money-bill"></i>
                <code>fa-solid fa-money-bill</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-chart-line')">
                <i class="fa-solid fa-chart-line"></i>
                <code>fa-solid fa-chart-line</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-solid fa-percent')">
                <i class="fa-solid fa-percent"></i>
                <code>fa-solid fa-percent</code>
            </div>
        </div>
        
        <h2>Regular Style Icons</h2>
        <div class="icon-grid">
            <div class="icon-item" onclick="copyToClipboard('fa-regular fa-star')">
                <i class="fa-regular fa-star"></i>
                <code>fa-regular fa-star</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-regular fa-heart')">
                <i class="fa-regular fa-heart"></i>
                <code>fa-regular fa-heart</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-regular fa-user')">
                <i class="fa-regular fa-user"></i>
                <code>fa-regular fa-user</code>
            </div>
            <div class="icon-item" onclick="copyToClipboard('fa-regular fa-id-card')">
                <i class="fa-regular fa-id-card"></i>
                <code>fa-regular fa-id-card</code>
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                const copiedDiv = document.getElementById('copied');
                copiedDiv.style.display = 'block';
                setTimeout(() => {
                    copiedDiv.style.display = 'none';
                }, 2000);
            });
        }
    </script>
</body>
</html>