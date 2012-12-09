@ECHO OFF

REM Windows wrapper for phpCliSrc.php programs
REM Copyright 2006-$LastChangedDate: 2007-08-19 21:17:29 -0700 (Sun, 19 Aug 2007) $ Bryn Mosher - All rights reserved
REM $Id: phpCliSrc.cmd 44 2007-08-20 04:17:29Z bryn $
REM $HeadURL: svn+ssh://bryn@dev.brynmosher.com/subversion/phpCliSrc/trunk/phpCliSrc.cmd $

REM This is an example Windows/DOS batch wrapper for your utility

REM You can set a couple of things here
SET phpPath=C:\Progra~1\PHP\php.exe
SET utilityPath=C:\phpCliSrc_Example.php
REM Unfortuantely there isn't an easey way to set the priority without getting sloppy with the START command.
REM You're welcome to try if you like.

REM The utility's help function will use this to recognize the wrapper
SET wpid=%COMSPEC%

REM Now we start our script
%phpPath% -f %utilityPath% -- %*
SET errorLev=%ERRORLEVEL%

REM Remove our environment variable
SET wpid=

REM This passes the exit code to to the console. Do not change this.
EXIT /b %errorLev%
