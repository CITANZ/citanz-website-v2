-- desensitise.sql — turn a copy of the live CITANZ DB into a dev-safe DB.
--
-- RUN ONLY INSIDE AWS (nonprod EC2), on the throwaway copy made by
-- `scripts/db-check.sh <dump> --keep`. Never on production. Never locally on
-- raw data. See the end of db-check.sh output for the exact command.
--
-- Optional inputs (set before sourcing this file):
--   SET @test_pw_hash     = '<bcrypt hash>';  -- password for the test accounts
--   SET @placeholder_sha1 = '<sha1 of the placeholder image>';
-- Without @test_pw_hash every customer password is disabled.
-- Without @placeholder_sha1 student-ID file records are deleted instead of
-- being pointed at a placeholder image.
--
-- Goal: fake who people are, keep how the data looks, so bugs still reproduce.
-- Kept real:  IDs, relations, dates, membership state (Verified, Expiry,
--             isStudent, NeverExpire), gender/degree/job fields, city/region,
--             order amounts/status/items, CMS pages, GUIDs.
-- Faked, same shape: names (Chinese-looking stays Chinese-looking), emails
--             (upper-case stays upper-case, unique per member), phones,
--             addresses, social handles, CV/cover-letter keys, free text.
--             Empty or NULL stays empty or NULL.
-- Removed:    passwords (NULL stays NULL), login tokens/codes, OAuth tokens,
--             sessions, login attempts, Mailchimp key, queued job payloads.
-- Student-ID images: records kept, renamed student-id-<ID>.png and pointed
--             at one placeholder image (the real files are never exported).
--
-- Safe to run twice. Tables/columns that do not exist are skipped.

SET NAMES utf8mb4;
SET @test_pw_hash     = IFNULL(@test_pw_hash, NULL);
SET @placeholder_sha1 = IFNULL(@placeholder_sha1, NULL);
SET @disabled_pw      = '!disabled-in-dev';

-- ---------------------------------------------------------------------------
-- helpers
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _ds_run;
DROP PROCEDURE IF EXISTS _ds_if_table;
DROP PROCEDURE IF EXISTS _ds_if_column;
DROP FUNCTION  IF EXISTS _ds_fake;
DROP FUNCTION  IF EXISTS _ds_first;
DROP FUNCTION  IF EXISTS _ds_last;
DELIMITER //
CREATE PROCEDURE _ds_run(IN stmt TEXT)
BEGIN
  SET @_ds_sql = stmt;
  PREPARE s FROM @_ds_sql; EXECUTE s; DEALLOCATE PREPARE s;
END//
CREATE PROCEDURE _ds_if_table(IN tbl VARCHAR(128), IN stmt TEXT)
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = tbl) THEN
    CALL _ds_run(stmt);
  END IF;
END//
CREATE PROCEDURE _ds_if_column(IN tbl VARCHAR(128), IN col VARCHAR(128), IN stmt TEXT)
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = col) THEN
    CALL _ds_run(stmt);
  END IF;
END//
-- replace a value only if it was set: NULL stays NULL, '' stays ''
CREATE FUNCTION _ds_fake(orig TEXT CHARSET utf8mb4, repl TEXT CHARSET utf8mb4)
  RETURNS TEXT CHARSET utf8mb4 DETERMINISTIC NO SQL
  RETURN IF(orig IS NULL OR orig = '', orig, repl)//
-- fake first/last name chosen by ID; non-ASCII names get Chinese fakes
CREATE FUNCTION _ds_first(i INT, orig VARCHAR(255) CHARSET utf8mb4)
  RETURNS VARCHAR(255) CHARSET utf8mb4 DETERMINISTIC NO SQL
  RETURN CASE
    WHEN orig IS NULL OR orig = '' THEN orig
    WHEN orig REGEXP '[^ -~]' THEN ELT(1 + i % 10, '伟','子涵','芳','浩然','静','雨萱','磊','欣怡','军','思远')
    ELSE ELT(1 + i % 16, 'Alex','Sam','Jordan','Taylor','Casey','Morgan','Riley','Jamie',
                         'Avery','Quinn','Charlie','Drew','Harper','Kai','Logan','Max')
  END//
