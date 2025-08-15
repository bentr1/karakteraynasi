<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Karakter Analizi Raporu</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            line-height: 1.6;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .content {
            margin-top: 20px;
            white-space: pre-wrap; /* Metindeki boşlukları ve satır sonlarını korur */
        }
    </style>
</head>
<body>
    <h1>Karakter Analizi Raporunuz</h1>
    <div class="content">
        <p>{{ $analysis->analysis_text }}</p>
    </div>
</body>
</html>
