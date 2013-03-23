<?php
/***
	File: dbline.config.php
	License: GPLv3
	http://softov.org/webdevtools
	http://code.google.com/p/dbline
	
	DBLine config file.
	DBLine is a quick web interface tool to MySQL DB for sql commands and queries.
	It supports all sql commands that your db allows you to.
	For a full list of features, and for feature requests, go to
	http://code.google.com/p/dbline

***/

/////////////////////////////////
//        Settings             //
/////////////////////////////////
global $cfg;

// Page encoding charset
header("Content-Type: text/html; charset=UTF-8");

// Password for enabling interface access (MD5)
// To calculate MD5 use http://softov.org/md5.html
$cfg['passmd5'] = 'PUT MD5-CODE-OF-PASSWORD HERE'; 

// database connection parameters
$cfg['dbhost'] = 'PUT HOST HERE';   // usually 'localhost'
$cfg['dbname'] = 'PUT DB-NAME HERE';
$cfg['dbuser'] = 'PUT USER HERE';   // many times it is the same as the db name
$cfg['dbpass'] = 'PUT PASSWORD HERE';
$cfg['dbcharset'] = ''; // optionally set database connection charset
$cfg['dbcollation'] = ''; // optionally set database connection collation

// Should we enable a free-text queries mode? 
// false means only preset queries from the list are allowed 
$cfg['freetext'] = true;

// Should I write the sql command back to the user?
$cfg['echo'] = true;

// Session mode? true means a single login, false means password is sent on every post
$cfg['session'] = true;

// Preset queries (these are enabled also when $enableQueries is disabled)
$cfg['preset'] = array (  // here are some queries examples
	'Show a list of all tables' => 'show tables',
	'Last INSERT id' => 'select last_insert_id()',
	'Show the log' => 'select * from log where 1=1',   // where statement is mandatory for dbline

	'endoflist'=>'' // only to avoid missing-comma syntax errors...
);
unset($cfg['preset']['endoflist']);

// xor the login password and sql commands? leave as true
$cfg['xor'] = true;

// A random string to encrypt the input sql string (using xor)
// On session mode this string is generated on login
$cfg['random'] = "XWwfjNIqJKHdZRvPFZteHRRvqLLzxck6na6jERfYoNEYCDl0HM81pLp4ZPmNYkYCTW7gj8ExuE8DtCvV6Mqgv6FOJUGqBIAp6gT1sTLrVrzsMoGizBBoSDZQPSZ5vyvuGsCjoko0noOU5sDXI10FGzzVqMYJtnINHUT5nDadBHXEU38w6eQRefc0x49yfnIiylv365bxj9gK46sojKPfOVHt3QTHaio0hj70fD1MZyEquJkxWNN4d04eCg2mgW2kwart2ZqnA0S99f0tRMfPAXEOaiKsShxbscrmc9oBTnp4a1xXpm0SD67AnTeRidLkjuMchjeeKehI0F9Z35JZhxzJ0TuTV9GtB72LnhwJMh9WyMUOtmllPCstkOC5aHIxknn2Q4QDbjqnkyKDN9IIFD62EfxX1XrIGTJZeENxSMppm6vB6uPybVbMHVyHQF0UajfNEyCyW79bwKriHqLzxvoqTjeyO7LdHIoAT38S5vEZXvUHiEVD";

// force 'where' caluse on select/delete/update
$cfg['where'] = true;
