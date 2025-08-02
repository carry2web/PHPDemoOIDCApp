@echo off
REM Run PHP tests without Xdebug to prevent connection timeouts

echo Running PHP tests without Xdebug...

REM Method 1: Use php with disabled xdebug
php -d xdebug.mode=off -d xdebug.start_with_request=no tests/complete_test.php

echo.
echo Test completed. Press any key to continue...
pause >nul
