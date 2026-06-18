# تصفير شاشة الـ Terminal ليكون العرض نظيفاً
Clear-Host

# رابط المسار للـ Distributed Lock في لارافيل
$URL = "http://127.0.0.1:8000/purchase-distributed-lock"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Launching 20 Concurrent Requests with Redis Distributed Lock " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "Processing and streaming results immediately..." -ForegroundColor Yellow
Write-Host ""

# مصفوفة لتجميع جوبز الخلفية المتوازية
$Jobs = @()

# إطلاق 20 طلباً متزامناً بالخلفية
for ($i = 1; $i -le 20; $i++) {
    $Jobs += Start-Job -ScriptBlock {
        param($TargetURL, $Id)
        try {
            $Body = @{ product_id = 1 }
            # إرسال طلب الـ POST
            $Result = Invoke-RestMethod -Uri $TargetURL -Method Post -Body $Body -TimeoutSec 10
            return "[SUCCESS] Request #$Id -> " + $Result.message + " | Stock left: " + $Result.remaining_stock
        }
        catch {
            # اقتناص الاستثناء بشكل هندسي دقيق لقراءة الـ Body القادم من لارافيل
            if ($_.Exception.InnerException -and $_.Exception.InnerException.Response) {
                $ResponseStream = $_.Exception.InnerException.Response.GetResponseStream()
                $Reader = New-Object System.IO.StreamReader($ResponseStream)
                $ErrorBody = $Reader.ReadToEnd()
                
                try {
                    $JsonError = $ErrorBody | ConvertFrom-Json
                    return "[REJECTED] Request #$Id -> " + $JsonError.message
                } catch {
                    return "[REJECTED] Request #$Id -> " + $ErrorBody
                }
            } 
            elseif ($_.Exception.Response) {
                $ResponseStream = $_.Exception.Response.GetResponseStream()
                $Reader = New-Object System.IO.StreamReader($ResponseStream)
                $ErrorBody = $Reader.ReadToEnd()
                try {
                    $JsonError = $ErrorBody | ConvertFrom-Json
                    return "[REJECTED] Request #$Id -> " + $JsonError.message
                } catch {
                    return "[REJECTED] Request #$Id -> " + $ErrorBody
                }
            }
            else {
                return "[REJECTED] Request #$Id -> Connection Error / Timeout"
            }
        }
    } -ArgumentList $URL, $i
}

# 🔄 حلقة الفحص والطباعة الفورية والملونة
while ($Jobs.Count -gt 0) {
    $CompletedJobs = $Jobs | Where-Object { $_.State -ne "Running" }
    
    foreach ($Job in $CompletedJobs) {
        $Output = Receive-Job -Job $Job
        if ($Output -like "*[SUCCESS]*") {
            Write-Host $Output -ForegroundColor Green
        } else {
            # إذا كانت رسالة الرفض تحتوي على نص الخطأ الفعلي، سنراها بوضوح هنا
            Write-Host $Output -ForegroundColor Red
        }
        
        $Jobs = $Jobs | Where-Object { $_.Id -ne $Job.Id }
        $Job | Remove-Job
    }
    
    Start-Sleep -Milliseconds 50
}

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " All 20 requests processed in real-time execution!         " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan