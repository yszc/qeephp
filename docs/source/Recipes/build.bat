@echo off
setlocal
set OUTPUT=..\..\output\Recipes
set BUILD=..\..\build\texy

if exist "%OUTPUT%" rmdir /s/q "%OUTPUT%"
mkdir "%OUTPUT%"
mkdir "%OUTPUT%\css"

copy css\*.* "%OUTPUT%\css"


for /f "tokens=*" %%f in ('dir /b *.txt') do %BUILD% "%%f" > "%OUTPUT%\%%~nf.html"
