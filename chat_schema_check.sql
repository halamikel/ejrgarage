-- EJR Garage — Live Chat / Chatbot schema check
-- Safe to run even if some of this already exists.
-- Run this in phpMyAdmin on your InfinityFree database.

-- 1. Make sure the `chat_session` column exists on users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS chat_session VARCHAR(20) NOT NULL DEFAULT 'bot';

-- 2. Make sure the ejr_chat table exists (stores live chat messages)
CREATE TABLE IF NOT EXISTS ejr_chat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  sender ENUM('user','admin') NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id)
);

-- 3. Make sure the ejr_bot table exists (stores keyword -> response pairs)
CREATE TABLE IF NOT EXISTS ejr_bot (
  id INT AUTO_INCREMENT PRIMARY KEY,
  keyword VARCHAR(100) NOT NULL,
  response TEXT NOT NULL
);

-- 4. Seed a few starter keywords if the table is empty
INSERT INTO ejr_bot (keyword, response)
SELECT * FROM (SELECT 'services offered' AS keyword, 'We offer engine diagnostics, oil changes, brake repair, suspension work, and full vehicle maintenance.' AS response) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ejr_bot WHERE keyword = 'services offered') LIMIT 1;

INSERT INTO ejr_bot (keyword, response)
SELECT * FROM (SELECT 'booking help' AS keyword, 'You can book an appointment through the Booking page on our site, or I can connect you to a live agent.' AS response) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ejr_bot WHERE keyword = 'booking help') LIMIT 1;

INSERT INTO ejr_bot (keyword, response)
SELECT * FROM (SELECT 'operating hours' AS keyword, 'We are open Monday-Friday 8AM-6PM, Saturday 8AM-5PM, and closed on Sundays.' AS response) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ejr_bot WHERE keyword = 'operating hours') LIMIT 1;

INSERT INTO ejr_bot (keyword, response)
SELECT * FROM (SELECT 'location' AS keyword, 'We are located in Mabalacat City, Pampanga.' AS response) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ejr_bot WHERE keyword = 'location') LIMIT 1;

INSERT INTO ejr_bot (keyword, response)
SELECT * FROM (SELECT 'contact' AS keyword, 'You can reach us at 0906-704-5422 or ejrgaragetest@gmail.com.' AS response) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM ejr_bot WHERE keyword = 'contact') LIMIT 1;
