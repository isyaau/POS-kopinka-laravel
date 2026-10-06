@echo off
title Update POS Kopinka
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0update.ps1" %*
echo.
echo Selesai. Tekan tombol apa saja untuk menutup.
pause > nul
