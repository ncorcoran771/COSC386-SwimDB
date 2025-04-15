# COSC386-SwimDB
Swimming Recruitment database for COSC 386

I'm using this as our todo / notes area

TODO:
- [ ] password hashes
- [x] login verification
- [ ] GUI fluffing
- [ ] file path clarifications
- [ ] a standardized/centralized place to hand sql queries?
- [ ] finalize ER diagram and database schema
- [ ] look into Prepared Statements as injection resistance: https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php sounds cool & handy in general 

other notes:
1. I'm running under the assumption that database.sql is the file we're using to create the database tables. if it's not please tell me and/or move them somewhere else
2. we cannot have login / connection details stored in public_html
3. how are we storing users/passwords? if it's on their individual tables like in the diagram or a separate users table. login.php assumes individual tables.
4. Recommended VSCode extensions (if you use it):
    - devsense.phptools-vscode
    - xdebug.php-debug


W3 pages that were helpful (please add to list)
1. Sessions: https://www.w3schools.com/php/php_sessions.asp

Session Variables:
| name | usage | type/values |
| ---- | ----- | ----------- |
| user | users ID | int |
| userType | users type | 'swimmer' 'coach' 'admin' |
| loggedIN | login success | true if verified |
    