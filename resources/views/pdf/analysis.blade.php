?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Karakter Analizi Raporu</title>
    <style>
        /* PDF'inize özel stilleri buraya yazabilirsiniz */
        body { 
            font-family: DejaVu Sans, sans-serif; /* Türkçe karakter desteği için */
            line-height: 1.6;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #6c5ce7;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #6c5ce7;
            margin: 0;
        }
        .content {
            margin-top: 20px;
            text-align: justify;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Karakter Aynası</h1>
            <p>Kişisel Analiz Raporu</p>
        </div>

        <div class="content">
            <p><strong>Analiz Tarihi:</strong> {{ $analysis->created_at->format('d.m.Y H:i') }}</p>
            <hr>
            <p>{!! nl2br(e($analysis->analysis_text)) !!}</p>
        </div>
    </div>
    
    <div class="footer">
        <p>Bu analiz, L.A. Vaught'un 1902 tarihli eserine dayalı, yapay zeka tarafından üretilmiş eğlence amaçlı bir yorumdur ve bilimsel bir geçerliliği yoktur.</p>
    </div>
</body>
</html>
