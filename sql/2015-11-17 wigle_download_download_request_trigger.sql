CREATE TRIGGER `wigle_download_queue_after_update` AFTER UPDATE ON `wigle_download_queue` FOR EACH ROW BEGIN

	IF NEW.count_downloaded_observations = NEW.downloaded_nets_count THEN
		UPDATE download_request SET downloaded_count = downloaded_count + 1 WHERE id = NEW.id_download_request;
	END IF;

END