CREATE DATABASE IF NOT EXISTS `sortlot` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `sortlot_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `sortlot`.* TO 'sortlot'@'%';
GRANT ALL PRIVILEGES ON `sortlot_test`.* TO 'sortlot'@'%';
GRANT ALL PRIVILEGES ON `sortlot_test\_%`.* TO 'sortlot'@'%';
FLUSH PRIVILEGES;
