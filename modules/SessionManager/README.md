# Session Manager
Managing sessions in PHP.

## Store session

    SessionManager::write([string/name], [value]);

## Read session
The method returns false if the session is missing or expired.

    SessionManager::read([string/name]);

## Set the expire time

    SessionManager::setTTL(60 * 60); // 1 hour

## Get the expire time

    SessionManager::getTTL();

## Remove all the stored session variables

    SessionManager::destroy();

## Remove specific session variable

    SessionManager::clear([string/name]);

## Find out how much time is left before the exprire

    SessionManager::timeLeftBeforeExpire();

* Don't forget to add *session_start();* in your application.