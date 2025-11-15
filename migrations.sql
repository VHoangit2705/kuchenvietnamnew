-- Thực hiện migration (up)
ALTER TABLE `users`
ADD COLUMN `password_changed_at` TIMESTAMP NULL DEFAULT NULL AFTER `password`;

-- Rollback migration (down)
ALTER TABLE `users`
DROP COLUMN `password_changed_at`;
