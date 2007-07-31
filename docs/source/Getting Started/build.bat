@echo off
setlocal
set OUTPUT=..\..\output\Getting Started
set BUILD=..\..\build\texy

if exist "%OUTPUT%" rmdir /s/q "%OUTPUT%"
mkdir "%OUTPUT%"
mkdir "%OUTPUT%\css"
mkdir "%OUTPUT%\images"

copy css\*.* "%OUTPUT%\css"
copy images\*.* "%OUTPUT%\images"

%BUILD% "Getting Started.txt" > "%OUTPUT%\Getting Started.html"
