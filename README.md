# COSC386-SwimDB
Swimming Recruitment database for COSC 386

I'm using this as our todo / notes area

TODO:
- [ ] password hashes
- [ ] login verification
- [ ] GUI fluffing
- [ ] file path clarifications
- [ ] a standardized/centralized place to hand sql queries?
- [ ] finalize ER diagram and database schema

other notes:
1. I'm running under the assumption that database.sql is the file we're using to create the database tables. if it's not please tell me and/or move them somewhere else
2. we cannot have login / connection details stored in public_html
3. how are we storing users/passwords? if it's on their individual tables like in the diagram or a separate users table. login.php assumes individual tables.

W3 pages that were helpful (please add to list)
1. Sessions: https://www.w3schools.com/php/php_sessions.asp

Session Variables:
| name | usage |
| ---- | ----- |
| user | users ID, either a swimmer/coach/admin ID |
| userType | users type, either 'swimmer' 'coach' or 'admin' |
| loggedIN | whether they've successfully logged in. value is 1 if true. |
    