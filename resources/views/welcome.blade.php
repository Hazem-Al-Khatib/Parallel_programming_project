<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parallel Store - Control Panel</title>
    <style>
        body { 
            font-family: sans-serif; 
            background: #f4f7f6; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .card { 
            background: white; 
            padding: 2.5rem; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            text-align: center; 
            width: 380px; 
        }
        h2 { color: #2d3748; margin-bottom: 0.5rem; margin-top: 0; }
        .subtitle { color: #718096; font-size: 0.85rem; margin-bottom: 1.5rem; }
        .product-info { background: #edf2f7; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: right; }
        
        .btn-purchase { 
            background: #4299e1; color: white; border: none; padding: 12px 24px; 
            font-size: 1.1rem; border-radius: 8px; cursor: pointer; transition: 0.3s; width: 100%;
            font-weight: bold; margin-bottom: 1rem;
        }
        .btn-purchase:hover { background: #3182ce; transform: translateY(-2px); }
        
        .divider {
            border: 0;
            height: 1px;
            background: #e2e8f0;
            margin: 1.5rem 0;
        }

        .btn-parallel-test {
            display: inline-block;
            background: #38a169; color: white; border: none; padding: 12px 24px; 
            font-size: 1rem; border-radius: 8px; cursor: pointer; transition: 0.3s; width: 100%;
            font-weight: bold; text-decoration: none; box-sizing: border-box;
        }
        .btn-parallel-test:hover { background: #2f855a; transform: translateY(-2px); }
        
        .status { margin-top: 1rem; font-size: 0.8rem; color: #a0aec0; line-height: 1.4; }
    </style>
</head>
<body>

    <div class="card">
        <h2> Parallel Store ⚙️ </h2>
        <div class="subtitle">لوحة اختبار التزامن والمحاكاة المتوازية</div>
        
        <div class="product-info">
            <strong>المنتج الحالي:</strong> Laptop AI Edition<br>
            <strong>آلية الحماية:</strong> Pessimistic Lock & Throttle
        </div>

        <form action="/buy" method="POST">
            @csrf
            <input type="hidden" name="product_id" value="1">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="btn-purchase">Confirm Purchase (تأكيد الشراء)</button>
        </form>

        <div class="divider"></div>

        <div style="text-align: right; margin-bottom: 0.8rem;">
            <span style="font-size: 0.9rem; font-weight: bold; color: #4a5568;">المعالجة الكثيفة (Batch Processing):</span>
        </div>
        <a href="/start-parallel-test" target="_blank" class="btn-parallel-test">
            Launch Parallel Batch Test 🚀
        </a>

        <div class="status">
            * زر الشراء مقيد بـ 5 طلبات في الدقيقة لكل IP.<br>
            * زر المعالجة يطلق الـ Daily Sales Batch في الخلفية.
        </div>
    </div>

</body>
</html>