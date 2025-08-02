# PowerShell script to run tests without Xdebug
# Run PHP tests with Xdebug disabled

Write-Host "Running PHP tests without Xdebug..." -ForegroundColor Green

# Method 1: Disable Xdebug via command line options
& php -d xdebug.mode=off -d xdebug.start_with_request=no tests/complete_test.php

Write-Host "`nTest completed!" -ForegroundColor Green
