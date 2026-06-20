Clear-Host

Write-Host "==========================================================" -ForegroundColor Yellow
Write-Host " 🚀 Launching 20 Concurrent Requests WITHOUT Distributed Locks " -ForegroundColor Yellow
Write-Host "==========================================================" -ForegroundColor Yellow
Write-Host "Firing requests simultaneously...`n" -ForegroundColor Cyan

$URL = "http://127.0.0.1:8000/purchase-without-lock"

for ($i = 1; $i -le 20; $i++) {
    Start-Job -ScriptBlock {
        param($id, $targetUrl) 
        try {
            $Body = @{ product_id = 1; quantity = 1 }
            $Response = Invoke-WebRequest -Uri $targetUrl -Method Post -Body $Body -TimeoutSec 10 -UseBasicParsing
            return "Request #$id -> STATUS: $($Response.StatusCode) | Body: $($Response.Content)"
        }
        catch {
            if ($_.Exception.Response) {
                $Reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                $ErrBody = $Reader.ReadToEnd()
                return "Request #$id -> ERROR STATUS: [422] | Message: $ErrBody"
            }
            return "Request #$id -> CRITICAL: $($_.Exception.Message)"
        }
    } -ArgumentList $i, $URL | Out-Null
}

Write-Host "Waiting for all 20 background workers to finish..." -ForegroundColor Gray

Get-Job | Wait-Job | Out-Null

Write-Host "`n==========================================================" -ForegroundColor Cyan
Write-Host " FINAL TRAFFIC RESULTS (WITHOUT LOCK CONCURRENCY):" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# استقبال وعرض النتائج الملونة
Get-Job | ForEach-Object {
    $Result = $_ | Receive-Job
    if ($Result) {
        if ($Result -match "STATUS: 200") {
            Write-Host $Result -ForegroundColor Green
        } else {
            Write-Host $Result -ForegroundColor Red
        }
    }
}

# 🔥 التنظيف الإجباري الحاسم لمنع ظهور أي خطأ أحمر في التيرمينال
Get-Job | Remove-Job -Force