CREATE FUNCTION _ds_last(i INT, orig VARCHAR(255) CHARSET utf8mb4)
  RETURNS VARCHAR(255) CHARSET utf8mb4 DETERMINISTIC NO SQL
  RETURN CASE
    WHEN orig IS NULL OR orig = '' THEN orig
    WHEN orig REGEXP '[^ -~]' THEN ELT(1 + (i DIV 10) % 10, '王','李','张','刘','陈','杨','黄','赵','吴','周')
    ELSE ELT(1 + (i DIV 16) % 12, 'Smith','Brown','Wilson','Taylor','Lee','Walker',
                                  'Hall','Young','King','Wright','Green','Baker')
  END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- 1. Pick test accounts (one per membership state), before anything changes
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS _ds_test_accounts;
CREATE TABLE _ds_test_accounts (ID INT PRIMARY KEY, Label VARCHAR(32));
INSERT IGNORE INTO _ds_test_accounts
  SELECT ID, 'active'      FROM Cita_eCommerce_Customer WHERE Verified=1 AND isStudent=0 AND NeverExpire=0 AND Expiry >= CURDATE() ORDER BY ID LIMIT 1;
INSERT IGNORE INTO _ds_test_accounts
  SELECT ID, 'student'     FROM Cita_eCommerce_Customer WHERE Verified=1 AND isStudent=1 ORDER BY Expiry DESC, ID LIMIT 1;
INSERT IGNORE INTO _ds_test_accounts
  SELECT ID, 'expired'     FROM Cita_eCommerce_Customer WHERE Verified=1 AND NeverExpire=0 AND Expiry < CURDATE() ORDER BY ID LIMIT 1;
INSERT IGNORE INTO _ds_test_accounts
  SELECT ID, 'neverexpire' FROM Cita_eCommerce_Customer WHERE Verified=1 AND NeverExpire=1 ORDER BY ID LIMIT 1;
INSERT IGNORE INTO _ds_test_accounts
  SELECT ID, 'unverified'  FROM Cita_eCommerce_Customer WHERE Verified=0 ORDER BY ID LIMIT 1;

-- ---------------------------------------------------------------------------
-- 2. Customers (members)
-- ---------------------------------------------------------------------------
UPDATE Cita_eCommerce_Customer c
LEFT JOIN _ds_test_accounts t ON t.ID = c.ID
SET c.FirstName    = IF(t.ID IS NULL, _ds_first(c.ID, c.FirstName), 'Test'),
    c.LastName     = IF(t.ID IS NULL, _ds_last(c.ID, c.LastName), t.Label),
    c.Email        = CASE
                       WHEN t.ID IS NOT NULL THEN CONCAT('test-', t.Label, '@example.com')
                       WHEN c.Email IS NULL OR c.Email = '' THEN c.Email
                       WHEN BINARY c.Email <> BINARY LOWER(c.Email) THEN CONCAT('Member', c.ID, '@Example.invalid')
                       ELSE CONCAT('member', c.ID, '@example.invalid')
                     END,
    c.Phone        = _ds_fake(c.Phone, CONCAT('021', LPAD(c.ID, 7, '0'))),
    c.Password     = CASE
                       WHEN t.ID IS NOT NULL AND @test_pw_hash IS NOT NULL THEN @test_pw_hash
                       WHEN c.Password IS NULL THEN NULL
                       ELSE @disabled_pw
                     END,
    c.PendingEmail = _ds_fake(c.PendingEmail, CONCAT('pending', c.ID, '@example.invalid')),
    c.Dob          = IF(c.Dob IS NULL, NULL, MAKEDATE(YEAR(c.Dob), 1)),
    c.Organisation = _ds_fake(c.Organisation, 'Example Org'),
    c.WechatID     = _ds_fake(c.WechatID, CONCAT('wx_test_', c.ID)),
    c.LinkedInLink = _ds_fake(c.LinkedInLink, CONCAT('https://www.linkedin.com/in/example-', c.ID)),
    c.Github       = _ds_fake(c.Github, CONCAT('example-', c.ID)),
    -- S3 keys under <GUID>/ in the CV bucket; the files themselves do not exist in dev
    c.CV           = _ds_fake(c.CV, CONCAT('dev-placeholder-cv-', c.ID, '.pdf')),
    c.CoverLetter  = _ds_fake(c.CoverLetter, CONCAT('dev-placeholder-cl-', c.ID, '.pdf'));

