CREATE TRIGGER `download_request_after_update` AFTER UPDATE ON `download_request` FOR EACH ROW BEGIN

	IF NEW.downloaded_count = NEW.total_count THEN
		UPDATE download_request_waiting SET completed_date = NOW(),completed='Y' WHERE id_download_request_waitfor = NEW.id;
	END IF;

END