$loginUrl = "http://osoyoos-events.localhost/login"
$historyUrl = "http://osoyoos-events.localhost/user/ticket-history"

$loginBody = @{
    email = "test@example.com"
    password = "password123"
} | ConvertTo-Json

$loginResponse = Invoke-RestMethod -Uri $loginUrl -Method Post -Body $loginBody -ContentType "application/json"
$token = $loginResponse.token

$headers = @{
    Authorization = "Bearer $token"
}

$historyResponse = Invoke-RestMethod -Uri $historyUrl -Method Get -Headers $headers

$historyResponse | ConvertTo-Json -Depth 5