CALL _ds_if_table('Cita_eCommerce_Address',
  "UPDATE Cita_eCommerce_Address SET
     FirstName = _ds_first(CustomerID, FirstName), Surname = _ds_last(CustomerID, Surname),
     Email     = _ds_fake(Email, CONCAT('member', CustomerID, '@example.invalid')),
     Phone     = _ds_fake(Phone, CONCAT('021', LPAD(CustomerID, 7, '0'))),
     Company   = _ds_fake(Company, 'Example Org'),
     Address   = _ds_fake(Address, CONCAT(ID, ' Example Street')),
     Apartment = _ds_fake(Apartment, 'Unit 1'),
     Suburb    = _ds_fake(Suburb, 'Example'),
     Postcode  = _ds_fake(Postcode, '0000'),
     FormattedAddress = _ds_fake(FormattedAddress, CONCAT(ID, ' Example Street, Example 0000'))");

CALL _ds_if_table('Cita_eCommerce_MemberVerificationCode', 'DELETE FROM Cita_eCommerce_MemberVerificationCode');

-- ---------------------------------------------------------------------------
-- 3. Orders (amounts/status/items stay real; contact details faked)
-- ---------------------------------------------------------------------------
CALL _ds_if_table('Cita_eCommerce_Order',
  "UPDATE Cita_eCommerce_Order SET
     Email = _ds_fake(Email, IF(CustomerID > 0, CONCAT('member', CustomerID, '@example.invalid'),
                                                CONCAT('order', ID, '@example.invalid'))),
     AnonymousCustomer    = _ds_fake(AnonymousCustomer, CONCAT('anon', ID)),
     ShippingFirstname    = _ds_first(CustomerID, ShippingFirstname),
     ShippingSurname      = _ds_last(CustomerID, ShippingSurname),
     ShippingAddress      = _ds_fake(ShippingAddress, CONCAT(ID, ' Example Street')),
     ShippingOrganisation = _ds_fake(ShippingOrganisation, 'Example Org'),
     ShippingApartment    = _ds_fake(ShippingApartment, 'Unit 1'),
     ShippingSuburb       = _ds_fake(ShippingSuburb, 'Example'),
     ShippingPostcode     = _ds_fake(ShippingPostcode, '0000'),
     ShippingPhone        = _ds_fake(ShippingPhone, CONCAT('021', LPAD(CustomerID, 7, '0'))),
     BillingFirstname     = _ds_first(CustomerID, BillingFirstname),
     BillingSurname       = _ds_last(CustomerID, BillingSurname),
     BillingAddress       = _ds_fake(BillingAddress, CONCAT(ID, ' Example Street')),
     BillingOrganisation  = _ds_fake(BillingOrganisation, 'Example Org'),
     BillingApartment     = _ds_fake(BillingApartment, 'Unit 1'),
     BillingSuburb        = _ds_fake(BillingSuburb, 'Example'),
     BillingPostcode      = _ds_fake(BillingPostcode, '0000'),
     BillingPhone         = _ds_fake(BillingPhone, CONCAT('021', LPAD(CustomerID, 7, '0'))),
     Comment              = _ds_fake(Comment, 'Example order comment')");
-- snapshot = JSON of the order (no customer block); only comment/messages are free text
CALL _ds_if_table('Cita_eCommerce_OrderSnapshot',
  "UPDATE Cita_eCommerce_OrderSnapshot SET StoredDetails = JSON_SET(StoredDetails,
     '$.comment',  IF(JSON_UNQUOTE(JSON_EXTRACT(StoredDetails, '$.comment')) IN ('', 'null'),
                      JSON_EXTRACT(StoredDetails, '$.comment'), 'Example order comment'),
     '$.messages', JSON_ARRAY())
   WHERE JSON_VALID(StoredDetails)");
CALL _ds_if_table('Cita_eCommerce_OrderSnapshot',
  "UPDATE Cita_eCommerce_OrderSnapshot SET StoredDetails = NULL WHERE NOT JSON_VALID(StoredDetails)");
CALL _ds_if_table('Cita_eCommerce_OrderMessage',
  "UPDATE Cita_eCommerce_OrderMessage SET Message = _ds_fake(Message, '<p>[redacted]</p>')");
-- omnipay payment messages may hold gateway responses / client IPs
CALL _ds_if_column('Omnipay_PaymentMessage', 'Message',
  "UPDATE Omnipay_PaymentMessage SET Message = _ds_fake(Message, '[redacted]')");
CALL _ds_if_column('Omnipay_PaymentMessage', 'ClientIp',
  "UPDATE Omnipay_PaymentMessage SET ClientIp = _ds_fake(ClientIp, '127.0.0.1')");

-- ---------------------------------------------------------------------------
-- 4. Other member-submitted data
-- ---------------------------------------------------------------------------
CALL _ds_if_table('StudentDiscountApplication',
  "UPDATE StudentDiscountApplication SET RejectReason = _ds_fake(RejectReason, '[redacted]')");
CALL _ds_if_table('JobApplication',
  "UPDATE JobApplication SET
     FirstName = _ds_first(ID, FirstName), LastName = _ds_last(ID, LastName),
     Email     = _ds_fake(Email, CONCAT('applicant', ID, '@example.invalid')),
     LinkedIn  = _ds_fake(LinkedIn, CONCAT('https://www.linkedin.com/in/example-', ID)),
     WechatID  = _ds_fake(WechatID, CONCAT('wx_test_', ID)),
     Phone     = _ds_fake(Phone, CONCAT('022', LPAD(ID, 7, '0'))),
     Github    = _ds_fake(Github, CONCAT('example-', ID)),
     CV        = _ds_fake(CV, CONCAT('dev-placeholder-cv-a', ID, '.pdf')),
     CoverLetter = _ds_fake(CoverLetter, CONCAT('dev-placeholder-cl-a', ID, '.pdf'))");
CALL _ds_if_table('ContactSubmission',
  "UPDATE ContactSubmission SET Name = _ds_fake(Name, CONCAT('Contact No', ID)),
     Email = _ds_fake(Email, CONCAT('contact', ID, '@example.invalid')),
     Title = _ds_fake(Title, '[redacted]'), Content = _ds_fake(Content, '[redacted]')");

-- ---------------------------------------------------------------------------
-- 5. Student-ID images (everything under assets/customers/, incl. old versions)
--    With @placeholder_sha1: keep the records, rename to student-id-<ID>.png and
--    point them at the placeholder image (links in /admin keep working).
--    Without it: delete the records.
--    The real image files are never exported (the asset package skips them).
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS _ds_id_files;
CREATE TABLE _ds_id_files (ID INT PRIMARY KEY);
CALL _ds_if_column('File', 'FileFilename',
  "INSERT IGNORE INTO _ds_id_files SELECT ID FROM File
     WHERE FileFilename LIKE 'customers/%' AND ClassName <> 'SilverStripe\\\\Assets\\\\Folder'");
CALL _ds_if_column('File_Versions', 'FileFilename',
  "INSERT IGNORE INTO _ds_id_files SELECT DISTINCT RecordID FROM File_Versions
     WHERE FileFilename LIKE 'customers/%' AND ClassName <> 'SilverStripe\\\\Assets\\\\Folder'");
CALL _ds_if_table('StudentDiscountApplication',
  "INSERT IGNORE INTO _ds_id_files SELECT StudentIDFileID FROM StudentDiscountApplication WHERE StudentIDFileID <> 0");

DROP PROCEDURE IF EXISTS _ds_id_files_apply;
DELIMITER //
CREATE PROCEDURE _ds_id_files_apply()
BEGIN
  IF @placeholder_sha1 IS NULL THEN
    DELETE f FROM File          f JOIN _ds_id_files p ON p.ID = f.ID;
    DELETE f FROM File_Live     f JOIN _ds_id_files p ON p.ID = f.ID;
    DELETE f FROM File_Versions f JOIN _ds_id_files p ON p.ID = f.RecordID;
    UPDATE StudentDiscountApplication SET StudentIDFileID = 0;
  ELSE
    -- only the last path segment changes: customers/<GUID>/student-id-<ID>.png
    UPDATE File f JOIN _ds_id_files p ON p.ID = f.ID SET
      f.ClassName = 'SilverStripe\\Assets\\Image',
      f.Name = CONCAT('student-id-', f.ID, '.png'), f.Title = CONCAT('student-id-', f.ID),
      f.FileHash = @placeholder_sha1, f.FileVariant = NULL,
      f.FileFilename = CONCAT(LEFT(f.FileFilename, CHAR_LENGTH(f.FileFilename) - CHAR_LENGTH(SUBSTRING_INDEX(f.FileFilename, '/', -1))),
                              'student-id-', f.ID, '.png');
    UPDATE File_Live f JOIN _ds_id_files p ON p.ID = f.ID SET
      f.ClassName = 'SilverStripe\\Assets\\Image',
      f.Name = CONCAT('student-id-', f.ID, '.png'), f.Title = CONCAT('student-id-', f.ID),
      f.FileHash = @placeholder_sha1, f.FileVariant = NULL,
      f.FileFilename = CONCAT(LEFT(f.FileFilename, CHAR_LENGTH(f.FileFilename) - CHAR_LENGTH(SUBSTRING_INDEX(f.FileFilename, '/', -1))),
                              'student-id-', f.ID, '.png');
    UPDATE File_Versions f JOIN _ds_id_files p ON p.ID = f.RecordID SET
      f.ClassName = 'SilverStripe\\Assets\\Image',
      f.Name = CONCAT('student-id-', f.RecordID, '.png'), f.Title = CONCAT('student-id-', f.RecordID),
      f.FileHash = @placeholder_sha1, f.FileVariant = NULL,
      f.FileFilename = CONCAT(LEFT(f.FileFilename, CHAR_LENGTH(f.FileFilename) - CHAR_LENGTH(SUBSTRING_INDEX(f.FileFilename, '/', -1))),
                              'student-id-', f.RecordID, '.png');
  END IF;
END//
DELIMITER ;
CALL _ds_id_files_apply();
-- the student-ID column on the customer itself (unused today, but clear if set)
UPDATE Cita_eCommerce_Customer SET StudentIDFileID = 0 WHERE StudentIDFileID <> 0
  AND StudentIDFileID NOT IN (SELECT ID FROM _ds_id_files);

-- ---------------------------------------------------------------------------
-- 6. CMS admin users (SilverStripe Member). Log in with SS_DEFAULT_ADMIN_* env.
--    PasswordEncryption is kept (tells you which hashing the account used).
-- ---------------------------------------------------------------------------
UPDATE Member SET
  FirstName = _ds_first(ID, FirstName), Surname = _ds_last(ID, Surname),
  Email = _ds_fake(Email, CONCAT('admin', ID, '@example.invalid')),
  Password = NULL, Salt = NULL, ValidationKey = NULL,
  TempIDHash = NULL, TempIDExpired = NULL, AutoLoginHash = NULL, AutoLoginExpired = NULL;
CALL _ds_if_table('MemberPassword',    'DELETE FROM MemberPassword');
CALL _ds_if_table('RememberLoginHash', 'DELETE FROM RememberLoginHash');
CALL _ds_if_table('LoginAttempt',      'DELETE FROM LoginAttempt');
CALL _ds_if_table('LoginSession',      'DELETE FROM LoginSession');

-- ---------------------------------------------------------------------------
-- 7. Tokens, secrets, background jobs, outgoing mail addresses
-- ---------------------------------------------------------------------------
CALL _ds_if_table('OAuth_AccessToken',  'DELETE FROM OAuth_AccessToken');
CALL _ds_if_table('OAuth_RefreshToken', 'DELETE FROM OAuth_RefreshToken');
CALL _ds_if_table('OAuth_AuthCode',     'DELETE FROM OAuth_AuthCode');
CALL _ds_if_table('OAuth_PersonalAccessToken', 'DELETE FROM OAuth_PersonalAccessToken');
-- client rows stay (front end needs the client ID); make a new secret in /admin > OAuth Clients
CALL _ds_if_column('OAuth_Client', 'Secret', 'UPDATE OAuth_Client SET Secret=NULL');

CALL _ds_if_column('SiteConfig', 'MailchimpAPIKey',     'UPDATE SiteConfig SET MailchimpAPIKey=NULL');
CALL _ds_if_column('SiteConfig', 'MailchimpListID',     'UPDATE SiteConfig SET MailchimpListID=NULL');
CALL _ds_if_column('SiteConfig', 'MailchimpAudienceID', 'UPDATE SiteConfig SET MailchimpAudienceID=NULL');
CALL _ds_if_column('SiteConfig', 'MailchimpEndpoint',   'UPDATE SiteConfig SET MailchimpEndpoint=NULL');
CALL _ds_if_column('SiteConfig', 'MailchimpURL',        'UPDATE SiteConfig SET MailchimpURL=NULL');
-- internal recipients -> dev-null (public contact addresses on the site stay)
CALL _ds_if_column('SiteConfig', 'ContactRecipients',  "UPDATE SiteConfig SET ContactRecipients  = _ds_fake(ContactRecipients,  'dev-null@example.com')");
CALL _ds_if_column('SiteConfig', 'ContactBcc',         "UPDATE SiteConfig SET ContactBcc         = _ds_fake(ContactBcc,         'dev-null@example.com')");
CALL _ds_if_column('SiteConfig', 'OrderEmail',         "UPDATE SiteConfig SET OrderEmail         = _ds_fake(OrderEmail,         'dev-null@example.com')");
CALL _ds_if_column('SiteConfig', 'InvoiceBccEmail',    "UPDATE SiteConfig SET InvoiceBccEmail    = _ds_fake(InvoiceBccEmail,    'dev-null@example.com')");
CALL _ds_if_column('SiteConfig', 'StudentApplicationRecipient', "UPDATE SiteConfig SET StudentApplicationRecipient = _ds_fake(StudentApplicationRecipient, 'dev-null@example.com')");
CALL _ds_if_column('SiteConfig', 'AccountAffairsRecipient',     "UPDATE SiteConfig SET AccountAffairsRecipient     = _ds_fake(AccountAffairsRecipient,     'dev-null@example.com')");

CALL _ds_if_table('QueuedJobDescriptor', 'DELETE FROM QueuedJobDescriptor');

-- ---------------------------------------------------------------------------
-- 8. Verification — every number below must be 0
-- ---------------------------------------------------------------------------
SELECT 'customers with real-looking email' AS chk, COUNT(*) AS n FROM Cita_eCommerce_Customer
  WHERE Email <> '' AND Email NOT LIKE '%@example.invalid' AND Email NOT LIKE 'test-%@example.com'
UNION ALL SELECT 'customers with real password hash', COUNT(*) FROM Cita_eCommerce_Customer
  WHERE Password IS NOT NULL AND BINARY Password <> BINARY @disabled_pw AND NOT (BINARY Password <=> BINARY @test_pw_hash)
UNION ALL SELECT 'customers with real wechat/linkedin/github', COUNT(*) FROM Cita_eCommerce_Customer
  WHERE (WechatID <> '' AND WechatID NOT LIKE 'wx_test_%')
     OR (LinkedInLink <> '' AND LinkedInLink NOT LIKE 'https://www.linkedin.com/in/example-%')
     OR (Github <> '' AND Github NOT LIKE 'example-%')
UNION ALL SELECT 'addresses with real email', COUNT(*) FROM Cita_eCommerce_Address
  WHERE Email <> '' AND Email NOT LIKE '%@example.invalid'
UNION ALL SELECT 'orders with real email', COUNT(*) FROM Cita_eCommerce_Order
  WHERE Email <> '' AND Email NOT LIKE '%@example.invalid'
UNION ALL SELECT 'payment messages with real IP', COUNT(*) FROM Omnipay_PaymentMessage
  WHERE ClientIp <> '' AND ClientIp <> '127.0.0.1'
UNION ALL SELECT 'admins with password', COUNT(*) FROM Member WHERE Password IS NOT NULL
UNION ALL SELECT 'student-ID files with original name', COUNT(*) FROM File
  WHERE FileFilename LIKE 'customers/%' AND ClassName <> 'SilverStripe\\Assets\\Folder'
    AND Name NOT LIKE 'student-id-%'
UNION ALL SELECT 'student-ID file versions with original name', COUNT(*) FROM File_Versions v
  JOIN _ds_id_files p ON p.ID = v.RecordID WHERE v.Name NOT LIKE 'student-id-%'
UNION ALL SELECT 'tokens/codes/sessions left',
  (SELECT COUNT(*) FROM OAuth_AccessToken) + (SELECT COUNT(*) FROM OAuth_RefreshToken)
  + (SELECT COUNT(*) FROM Cita_eCommerce_MemberVerificationCode) + (SELECT COUNT(*) FROM LoginSession)
  + (SELECT COUNT(*) FROM RememberLoginHash) + (SELECT COUNT(*) FROM LoginAttempt)
UNION ALL SELECT 'mailchimp key left', COUNT(*) FROM SiteConfig WHERE MailchimpAPIKey <> '';

SELECT Label AS test_account, CONCAT('test-', Label, '@example.com') AS login_email,
       IF(@test_pw_hash IS NULL, 'password disabled', 'uses @test_pw_hash') AS password
FROM _ds_test_accounts ORDER BY Label;

-- ---------------------------------------------------------------------------
-- cleanup
-- ---------------------------------------------------------------------------
DROP TABLE _ds_test_accounts;
DROP TABLE _ds_id_files;
DROP PROCEDURE _ds_run;
DROP PROCEDURE _ds_if_table;
DROP PROCEDURE _ds_if_column;
DROP PROCEDURE _ds_id_files_apply;
DROP FUNCTION _ds_fake;
DROP FUNCTION _ds_first;
DROP FUNCTION _ds_last;
